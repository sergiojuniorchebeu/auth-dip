<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Diploma;
use App\Models\VerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VerificationController extends Controller
{
    public function index(Request $request)
    {
        $query = VerificationRequest::with(['diploma', 'employer'])->latest();
        if ($request->user()->role === 'employer') $query->where('employer_id', $request->user()->id);
        return response()->json($query->paginate(20));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->role === 'employer', 403, 'Accès réservé aux employeurs.');
        $data = $request->validate([
            'holder_name' => ['required', 'string', 'max:150'], 'candidate_birth_date' => ['nullable', 'date'], 'candidate_birth_place' => ['nullable', 'string', 'max:150'], 'candidate_id_number' => ['nullable', 'string', 'max:100'], 'candidate_email' => ['nullable', 'email'], 'candidate_phone' => ['nullable', 'string', 'max:40'],
            'diploma_number' => ['required', 'string', 'max:100'], 'diploma_type' => ['nullable', 'string', 'max:150'], 'specialty' => ['nullable', 'string', 'max:150'], 'declared_average' => ['nullable', 'numeric', 'min:0', 'max:20'], 'graduation_year' => ['required', 'integer', 'min:1950', 'max:2100'], 'program' => ['required', 'string', 'max:150'], 'employment_position' => ['nullable', 'string', 'max:150'], 'employment_reference' => ['nullable', 'string', 'max:100'], 'verification_purpose' => ['nullable', 'string', 'max:1000'], 'candidate_consent' => ['sometimes', 'accepted'],
            'candidate_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], 'diploma_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], 'transcript_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], 'authorization_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);
        $data['reference'] = 'REQ-'.now()->format('Y').'-'.str_pad((string) (VerificationRequest::count() + 1), 4, '0', STR_PAD_LEFT);
        $data['diploma_id'] = Diploma::where('number', $data['diploma_number'])->value('id');
        $data['diploma_type'] ??= $data['program'];
        $data['specialty'] ??= $data['program'];
        $data['employer_id'] = $request->user()->id;
        $data['status'] = 'pending';
        $data['employment_position'] ??= 'À préciser';
        $data['verification_purpose'] ??= 'Vérification dans le cadre d’un recrutement';
        $data['candidate_consent'] = $data['candidate_consent'] ?? true;
        $data['requester_name'] = $request->user()->name;
        $data['requester_email'] = $request->user()->email;
        $data['requester_phone'] = $request->user()->company_phone;
        foreach (['candidate_document', 'diploma_document', 'transcript_document', 'authorization_document'] as $file) {
            if ($request->hasFile($file)) $data[str_replace('_document', '_document_path', $file)] = $request->file($file)->store('verification-requests');
        }
        unset($data['candidate_document'], $data['diploma_document'], $data['transcript_document'], $data['authorization_document']);
        return response()->json(VerificationRequest::create($data)->load('diploma'), 201);
    }

    public function search(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403, 'Accès réservé aux administrateurs.');
        $term = $request->string('q')->toString();
        return Diploma::query()->when($term, fn ($q) => $q->where('holder_name', 'like', "%$term%")->orWhere('number', 'like', "%$term%")->orWhere('program', 'like', "%$term%"))->latest()->get();
    }

    public function decide(Request $request, VerificationRequest $verificationRequest)
    {
        abort_unless($request->user()->role === 'admin', 403, 'Accès réservé aux administrateurs.');
        $data = $request->validate(['status' => ['required', 'in:validated,rejected'], 'decision_note' => ['nullable', 'string']]);
        if ($data['status'] === 'validated') {
            $matchingDiploma = Diploma::where('number', $verificationRequest->diploma_number)->first();
            if (! $matchingDiploma) {
                return response()->json(['message' => 'Validation impossible : aucun diplôme ne correspond exactement à ce numéro dans la base IAI.', 'code' => 'DIPLOMA_NUMBER_NOT_FOUND'], 422);
            }
            $verificationRequest->diploma_id = $matchingDiploma->id;
        }
        $verificationRequest->update([...$data, 'diploma_id' => $verificationRequest->diploma_id, 'processed_by' => $request->user()->id, 'processed_at' => now()]);
        return response()->json($verificationRequest->fresh()->load(['diploma', 'processor']));
    }

    public function byQr(string $token)
    {
        $diploma = Diploma::where('qr_token', $token)->firstOrFail();
        return response()->json(['verified' => true, 'diploma' => $diploma]);
    }
}
