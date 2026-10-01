@extends('layouts.admin')
@section('content')
<div class="mx-auto max-w-6xl">
    <a href="{{ route('admin.dashboard') }}#diplomas" class="text-sm font-semibold text-auth-ink">← Retour au référentiel</a>

    <div class="mt-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm text-slate-500">Fiche officielle du diplôme</p>
            <h2 class="mt-1 text-3xl font-bold text-auth-ink">{{ $diploma->holder_name }}</h2>
            <p class="mt-2 text-sm text-slate-500">{{ $diploma->number }}</p>
        </div>
        <a href="{{ route('public.verify', $diploma->qr_token) }}" target="_blank" class="rounded-[5px] bg-auth-blue px-4 py-3 text-sm font-bold text-auth-ink">Voir la vérification publique</a>
    </div>

    @if(session('success'))<div class="mt-5 rounded-[10px] bg-auth-mint px-4 py-3 text-sm font-semibold text-auth-ink">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="mt-5 rounded-[10px] bg-auth-pink px-4 py-3 text-sm text-auth-ink">{{ $errors->first() }}</div>@endif

    <div class="mt-8 grid gap-6 lg:grid-cols-[1fr_280px]">
        <section class="rounded-[10px] bg-white p-6 shadow-sm">
            <h3 class="font-bold text-auth-ink">Informations académiques</h3>
            <dl class="mt-5 grid gap-5 sm:grid-cols-2">
                @foreach([
                    ['Titulaire', $diploma->holder_name],
                    ['Numéro officiel', $diploma->number],
                    ['Type de diplôme', $diploma->diploma_type ?: 'Non renseigné'],
                    ['Filière', $diploma->program ?: $diploma->specialty ?: 'Non renseignée'],
                    ['Niveau', $diploma->study_level ?: 'Non renseigné'],
                    ['Année d’étude', $diploma->study_year ?: 'Non renseignée'],
                    ['Année d’obtention', $diploma->graduation_year],
                    ['Moyenne', $diploma->average !== null ? $diploma->average.'/20' : 'Non renseignée'],
                    ['Mention', $diploma->mention ?: 'Non renseignée'],
                    ['Établissement', $diploma->institution ?: 'IAI'],
                    ['Date de délivrance', $diploma->issue_date?->format('d/m/Y') ?: 'Non renseignée'],
                    ['Statut', $diploma->status ?: 'Actif'],
                ] as [$label, $value])
                    <div><dt class="text-xs text-slate-400">{{ $label }}</dt><dd class="mt-1 text-sm font-semibold text-auth-ink">{{ $value }}</dd></div>
                @endforeach
            </dl>
        </section>

        <section class="rounded-[10px] bg-white p-6 text-center shadow-sm">
            <h3 class="font-bold text-auth-ink">QR de vérification</h3>
            <div class="mt-5 flex justify-center bg-white">{!! QrCode::size(190)->margin(1)->generate(route('public.verify', $diploma->qr_token)) !!}</div>
            <p class="mt-4 break-all text-xs text-slate-400">{{ route('public.verify', $diploma->qr_token) }}</p>
        </section>
    </div>

    <section class="mt-6 rounded-[10px] bg-white p-6 shadow-sm">
        <h3 class="font-bold text-auth-ink">Pièces justificatives sécurisées</h3>
        <div class="mt-5 grid gap-3 sm:grid-cols-2">
            @foreach([['diploma', 'Copie du diplôme', $diploma->document_path], ['transcript', 'Relevé de notes', $diploma->transcript_path]] as [$key, $label, $path])
                <div class="flex items-center justify-between rounded-[10px] bg-slate-50 px-4 py-3">
                    <span class="text-sm font-semibold text-auth-ink">{{ $label }}</span>
                    @if($path)<a href="{{ route('admin.diplomas.document', [$diploma, $key]) }}" class="rounded-[5px] bg-auth-blue px-3 py-2 text-xs font-bold text-auth-ink">Télécharger</a>@else<span class="text-xs text-slate-400">Non déposée</span>@endif
                </div>
            @endforeach
        </div>
    </section>

    @if($diploma->corrected_at || $diploma->correction_note)
        <section class="mt-6 rounded-[10px] border border-auth-blue/50 bg-white p-6 shadow-sm">
            <h3 class="font-bold text-auth-ink">Traçabilité de la correction</h3>
            <p class="mt-3 text-sm text-slate-600">{{ $diploma->correction_note ?: 'Aucune note de correction.' }}</p>
            @if($diploma->corrected_at)<p class="mt-2 text-xs text-slate-400">Corrigé le {{ $diploma->corrected_at->format('d/m/Y à H:i') }}.</p>@endif
        </section>
    @endif

    <section class="mt-6 rounded-[10px] bg-white p-6 shadow-sm">
        <h3 class="font-bold text-auth-ink">Demandes liées à ce diplôme</h3>
        <div class="mt-5 overflow-x-auto">
            <table class="w-full min-w-[680px] text-left text-sm">
                <thead class="border-b border-slate-100 text-xs uppercase tracking-wider text-slate-400"><tr><th class="pb-3">Référence</th><th class="pb-3">Entreprise</th><th class="pb-3">Statut</th><th class="pb-3">Détails</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($verificationRequests as $verificationRequest)
                        <tr><td class="py-3 font-semibold text-auth-ink">{{ $verificationRequest->reference }}</td><td class="py-3 text-slate-600">{{ $verificationRequest->employer?->company_legal_name ?: $verificationRequest->employer?->name ?: '—' }}</td><td class="py-3 text-slate-600">{{ $verificationRequest->status === 'validated' ? 'Validée' : ($verificationRequest->status === 'rejected' ? 'Rejetée' : 'En attente') }}</td><td class="py-3"><a href="{{ route('admin.requests.show', $verificationRequest) }}" class="rounded-[5px] bg-auth-blue px-3 py-2 text-xs font-bold text-auth-ink">Voir</a></td></tr>
                    @empty
                        <tr><td colspan="4" class="py-8 text-center text-sm text-slate-400">Aucune demande liée à ce diplôme.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if($otherDiplomas->isNotEmpty())
        <section class="mt-6 rounded-[10px] bg-white p-6 shadow-sm">
            <h3 class="font-bold text-auth-ink">Autres diplômes du même étudiant</h3>
            <p class="mt-1 text-sm text-slate-500">Chaque diplôme du cursus possède sa propre fiche et son propre numéro.</p>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">@foreach($otherDiplomas as $other)<a href="{{ route('admin.diplomas.show', $other) }}" class="rounded-[10px] border border-slate-100 p-4 hover:border-auth-blue"><p class="font-semibold text-auth-ink">{{ $other->diploma_type ?: $other->program }}</p><p class="text-sm text-slate-500">{{ $other->number }} · {{ $other->graduation_year }}</p></a>@endforeach</div>
        </section>
    @endif
</div>
@endsection
