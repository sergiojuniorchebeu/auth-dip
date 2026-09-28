import 'dart:async';
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:file_picker/file_picker.dart';

class ApiException implements Exception {
  ApiException(this.message);
  final String message;
  @override
  String toString() => message;
}

class ApiService {
  ApiService({String? baseUrl}) : baseUrl = baseUrl ?? _defaultBaseUrl;
  final String baseUrl;
  String? token;

  static String get _defaultBaseUrl {
    // localhost works on iOS Simulator; Android Emulator reaches the host via 10.0.2.2.
    return const String.fromEnvironment(
      'AUTHDIP_API_URL',
      defaultValue: 'http://127.0.0.1:8081/api',
    );
  }

  Map<String, String> get _headers => {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    if (token != null) 'Authorization': 'Bearer $token',
  };

  Future<Map<String, dynamic>> login(String email, String password) async {
    final response = await _post('/login', {
      'email': email,
      'password': password,
    });
    token = response['token'] as String?;
    if (token == null) {
      throw ApiException('Le serveur n’a pas retourné de session.');
    }
    return Map<String, dynamic>.from(response['user'] as Map);
  }

  Future<Map<String, dynamic>> registerCompany({
    required String name,
    required String email,
    required String password,
    required String confirmation,
    required Map<String, String> companyFields,
    required List<PlatformFile> documents,
  }) async {
    final request = http.MultipartRequest(
      'POST',
      Uri.parse('$baseUrl/register'),
    );
    request.headers['Accept'] = 'application/json';
    request.fields.addAll({
      'name': name,
      'email': email,
      'password': password,
      'password_confirmation': confirmation,
      ...companyFields,
    });
    for (final file in documents) {
      request.files.add(
        http.MultipartFile.fromBytes(
          'company_documents[]',
          await file.readAsBytes(),
          filename: file.name,
        ),
      );
    }
    final streamed = await request.send().timeout(const Duration(seconds: 15));
    final responseBody = await streamed.stream.bytesToString();
    final decoded = responseBody.isEmpty
        ? <String, dynamic>{}
        : jsonDecode(responseBody) as Map<String, dynamic>;
    if (streamed.statusCode < 200 || streamed.statusCode >= 300) {
      throw ApiException(
        decoded['message']?.toString() ?? 'Impossible de créer le compte.',
      );
    }
    final response = decoded;
    token = response['token'] as String?;
    return Map<String, dynamic>.from(response['user'] as Map);
  }

  Future<void> logout() async {
    if (token != null) await _request('POST', '/logout');
    token = null;
  }

  Future<List<Map<String, dynamic>>> requests() async {
    final response = await _request('GET', '/requests');
    final data = response['data'];
    return data is List
        ? data.map((item) => Map<String, dynamic>.from(item as Map)).toList()
        : [];
  }

  Future<Map<String, dynamic>> submitRequest({
    required String holderName,
    required String diplomaNumber,
    required int year,
    required String program,
  }) => _post('/requests', {
    'holder_name': holderName,
    'diploma_number': diplomaNumber,
    'graduation_year': year,
    'program': program,
  });

  Future<Map<String, dynamic>> decide(String id, String status) =>
      _patch('/requests/$id/decision', {'status': status});

  Future<Map<String, dynamic>> verifyQr(String tokenValue) =>
      _request('GET', '/diplomas/qr/$tokenValue');

  Future<Map<String, dynamic>> _post(String path, Map<String, dynamic> body) =>
      _request('POST', path, body);
  Future<Map<String, dynamic>> _patch(String path, Map<String, dynamic> body) =>
      _request('PATCH', path, body);

  Future<Map<String, dynamic>> _request(
    String method,
    String path, [
    Map<String, dynamic>? body,
  ]) async {
    try {
      final uri = Uri.parse('$baseUrl$path');
      final response = switch (method) {
        'POST' =>
          await http
              .post(uri, headers: _headers, body: jsonEncode(body ?? {}))
              .timeout(const Duration(seconds: 8)),
        'PATCH' =>
          await http
              .patch(uri, headers: _headers, body: jsonEncode(body ?? {}))
              .timeout(const Duration(seconds: 8)),
        _ =>
          await http
              .get(uri, headers: _headers)
              .timeout(const Duration(seconds: 8)),
      };
      final decoded = response.body.isEmpty
          ? <String, dynamic>{}
          : jsonDecode(response.body);
      if (response.statusCode < 200 || response.statusCode >= 300) {
        final message = decoded is Map ? decoded['message']?.toString() : null;
        throw ApiException(
          message ?? 'La requête a échoué (${response.statusCode}).',
        );
      }
      return Map<String, dynamic>.from(decoded as Map);
    } on ApiException {
      rethrow;
    } on TimeoutException {
      throw ApiException(
        'Le serveur ne répond pas. Vérifiez que Laravel est lancé sur le port 8081.',
      );
    } catch (_) {
      throw ApiException(
        'Serveur inaccessible. Démarrez Laravel avec « php artisan serve ».',
      );
    }
  }
}
