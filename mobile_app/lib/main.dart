import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import 'package:file_picker/file_picker.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'api_service.dart';

void main() => runApp(const AuthDipApp());

const primary = Color(0xFFBAE1FF);
const pink = Color(0xFFFFB3BA);
const peach = Color(0xFFFFDFBA);
const yellow = Color(0xFFFFFFBA);
const mint = Color(0xFFBAFFC9);
const ink = Color(0xFF203040);
const canvas = Color(0xFFF9FBFC);
const radius = 5.0;

class RequestItem {
  RequestItem(
    this.id,
    this.reference,
    this.name,
    this.program,
    this.date,
    this.status,
    this.diploma,
  );
  final String id, reference, name, program, date;
  String status;
  final Map<String, dynamic>? diploma;

  factory RequestItem.fromApi(Map<String, dynamic> json) => RequestItem(
    json['id']?.toString() ?? '0',
    json['reference']?.toString() ?? 'Demande',
    json['holder_name']?.toString() ?? 'Titulaire inconnu',
    json['program']?.toString() ?? 'Diplôme',
    json['created_at']?.toString().split('T').first ?? '',
    switch (json['status']) {
      'validated' => 'Validée',
      'rejected' => 'Rejetée',
      _ => 'En attente',
    },
    json['diploma'] is Map
        ? Map<String, dynamic>.from(json['diploma'] as Map)
        : null,
  );

  String get diplomaNumber =>
      diploma?['number']?.toString() ?? 'Numéro non renseigné';
  String get diplomaType => diploma?['diploma_type']?.toString() ?? program;
  String get specialty =>
      diploma?['specialty']?.toString() ??
      diploma?['program']?.toString() ??
      program;
  String get graduationYear => diploma?['graduation_year']?.toString() ?? '—';
  String get average => diploma?['average']?.toString() ?? '—';
  String get mention => diploma?['mention']?.toString() ?? '—';
  String get institution =>
      diploma?['institution']?.toString() ?? 'IAI Cameroun';
}

class SessionStore {
  static const tokenKey = 'authdip_token';
  static const userIdKey = 'authdip_user_id';
  static const roleKey = 'authdip_role';
  static const nameKey = 'authdip_name';

  static Future<void> save(String token, Map<String, dynamic> user) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(tokenKey, token);
    await prefs.setString(userIdKey, user['id'].toString());
    await prefs.setString(roleKey, user['role']?.toString() ?? 'employer');
    await prefs.setString(nameKey, user['name']?.toString() ?? 'Utilisateur');
  }

  static Future<Map<String, String>?> read() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString(tokenKey);
    if (token == null) return null;
    return {
      'token': token,
      'role': prefs.getString(roleKey) ?? 'employer',
      'name': prefs.getString(nameKey) ?? 'Utilisateur',
    };
  }

  static Future<void> clear() async =>
      (await SharedPreferences.getInstance()).clear();
}

class AuthDipApp extends StatelessWidget {
  const AuthDipApp({super.key});
  @override
  Widget build(BuildContext context) => MaterialApp(
    debugShowCheckedModeBanner: false,
    title: 'AuthDip',
    theme: ThemeData(
      useMaterial3: true,
      scaffoldBackgroundColor: canvas,
      colorScheme: ColorScheme.fromSeed(
        seedColor: primary,
        brightness: Brightness.light,
      ),
      textTheme: GoogleFonts.poppinsTextTheme().apply(
        bodyColor: ink,
        displayColor: ink,
      ),
      appBarTheme: const AppBarTheme(
        backgroundColor: canvas,
        foregroundColor: ink,
        elevation: 0,
        centerTitle: false,
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: Colors.white,
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 16,
          vertical: 15,
        ),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radius),
          borderSide: BorderSide.none,
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radius),
          borderSide: const BorderSide(color: Color(0xFFE7EDF1)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radius),
          borderSide: const BorderSide(color: ink),
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: ink,
          foregroundColor: Colors.white,
          minimumSize: const Size.fromHeight(52),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(radius),
          ),
          textStyle: GoogleFonts.poppins(fontWeight: FontWeight.w600),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: ink,
          side: const BorderSide(color: ink),
          minimumSize: const Size.fromHeight(52),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(radius),
          ),
          textStyle: GoogleFonts.poppins(fontWeight: FontWeight.w600),
        ),
      ),
    ),
    home: const SessionGate(),
  );
}

class SessionGate extends StatefulWidget {
  const SessionGate({super.key});
  @override
  State<SessionGate> createState() => _SessionGateState();
}

class _SessionGateState extends State<SessionGate> {
  Map<String, String>? session;
  @override
  void initState() {
    super.initState();
    _read();
  }

  Future<void> _read() async {
    final value = await SessionStore.read();
    if (mounted) setState(() => session = value);
  }

  @override
  Widget build(BuildContext context) => session == null
      ? LandingPage(onStarted: () => setState(() => session = {}))
      : session!.isEmpty
      ? const LoginPage()
      : DashboardPage(
          api: ApiService()..token = session!['token'],
          admin: session!['role'] == 'admin',
          userName: session!['name']!,
        );
}

class LandingPage extends StatelessWidget {
  const LandingPage({super.key, required this.onStarted});
  final VoidCallback onStarted;
  @override
  Widget build(BuildContext context) => Scaffold(
    body: SafeArea(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(24, 38, 24, 18),
        child: Column(
          children: [
            const Align(alignment: Alignment.centerLeft, child: Brand()),
            const Spacer(),
            Image.asset(
              'assets/Login-pana.png',
              height: MediaQuery.sizeOf(context).height < 700
                  ? 215
                  : (MediaQuery.sizeOf(context).width < 500 ? 265 : 340),
              fit: BoxFit.contain,
            ),
            const SizedBox(height: 32),
            const Text(
              'Vos diplômes.\nVotre confiance.',
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 30,
                height: 1.2,
                fontWeight: FontWeight.w700,
                color: ink,
              ),
            ),
            const SizedBox(height: 14),
            const Text(
              'La plateforme AuthDip simplifie la vérification des diplômes de l’IAI, rapidement et en toute sécurité.',
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 14,
                height: 1.6,
                color: Colors.black54,
              ),
            ),
            const Spacer(),
            SizedBox(
              width: double.infinity,
              child: FilledButton(
                onPressed: onStarted,
                child: const Text('Commencer'),
              ),
            ),
          ],
        ),
      ),
    ),
  );
}

class Brand extends StatelessWidget {
  const Brand({super.key});
  @override
  Widget build(BuildContext context) => Row(
    children: [
      Container(
        width: 34,
        height: 34,
        decoration: BoxDecoration(
          color: primary,
          borderRadius: BorderRadius.circular(radius),
        ),
        child: const Icon(Icons.verified_user_outlined, color: ink, size: 20),
      ),
      const SizedBox(width: 10),
      const Text(
        'AuthDip',
        style: TextStyle(fontSize: 20, fontWeight: FontWeight.w700, color: ink),
      ),
    ],
  );
}

class LoginPage extends StatefulWidget {
  const LoginPage({super.key});
  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage> {
  final email = TextEditingController(), password = TextEditingController();
  bool loading = false, obscure = true;
  String? error;
  @override
  void dispose() {
    email.dispose();
    password.dispose();
    super.dispose();
  }

  Future<void> _login() async {
    setState(() {
      loading = true;
      error = null;
    });
    try {
      final api = ApiService();
      final user = await api.login(email.text.trim(), password.text);
      await SessionStore.save(api.token!, user);
      if (mounted) {
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(
            builder: (_) => DashboardPage(
              api: api,
              admin: user['role'] == 'admin',
              userName: user['name']?.toString() ?? 'Utilisateur',
            ),
          ),
        );
      }
    } on ApiException catch (exception) {
      if (mounted) setState(() => error = exception.message);
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    body: SafeArea(
      child: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(24, 32, 24, 32),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Brand(),
            const SizedBox(height: 20),
            Center(child: Image.asset('assets/sign_up.png', height: 180)),
            const SizedBox(height: 24),
            const Text(
              'Connexion',
              style: TextStyle(
                fontSize: 30,
                fontWeight: FontWeight.w700,
                color: ink,
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              'Accédez à votre espace sécurisé.',
              style: TextStyle(color: Colors.black54),
            ),
            const SizedBox(height: 32),
            if (error != null) SoftMessage(text: error!, color: pink),
            if (error != null) const SizedBox(height: 16),
            const FieldLabel('Adresse e-mail'),
            const SizedBox(height: 8),
            TextField(
              controller: email,
              keyboardType: TextInputType.emailAddress,
              decoration: const InputDecoration(
                hintText: 'nom@entreprise.com',
                prefixIcon: Icon(Icons.mail_outline),
              ),
            ),
            const SizedBox(height: 20),
            const FieldLabel('Mot de passe'),
            const SizedBox(height: 8),
            TextField(
              controller: password,
              obscureText: obscure,
              decoration: InputDecoration(
                hintText: 'Votre mot de passe',
                prefixIcon: const Icon(Icons.lock_outline),
                suffixIcon: IconButton(
                  onPressed: () => setState(() => obscure = !obscure),
                  icon: Icon(
                    obscure
                        ? Icons.visibility_outlined
                        : Icons.visibility_off_outlined,
                  ),
                ),
              ),
            ),
            const SizedBox(height: 30),
            SizedBox(
              width: double.infinity,
              child: FilledButton(
                onPressed: loading ? null : _login,
                child: loading
                    ? const SizedBox(
                        width: 22,
                        height: 22,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: Colors.white,
                        ),
                      )
                    : const Text('Se connecter'),
              ),
            ),
            const SizedBox(height: 20),
            Center(
              child: TextButton(
                onPressed: () {},
                child: const Text('Mot de passe oublié ?'),
              ),
            ),
            Center(
              child: TextButton(
                onPressed: () => Navigator.push(
                  context,
                  MaterialPageRoute(builder: (_) => const SignupPage()),
                ),
                child: const Text('Créer un compte employeur'),
              ),
            ),
          ],
        ),
      ),
    ),
  );
}

class SignupPage extends StatefulWidget {
  const SignupPage({super.key});
  @override
  State<SignupPage> createState() => _SignupPageState();
}

class _SignupPageState extends State<SignupPage> {
  final name = TextEditingController(),
      email = TextEditingController(),
      password = TextEditingController(),
      confirmation = TextEditingController(),
      companyName = TextEditingController(),
      registrationNumber = TextEditingController(),
      taxNumber = TextEditingController(),
      sector = TextEditingController(),
      phone = TextEditingController(),
      address = TextEditingController(),
      city = TextEditingController(),
      country = TextEditingController(text: 'Cameroun'),
      position = TextEditingController();
  List<PlatformFile> documents = [];
  bool loading = false, obscure = true;
  String? error;
  @override
  void dispose() {
    name.dispose();
    email.dispose();
    password.dispose();
    confirmation.dispose();
    companyName.dispose();
    registrationNumber.dispose();
    taxNumber.dispose();
    sector.dispose();
    phone.dispose();
    address.dispose();
    city.dispose();
    country.dispose();
    position.dispose();
    super.dispose();
  }

  Future<void> _pickDocuments() async {
    final result = await FilePicker.pickFiles(
      type: FileType.custom,
      allowedExtensions: ['pdf', 'jpg', 'jpeg', 'png'],
    );
    if (mounted) setState(() => documents = result);
  }

  Future<void> _register() async {
    setState(() {
      loading = true;
      error = null;
    });
    try {
      final api = ApiService();
      if (documents.isEmpty)
        throw ApiException(
          'Ajoutez au moins une pièce justificative de l’entreprise.',
        );
      final user = await api.registerCompany(
        name: name.text.trim(),
        email: email.text.trim(),
        password: password.text,
        confirmation: confirmation.text,
        companyFields: {
          'company_legal_name': companyName.text.trim(),
          'company_registration_number': registrationNumber.text.trim(),
          'company_tax_number': taxNumber.text.trim(),
          'company_sector': sector.text.trim(),
          'company_phone': phone.text.trim(),
          'company_address': address.text.trim(),
          'company_city': city.text.trim(),
          'company_country': country.text.trim(),
          'contact_position': position.text.trim(),
        },
        documents: documents,
      );
      if (api.token == null) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text(
                'Dossier envoyé. Votre compte sera activé après validation par l’IAI.',
              ),
            ),
          );
          Navigator.pop(context);
        }
        return;
      }
      await SessionStore.save(api.token!, user);
      if (mounted)
        Navigator.pushAndRemoveUntil(
          context,
          MaterialPageRoute(
            builder: (_) => DashboardPage(
              api: api,
              admin: false,
              userName: user['name']?.toString() ?? name.text,
            ),
          ),
          (_) => false,
        );
    } on ApiException catch (exception) {
      if (mounted) setState(() => error = exception.message);
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: const Text('Créer un compte')),
    body: SafeArea(
      child: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(24, 18, 24, 32),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Center(child: Image.asset('assets/sign_up.png', height: 150)),
            const SizedBox(height: 14),
            const Text(
              'Créer votre espace employeur',
              style: TextStyle(
                fontSize: 25,
                fontWeight: FontWeight.w700,
                color: ink,
              ),
            ),
            const SizedBox(height: 6),
            const Text(
              'Envoyez et suivez vos demandes de vérification.',
              style: TextStyle(color: Colors.black54),
            ),
            const SizedBox(height: 24),
            if (error != null) ...[
              SoftMessage(text: error!, color: pink),
              const SizedBox(height: 14),
            ],
            const SectionTitle('Informations de l’entreprise'),
            const SizedBox(height: 12),
            AppTextField(
              controller: companyName,
              label: 'Raison sociale',
              icon: Icons.business_outlined,
            ),
            const SizedBox(height: 12),
            AppTextField(
              controller: registrationNumber,
              label: 'Numéro RCCM / registre',
              icon: Icons.badge_outlined,
            ),
            const SizedBox(height: 12),
            AppTextField(
              controller: taxNumber,
              label: 'Identifiant fiscal',
              icon: Icons.numbers_outlined,
            ),
            const SizedBox(height: 12),
            AppTextField(
              controller: sector,
              label: 'Secteur d’activité',
              icon: Icons.work_outline,
            ),
            const SizedBox(height: 12),
            AppTextField(
              controller: phone,
              label: 'Téléphone professionnel',
              icon: Icons.phone_outlined,
              keyboardType: TextInputType.phone,
            ),
            const SizedBox(height: 12),
            AppTextField(
              controller: address,
              label: 'Adresse complète',
              icon: Icons.location_on_outlined,
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: AppTextField(controller: city, label: 'Ville'),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: AppTextField(controller: country, label: 'Pays'),
                ),
              ],
            ),
            const SizedBox(height: 12),
            AppTextField(
              controller: position,
              label: 'Fonction du responsable',
              icon: Icons.person_pin_outlined,
            ),
            const SizedBox(height: 24),
            const SectionTitle('Responsable du compte'),
            const SizedBox(height: 12),
            const FieldLabel('Nom de l’entreprise ou du responsable'),
            const SizedBox(height: 8),
            AppTextField(
              controller: name,
              label: 'Nom complet',
              icon: Icons.person_outline,
            ),
            const SizedBox(height: 16),
            const FieldLabel('Adresse e-mail'),
            const SizedBox(height: 8),
            AppTextField(
              controller: email,
              label: 'nom@entreprise.com',
              icon: Icons.mail_outline,
              keyboardType: TextInputType.emailAddress,
            ),
            const SizedBox(height: 16),
            const FieldLabel('Mot de passe'),
            const SizedBox(height: 8),
            TextField(
              controller: password,
              obscureText: obscure,
              decoration: InputDecoration(
                labelText: '8 caractères minimum',
                prefixIcon: const Icon(Icons.lock_outline),
                suffixIcon: IconButton(
                  onPressed: () => setState(() => obscure = !obscure),
                  icon: Icon(
                    obscure
                        ? Icons.visibility_outlined
                        : Icons.visibility_off_outlined,
                  ),
                ),
              ),
            ),
            const SizedBox(height: 16),
            const FieldLabel('Confirmer le mot de passe'),
            const SizedBox(height: 8),
            TextField(
              controller: confirmation,
              obscureText: true,
              decoration: const InputDecoration(
                labelText: 'Répétez le mot de passe',
                prefixIcon: Icon(Icons.lock_reset_outlined),
              ),
            ),
            const SizedBox(height: 24),
            const SectionTitle('Pièces justificatives'),
            const SizedBox(height: 8),
            const Text(
              'Ajoutez au moins un document : RCCM, identifiant fiscal ou document officiel. PDF/JPG/PNG, 5 Mo maximum.',
              style: TextStyle(fontSize: 12, color: Colors.black54),
            ),
            const SizedBox(height: 10),
            OutlinedButton.icon(
              onPressed: loading ? null : _pickDocuments,
              icon: const Icon(Icons.attach_file),
              label: Text(
                documents.isEmpty
                    ? 'Choisir les pièces'
                    : '${documents.length} pièce(s) sélectionnée(s)',
              ),
            ),
            const SizedBox(height: 26),
            SizedBox(
              width: double.infinity,
              child: FilledButton(
                onPressed: loading ? null : _register,
                child: loading
                    ? const CircularProgressIndicator(color: Colors.white)
                    : const Text('Créer mon compte'),
              ),
            ),
          ],
        ),
      ),
    ),
  );
}

class DashboardPage extends StatefulWidget {
  const DashboardPage({
    super.key,
    required this.api,
    required this.admin,
    required this.userName,
  });
  final ApiService api;
  final bool admin;
  final String userName;
  @override
  State<DashboardPage> createState() => _DashboardPageState();
}

class _DashboardPageState extends State<DashboardPage> {
  int selected = 0;
  bool loading = true;
  String? error;
  List<RequestItem> requests = [];
  final holder = TextEditingController(),
      number = TextEditingController(),
      year = TextEditingController(),
      program = TextEditingController(),
      qrToken = TextEditingController();
  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    holder.dispose();
    number.dispose();
    year.dispose();
    program.dispose();
    qrToken.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    try {
      final data = await widget.api.requests();
      if (mounted) {
        setState(() {
          requests = data.map(RequestItem.fromApi).toList();
          loading = false;
        });
      }
    } on ApiException catch (e) {
      if (mounted) {
        setState(() {
          error = e.message;
          loading = false;
        });
      }
    }
  }

  Future<void> _scanQr() async {
    final value = await Navigator.of(
      context,
    ).push<String>(MaterialPageRoute(builder: (_) => const QrScannerPage()));
    if (!mounted || value == null || value.isEmpty) return;
    final uri = Uri.tryParse(value);
    final token = uri != null && uri.pathSegments.isNotEmpty
        ? uri.pathSegments.last
        : value;
    qrToken.text = token;
    try {
      final result = await widget.api.verifyQr(token);
      final diploma = Map<String, dynamic>.from(result['diploma'] as Map);
      if (!mounted) return;
      showDialog<void>(
        context: context,
        builder: (_) => AlertDialog(
          title: const Text('Diplôme authentifié'),
          content: Text(
            '${diploma['holder_name']}\n${diploma['program']}\n${diploma['number']}',
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('Fermer'),
            ),
          ],
        ),
      );
    } on ApiException catch (e) {
      if (mounted) _message(e.message, pink);
    }
  }

  Future<void> _submit() async {
    try {
      await widget.api.submitRequest(
        holderName: holder.text.trim(),
        diplomaNumber: number.text.trim(),
        year: int.tryParse(year.text) ?? 0,
        program: program.text.trim(),
      );
      holder.clear();
      number.clear();
      year.clear();
      program.clear();
      await _load();
      if (mounted) _message('Demande envoyée au service IAI.', mint);
    } on ApiException catch (e) {
      if (mounted) _message(e.message, pink);
    }
  }

  Future<void> _decide(RequestItem item, String value) async {
    try {
      await widget.api.decide(item.id, value);
      setState(
        () => item.status = value == 'validated' ? 'Validée' : 'Rejetée',
      );
      if (mounted) _message('Décision enregistrée.', mint);
    } on ApiException catch (e) {
      if (mounted) _message(e.message, pink);
    }
  }

  Future<void> _logout() async {
    await widget.api.logout();
    await SessionStore.clear();
    if (mounted) {
      Navigator.pushAndRemoveUntil(
        context,
        MaterialPageRoute(builder: (_) => const LandingPage(onStarted: _noop)),
        (_) => false,
      );
    }
  }

  static void _noop() {}
  void _message(String message, Color color) =>
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(message),
          backgroundColor: color,
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(radius),
          ),
        ),
      );
  @override
  Widget build(BuildContext context) {
    final pages = widget.admin
        ? [_overview(), _adminRequests(), _diplomas(), _profile()]
        : [_overview(), _newRequest(), _myRequests(), _verify()];
    final labels = widget.admin
        ? ['Accueil', 'Demandes', 'Diplômes', 'Profil']
        : ['Accueil', 'Nouvelle', 'Demandes', 'Vérifier'];
    final icons = widget.admin
        ? [
            Icons.home_outlined,
            Icons.inbox_outlined,
            Icons.school_outlined,
            Icons.person_outline,
          ]
        : [
            Icons.home_outlined,
            Icons.add_circle_outline,
            Icons.history,
            Icons.qr_code_scanner,
          ];
    return Scaffold(
      appBar: AppBar(
        title: const Brand(),
        actions: [
          IconButton(
            onPressed: _logout,
            icon: const Icon(Icons.logout_outlined),
          ),
          const SizedBox(width: 8),
        ],
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(20, 14, 20, 26),
          child: pages[selected],
        ),
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: selected,
        onDestinationSelected: (value) => setState(() => selected = value),
        backgroundColor: Colors.white,
        indicatorColor: primary,
        destinations: List.generate(
          labels.length,
          (i) => NavigationDestination(icon: Icon(icons[i]), label: labels[i]),
        ),
      ),
    );
  }

  Widget _overview() => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Text(
        'Bonjour, ${widget.userName}',
        style: const TextStyle(
          fontSize: 24,
          fontWeight: FontWeight.w700,
          color: ink,
        ),
      ),
      const SizedBox(height: 6),
      Text(
        widget.admin
            ? 'Voici le suivi de votre activité IAI.'
            : 'Gérez vos vérifications de diplômes.',
        style: const TextStyle(color: Colors.black54),
      ),
      if (error != null) ...[
        const SizedBox(height: 14),
        SoftMessage(text: error!, color: peach),
      ],
      const SizedBox(height: 24),
      Row(
        children: [
          Expanded(
            child: MetricCard(
              label: widget.admin ? 'Demandes' : 'Mes demandes',
              value: widget.admin ? '${requests.length}' : '${requests.length}',
              color: primary,
              icon: Icons.description_outlined,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: MetricCard(
              label: widget.admin ? 'En attente' : 'Validées',
              value:
                  '${requests.where((r) => r.status == (widget.admin ? 'En attente' : 'Validée')).length}',
              color: widget.admin ? yellow : mint,
              icon: Icons.pending_actions,
            ),
          ),
        ],
      ),
      const SizedBox(height: 24),
      const SectionTitle('Actions rapides'),
      const SizedBox(height: 12),
      Row(
        children: [
          Expanded(
            child: ActionTile(
              title: widget.admin ? 'Traiter les demandes' : 'Nouvelle demande',
              icon: widget.admin
                  ? Icons.inbox_outlined
                  : Icons.add_circle_outline,
              color: peach,
              onTap: () => setState(() => selected = widget.admin ? 1 : 1),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: ActionTile(
              title: 'Vérifier un diplôme',
              icon: Icons.qr_code_scanner,
              color: blueish,
              onTap: () => setState(() => selected = widget.admin ? 2 : 3),
            ),
          ),
        ],
      ),
      const SizedBox(height: 26),
      const SectionTitle('Activité récente'),
      const SizedBox(height: 10),
      _requestList(requests.take(3).toList()),
    ],
  );
  Widget _newRequest() => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      const PageTitle(
        'Nouvelle demande',
        subtitle: 'Transmettez les informations du diplôme à vérifier.',
      ),
      const SizedBox(height: 22),
      Image.asset('assets/college admission-rafiki.png', height: 120),
      const SizedBox(height: 16),
      AppCard(
        child: Column(
          children: [
            AppTextField(
              controller: holder,
              label: 'Nom du titulaire',
              icon: Icons.person_outline,
            ),
            const SizedBox(height: 14),
            AppTextField(
              controller: number,
              label: 'Numéro du diplôme',
              icon: Icons.tag,
            ),
            const SizedBox(height: 14),
            Row(
              children: [
                Expanded(
                  child: AppTextField(
                    controller: year,
                    label: 'Année',
                    icon: Icons.calendar_today_outlined,
                    keyboardType: TextInputType.number,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: AppTextField(controller: program, label: 'Filière'),
                ),
              ],
            ),
            const SizedBox(height: 22),
            SizedBox(
              width: double.infinity,
              child: FilledButton.icon(
                onPressed: _submit,
                icon: const Icon(Icons.send_outlined),
                label: const Text('Envoyer la demande'),
              ),
            ),
          ],
        ),
      ),
    ],
  );
  Widget _myRequests() => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      const PageTitle(
        'Mes demandes',
        subtitle: 'Suivez l’avancement de vos vérifications.',
      ),
      const SizedBox(height: 22),
      _requestList(requests),
    ],
  );
  Widget _adminRequests() => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      const PageTitle(
        'Demandes à traiter',
        subtitle: 'Comparez les données et prenez une décision.',
      ),
      const SizedBox(height: 22),
      _requestList(requests, actions: true),
    ],
  );
  Widget _diplomas() => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      const PageTitle(
        'Diplômes',
        subtitle: 'Consultez la base centralisée de l’IAI.',
      ),
      const SizedBox(height: 22),
      const AppTextField(label: 'Rechercher un diplôme', icon: Icons.search),
      const SizedBox(height: 16),
      AppCard(
        child: Column(
          children: const [
            ListTile(
              contentPadding: EdgeInsets.zero,
              leading: Icon(Icons.school_outlined, color: ink),
              title: Text('Licence Professionnelle'),
              subtitle: Text('IAI-LP-2026-00482 · Informatique'),
              trailing: Icon(Icons.qr_code_2),
            ),
            Divider(),
            ListTile(
              contentPadding: EdgeInsets.zero,
              leading: Icon(Icons.school_outlined, color: ink),
              title: Text('Master en Informatique'),
              subtitle: Text('IAI-MI-2025-00117 · Génie logiciel'),
              trailing: Icon(Icons.qr_code_2),
            ),
          ],
        ),
      ),
    ],
  );
  Widget _profile() => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      const PageTitle('Mon profil', subtitle: 'Compte administrateur IAI'),
      const SizedBox(height: 22),
      AppCard(
        child: Column(
          children: [
            const CircleAvatar(
              radius: 34,
              backgroundColor: primary,
              child: Icon(Icons.person_outline, color: ink, size: 34),
            ),
            const SizedBox(height: 14),
            Text(
              widget.userName,
              style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 18),
            ),
            const Text(
              'Administrateur IAI',
              style: TextStyle(color: Colors.black54),
            ),
            const SizedBox(height: 22),
            SizedBox(
              width: double.infinity,
              child: OutlinedButton(
                onPressed: _logout,
                child: const Text('Se déconnecter'),
              ),
            ),
          ],
        ),
      ),
    ],
  );
  Widget _verify() => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      const PageTitle(
        'Vérifier un diplôme',
        subtitle: 'Saisissez le token présent dans le QR code.',
      ),
      const SizedBox(height: 22),
      AppCard(
        child: Column(
          children: [
            Image.asset('assets/graduation hats-cuate.png', height: 150),
            AppTextField(
              controller: qrToken,
              label: 'Token QR',
              icon: Icons.qr_code_2,
            ),
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              child: FilledButton.icon(
                onPressed: _scanQr,
                icon: const Icon(Icons.camera_alt_outlined),
                label: const Text('Scanner le QR code'),
              ),
            ),
          ],
        ),
      ),
    ],
  );
  Widget _requestList(List<RequestItem> items, {bool actions = false}) =>
      items.isEmpty
      ? const AppCard(
          child: Center(
            child: Padding(
              padding: EdgeInsets.all(24),
              child: Text(
                'Aucune demande enregistrée.',
                style: TextStyle(color: Colors.black54),
              ),
            ),
          ),
        )
      : Column(
          children: items
              .map(
                (item) => Padding(
                  padding: const EdgeInsets.only(bottom: 10),
                  child: AppCard(
                    child: Column(
                      children: [
                        Row(
                          children: [
                            Container(
                              width: 40,
                              height: 40,
                              decoration: BoxDecoration(
                                color: primary,
                                borderRadius: BorderRadius.circular(radius),
                              ),
                              child: const Icon(
                                Icons.description_outlined,
                                color: ink,
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    item.name,
                                    style: const TextStyle(
                                      fontWeight: FontWeight.w700,
                                      color: ink,
                                    ),
                                  ),
                                  Text(
                                    item.program,
                                    style: const TextStyle(
                                      fontSize: 12,
                                      color: Colors.black54,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            StatusChip(item.status),
                          ],
                        ),
                        if (item.status == 'Validée') ...[
                          const SizedBox(height: 16),
                          Container(
                            width: double.infinity,
                            padding: const EdgeInsets.all(14),
                            decoration: BoxDecoration(
                              color: mint.withValues(alpha: .35),
                              borderRadius: BorderRadius.circular(radius),
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text(
                                  'Détails du diplôme validé',
                                  style: TextStyle(
                                    fontWeight: FontWeight.w700,
                                    color: ink,
                                  ),
                                ),
                                const SizedBox(height: 10),
                                _detailLine('Type', item.diplomaType),
                                _detailLine('Filière', item.specialty),
                                _detailLine('Numéro', item.diplomaNumber),
                                _detailLine('Année', item.graduationYear),
                                _detailLine(
                                  'Moyenne',
                                  item.average == '—'
                                      ? 'Non renseignée'
                                      : '${item.average}/20',
                                ),
                                _detailLine('Mention', item.mention),
                                _detailLine('Établissement', item.institution),
                              ],
                            ),
                          ),
                        ],
                        if (actions && item.status == 'En attente') ...[
                          const SizedBox(height: 14),
                          Row(
                            children: [
                              Expanded(
                                child: OutlinedButton(
                                  onPressed: () => _decide(item, 'rejected'),
                                  child: const Text('Rejeter'),
                                ),
                              ),
                              const SizedBox(width: 10),
                              Expanded(
                                child: FilledButton(
                                  onPressed: () => _decide(item, 'validated'),
                                  child: const Text('Valider'),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ],
                    ),
                  ),
                ),
              )
              .toList(),
        );

  Widget _detailLine(String label, String value) => Padding(
    padding: const EdgeInsets.only(bottom: 5),
    child: Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(
          width: 105,
          child: Text(
            label,
            style: const TextStyle(fontSize: 12, color: Colors.black54),
          ),
        ),
        Expanded(
          child: Text(
            value,
            style: const TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w600,
              color: ink,
            ),
          ),
        ),
      ],
    ),
  );
}

const blueish = Color(0xFFBAE1FF);

class PageTitle extends StatelessWidget {
  const PageTitle(this.title, {super.key, required this.subtitle});
  final String title, subtitle;
  @override
  Widget build(BuildContext context) => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Text(
        title,
        style: const TextStyle(
          fontSize: 25,
          fontWeight: FontWeight.w700,
          color: ink,
        ),
      ),
      const SizedBox(height: 5),
      Text(subtitle, style: const TextStyle(color: Colors.black54)),
    ],
  );
}

class SectionTitle extends StatelessWidget {
  const SectionTitle(this.text, {super.key});
  final String text;
  @override
  Widget build(BuildContext context) => Text(
    text,
    style: const TextStyle(
      fontSize: 17,
      fontWeight: FontWeight.w700,
      color: ink,
    ),
  );
}

class FieldLabel extends StatelessWidget {
  const FieldLabel(this.text, {super.key});
  final String text;
  @override
  Widget build(BuildContext context) => Text(
    text,
    style: const TextStyle(fontWeight: FontWeight.w600, color: ink),
  );
}

class QrScannerPage extends StatefulWidget {
  const QrScannerPage({super.key});
  @override
  State<QrScannerPage> createState() => _QrScannerPageState();
}

class _QrScannerPageState extends State<QrScannerPage> {
  bool handled = false;
  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: const Text('Scanner un QR code')),
    body: MobileScanner(
      onDetect: (capture) {
        if (handled || capture.barcodes.isEmpty) return;
        final value = capture.barcodes.first.rawValue;
        if (value == null || value.isEmpty) return;
        handled = true;
        Navigator.of(context).pop(value);
      },
    ),
  );
}

class AppTextField extends StatelessWidget {
  const AppTextField({
    super.key,
    this.controller,
    required this.label,
    this.icon,
    this.keyboardType,
  });
  final TextEditingController? controller;
  final String label;
  final IconData? icon;
  final TextInputType? keyboardType;
  @override
  Widget build(BuildContext context) => TextField(
    controller: controller,
    keyboardType: keyboardType,
    decoration: InputDecoration(
      labelText: label,
      prefixIcon: icon == null ? null : Icon(icon),
    ),
  );
}

class AppCard extends StatelessWidget {
  const AppCard({super.key, required this.child});
  final Widget child;
  @override
  Widget build(BuildContext context) => Container(
    width: double.infinity,
    padding: const EdgeInsets.all(16),
    decoration: BoxDecoration(
      color: Colors.white,
      borderRadius: BorderRadius.circular(radius),
      border: Border.all(color: const Color(0xFFE8EEF2)),
    ),
    child: child,
  );
}

class MetricCard extends StatelessWidget {
  const MetricCard({
    super.key,
    required this.label,
    required this.value,
    required this.color,
    required this.icon,
  });
  final String label, value;
  final Color color;
  final IconData icon;
  @override
  Widget build(BuildContext context) => AppCard(
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          padding: const EdgeInsets.all(9),
          decoration: BoxDecoration(
            color: color,
            borderRadius: BorderRadius.circular(radius),
          ),
          child: Icon(icon, color: ink, size: 20),
        ),
        const SizedBox(height: 14),
        Text(
          value,
          style: const TextStyle(
            fontSize: 25,
            fontWeight: FontWeight.w700,
            color: ink,
          ),
        ),
        Text(
          label,
          style: const TextStyle(fontSize: 12, color: Colors.black54),
        ),
      ],
    ),
  );
}

class ActionTile extends StatelessWidget {
  const ActionTile({
    super.key,
    required this.title,
    required this.icon,
    required this.color,
    required this.onTap,
  });
  final String title;
  final IconData icon;
  final Color color;
  final VoidCallback onTap;
  @override
  Widget build(BuildContext context) => InkWell(
    onTap: onTap,
    borderRadius: BorderRadius.circular(radius),
    child: AppCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(9),
            decoration: BoxDecoration(
              color: color,
              borderRadius: BorderRadius.circular(radius),
            ),
            child: Icon(icon, color: ink),
          ),
          const SizedBox(height: 12),
          Text(
            title,
            style: const TextStyle(fontWeight: FontWeight.w600, color: ink),
          ),
        ],
      ),
    ),
  );
}

class StatusChip extends StatelessWidget {
  const StatusChip(this.status, {super.key});
  final String status;
  @override
  Widget build(BuildContext context) {
    final color = status == 'Validée'
        ? mint
        : status == 'Rejetée'
        ? pink
        : yellow;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 6),
      decoration: BoxDecoration(
        color: color,
        borderRadius: BorderRadius.circular(radius),
      ),
      child: Text(
        status,
        style: const TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w600,
          color: ink,
        ),
      ),
    );
  }
}

class SoftMessage extends StatelessWidget {
  const SoftMessage({super.key, required this.text, required this.color});
  final String text;
  final Color color;
  @override
  Widget build(BuildContext context) => Container(
    width: double.infinity,
    padding: const EdgeInsets.all(13),
    decoration: BoxDecoration(
      color: color,
      borderRadius: BorderRadius.circular(radius),
    ),
    child: Text(text, style: const TextStyle(fontSize: 12, color: ink)),
  );
}
