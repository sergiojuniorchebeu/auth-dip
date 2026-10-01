<?php

namespace App\Http\Controllers;

use App\Models\Diploma;
use App\Models\User;
use App\Models\VerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminWebController extends Controller
{
    public function webLoginForm()
    {
        return view('auth.login');
    }

    public function webLogin(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $user = User::where('email', $data['email'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return back()->withErrors(['email' => 'Adresse e-mail ou mot de passe incorrect.'])->withInput();
        }
        if ($user->role === 'employer' && $user->registration_status !== 'approved') {
            return back()->withErrors(['email' => 'Votre inscription entreprise est en attente de validation par l’IAI.'])->withInput();
        }
        $request->session()->regenerate();
        $request->session()->put('web_user_id', $user->id);
        $request->session()->put('web_role', $user->role);
        if ($user->role === 'admin') {
            $request->session()->put('admin_id', $user->id);
            return redirect()->route('admin.dashboard');
        }
        $request->session()->put('employer_id', $user->id);
        return redirect()->route('employer.dashboard');
    }

    public function employerDashboard(Request $request)
    {
        $user = User::whereKey($request->session()->get('employer_id'))->where('role', 'employer')->firstOrFail();
        return view('employer.dashboard', ['user' => $user, 'requests' => VerificationRequest::where('employer_id', $user->id)->latest()->get()]);
    }

    public function storeWebRequest(Request $request)
    {
        $user = User::whereKey($request->session()->get('employer_id'))->where('role', 'employer')->where('registration_status', 'approved')->firstOrFail();
        $data = $request->validate([
            'holder_name' => ['required', 'string', 'max:150'], 'candidate_birth_date' => ['nullable', 'date'], 'candidate_birth_place' => ['nullable', 'string', 'max:150'], 'candidate_id_number' => ['nullable', 'string', 'max:100'], 'candidate_email' => ['nullable', 'email'], 'candidate_phone' => ['nullable', 'string', 'max:40'],
            'diploma_number' => ['required', 'string', 'max:100'], 'diploma_type' => ['required', 'string', 'max:150'], 'specialty' => ['required', 'string', 'max:150'], 'declared_average' => ['nullable', 'numeric', 'min:0', 'max:20'], 'graduation_year' => ['required', 'integer', 'min:1950', 'max:2100'], 'program' => ['nullable', 'string', 'max:150'], 'employment_position' => ['required', 'string', 'max:150'], 'employment_reference' => ['nullable', 'string', 'max:100'], 'verification_purpose' => ['required', 'string', 'max:1000'], 'candidate_consent' => ['accepted'],
            'candidate_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], 'diploma_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], 'transcript_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], 'authorization_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);
        $data['reference'] = 'REQ-'.now()->format('Y').'-'.str_pad((string) (VerificationRequest::count() + 1), 4, '0', STR_PAD_LEFT);
        $data['diploma_id'] = Diploma::where('number', $data['diploma_number'])->value('id');
        $data['diploma_type'] ??= $data['program'];
        $data['specialty'] ??= $data['program'];
        $data['program'] ??= $data['specialty'];
        $data['employer_id'] = $user->id; $data['requester_name'] = $user->name; $data['requester_email'] = $user->email; $data['requester_phone'] = $user->company_phone; $data['status'] = 'pending';
        foreach (['candidate_document', 'diploma_document', 'transcript_document', 'authorization_document'] as $file) {
            if ($request->hasFile($file)) $data[str_replace('_document', '_document_path', $file)] = $request->file($file)->store('verification-requests');
        }
        unset($data['candidate_document'], $data['diploma_document'], $data['transcript_document'], $data['authorization_document']);
        VerificationRequest::create($data);
        return back()->with('success', 'La demande détaillée a été enregistrée et placée en attente.');
    }

    public function webLogout(Request $request)
    {
        $request->session()->forget(['web_user_id', 'admin_id', 'employer_id']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('landing');
    }

    private function guard(Request $request): void
    {
        abort_unless($request->session()->get('admin_id'), 403);
        abort_unless(User::whereKey($request->session()->get('admin_id'))->where('role', 'admin')->exists(), 403);
    }

    public function loginForm(Request $request)
    {
        if ($request->session()->get('admin_id')) return redirect()->route('admin.dashboard');
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $admin = User::where('email', $data['email'])->where('role', 'admin')->first();
        if (! $admin || ! Hash::check($data['password'], $admin->password)) {
            return back()->withErrors(['email' => 'Identifiants administrateur invalides.'])->withInput();
        }
        $request->session()->regenerate();
        $request->session()->put('admin_id', $admin->id);
        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('admin_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }

    public function dashboard(Request $request)
    {
        $this->guard($request);
        return view('admin.dashboard', [
            'admin' => User::find($request->session()->get('admin_id')),
            'diplomas' => Diploma::count(),
            'diplomaRecords' => Diploma::latest()->limit(20)->get(),
            'totalRequests' => VerificationRequest::count(),
            'pendingRequests' => VerificationRequest::where('status', 'pending')->count(),
            'validatedRequests' => VerificationRequest::where('status', 'validated')->count(),
            'requests' => VerificationRequest::with('employer')->latest()->limit(12)->get(),
            'pendingEmployers' => User::where('role', 'employer')->where('registration_status', 'pending')->with('companyDocuments')->latest()->get(),
            'monthlyRequests' => collect(range(5, 0))->map(function (int $monthsAgo) {
                $date = now()->subMonths($monthsAgo);
                return ['label' => $date->format('M'), 'value' => VerificationRequest::whereBetween('created_at', [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()])->count()];
            })->values(),
            'statusDistribution' => collect(['pending' => 'En attente', 'validated' => 'Validées', 'rejected' => 'Rejetées'])->mapWithKeys(fn ($label, $status) => [$status => ['label' => $label, 'value' => VerificationRequest::where('status', $status)->count()]]),
        ]);
    }

    public function showRequest(Request $request, VerificationRequest $verificationRequest)
    {
        $this->guard($request);
        return view('admin.request-show', [
            'verificationRequest' => $verificationRequest->load(['employer', 'diploma', 'processor']),
        ]);
    }

    public function registerForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'company_legal_name' => ['required', 'string', 'max:180'],
            'company_registration_number' => ['required', 'string', 'max:100'],
            'company_tax_number' => ['required', 'string', 'max:100'],
            'company_sector' => ['required', 'string', 'max:150'],
            'company_phone' => ['required', 'string', 'max:40'],
            'company_address' => ['required', 'string', 'max:255'],
            'company_city' => ['required', 'string', 'max:100'],
            'company_country' => ['required', 'string', 'max:100'],
            'contact_position' => ['required', 'string', 'max:120'],
            'company_documents' => ['required', 'array', 'min:1'],
            'company_documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);
        $documents = $request->file('company_documents', []);
        $user = DB::transaction(function () use ($data, $documents) {
            $user = User::create([...collect($data)->except(['company_documents'])->all(), 'role' => 'employer', 'registration_status' => 'pending']);
            foreach ($documents as $document) {
                $user->companyDocuments()->create(['document_type' => 'document_entreprise', 'path' => $document->store('company-documents'), 'original_name' => $document->getClientOriginalName()]);
            }
            return $user;
        });
        return redirect()->route('web.login')->with('success', 'Votre dossier a été transmis. L’IAI validera votre compte après contrôle des pièces.');
    }

    public function decideEmployer(Request $request, User $user)
    {
        $this->guard($request);
        abort_unless($user->role === 'employer', 404);
        $data = $request->validate(['registration_status' => ['required', 'in:approved,rejected'], 'registration_note' => ['nullable', 'string', 'max:1000']]);
        $user->update([...$data, 'approved_by' => $data['registration_status'] === 'approved' ? $request->session()->get('admin_id') : null, 'approved_at' => $data['registration_status'] === 'approved' ? now() : null]);
        return back()->with('success', 'Le dossier entreprise a été mis à jour.');
    }

    public function downloadRequestDocument(Request $request, VerificationRequest $verificationRequest, string $document)
    {
        $this->guard($request);
        abort_unless(in_array($document, ['candidate', 'diploma', 'transcript', 'authorization'], true), 404);
        $path = $verificationRequest->{$document.'_document_path'};
        abort_unless($path && Storage::exists($path), 404);
        return Storage::download($path);
    }

    public function downloadDiplomaDocument(Request $request, Diploma $diploma, string $document)
    {
        $this->guard($request);
        abort_unless(in_array($document, ['diploma', 'transcript'], true), 404);
        $path = $diploma->{$document.'_path'};
        abort_unless($path && Storage::exists($path), 404);
        return Storage::download($path);
    }

    public function decide(Request $request, VerificationRequest $verificationRequest)
    {
        $this->guard($request);
        $data = $request->validate(['status' => ['required', 'in:validated,rejected'], 'decision_note' => ['nullable', 'string', 'max:1000']]);
        if ($data['status'] === 'validated') {
            $matchingDiploma = Diploma::where('number', $verificationRequest->diploma_number)->first();
            if (! $matchingDiploma) {
                return back()->withErrors(['decision' => 'Validation impossible : aucun diplôme ne correspond exactement au numéro fourni dans la base IAI.'])->withFragment('requests');
            }
            $verificationRequest->diploma_id = $matchingDiploma->id;
        }
        $verificationRequest->update([...$data, 'diploma_id' => $verificationRequest->diploma_id, 'processed_by' => $request->session()->get('admin_id'), 'processed_at' => now()]);
        return back()->with('success', 'La demande '.$verificationRequest->reference.' a été traitée.');
    }

    public function storeDiploma(Request $request)
    {
        $this->guard($request);
        $data = $request->validate(['number' => ['required', 'string', 'max:100', 'unique:diplomas,number'], 'holder_name' => ['required', 'string', 'max:150'], 'program' => ['required', 'string', 'max:150'], 'diploma_type' => ['required', 'string', 'max:150'], 'average' => ['nullable', 'numeric', 'min:0', 'max:20'], 'graduation_year' => ['required', 'integer', 'min:1950', 'max:2100'], 'diploma_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], 'transcript_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120']]);
        foreach (['diploma_document', 'transcript_document'] as $file) {
            if ($request->hasFile($file)) $data[str_replace('_document', '_path', $file)] = $request->file($file)->store('diplomas');
        }
        unset($data['diploma_document'], $data['transcript_document']);
        Diploma::create([...$data, 'qr_token' => Str::uuid()->toString()]);
        return back()->with('success', 'Le diplôme a été ajouté à la base centralisée.');
    }

    public function verify(string $token)
    {
        $diploma = Diploma::where('qr_token', $token)->first();
        return view('verify', ['diploma' => $diploma]);
    }
}
