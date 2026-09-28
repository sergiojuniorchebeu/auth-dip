<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'company_legal_name' => ['nullable', 'string', 'max:180'],
            'company_registration_number' => ['nullable', 'string', 'max:100'],
            'company_tax_number' => ['nullable', 'string', 'max:100'],
            'company_sector' => ['nullable', 'string', 'max:150'],
            'company_phone' => ['nullable', 'string', 'max:40'],
            'company_address' => ['nullable', 'string', 'max:255'],
            'company_city' => ['nullable', 'string', 'max:100'],
            'company_country' => ['nullable', 'string', 'max:100'],
            'contact_position' => ['nullable', 'string', 'max:120'],
            'registration_note' => ['nullable', 'string', 'max:1000'],
            'company_documents.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);
        $isCompanyRegistration = filled($data['company_legal_name'] ?? null);
        if ($isCompanyRegistration && ! $request->hasFile('company_documents')) {
            throw ValidationException::withMessages(['company_documents' => 'Au moins une pièce justificative de l’entreprise est obligatoire.']);
        }
        $userData = collect($data)->except(['company_documents'])->all();
        $userData['role'] = 'employer';
        $userData['registration_status'] = $isCompanyRegistration ? 'pending' : 'approved';
        $user = DB::transaction(function () use ($request, $userData) {
            $created = User::create($userData);
            foreach ($request->file('company_documents', []) as $document) {
                $created->companyDocuments()->create([
                    'document_type' => 'document_entreprise',
                    'path' => $document->store('company-documents'),
                    'original_name' => $document->getClientOriginalName(),
                ]);
            }
            return $created;
        });
        if ($isCompanyRegistration) {
            return response()->json(['message' => 'Votre dossier entreprise a été transmis. Il sera accessible après validation par l’IAI.', 'user' => $user], 201);
        }
        return response()->json(['token' => $user->createToken('authdip')->plainTextToken, 'user' => $user], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $user = User::where('email', $data['email'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Identifiants invalides.'], 422);
        }
        if ($user->role === 'employer' && $user->registration_status !== 'approved') {
            return response()->json(['message' => 'Votre inscription entreprise est encore en attente de validation par l’IAI.', 'status' => $user->registration_status], 403);
        }
        return response()->json(['token' => $user->createToken('authdip')->plainTextToken, 'user' => $user]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();
        return response()->json(['message' => 'Session fermée.']);
    }
}
