<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>{{ $title ?? 'AuthDip · IAI' }}</title>@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
@php($isAdmin = request()->routeIs('admin.*'))
<body class="min-h-screen bg-slate-50 antialiased">
<div class="flex min-h-screen">
<aside class="hidden w-72 shrink-0 bg-auth-ink p-6 text-white lg:block">
<a href="{{ $isAdmin ? route('admin.dashboard') : route('employer.dashboard') }}" class="flex items-center gap-3 text-xl font-bold"><img src="{{ asset('images/logo_IAI.jpeg') }}" alt="Logo IAI" class="h-10 w-10 rounded-[5px] bg-white object-contain p-1"><span>AuthDip <span class="text-xs font-medium text-auth-blue">IAI</span></span></a>
@if($isAdmin)
<p class="mt-12 px-3 text-xs font-semibold uppercase tracking-[.18em] text-slate-300">Administration</p>
<nav class="mt-4 space-y-2"><a href="{{ route('admin.dashboard') }}#overview" data-tab="overview" class="side-link">▦ <span>Tableau de bord</span></a><a href="{{ route('admin.dashboard') }}#requests" data-tab="requests" class="side-link">⌁ <span>Demandes</span></a><a href="{{ route('admin.dashboard') }}#diplomas" data-tab="diplomas" class="side-link">▣ <span>Diplômes</span></a><a href="{{ route('admin.dashboard') }}#reports" data-tab="reports" class="side-link">▤ <span>Rapports</span></a><a href="{{ route('admin.dashboard') }}#employers" data-tab="employers" class="side-link">◉ <span>Entreprises</span></a></nav>
@else
<p class="mt-12 px-3 text-xs font-semibold uppercase tracking-[.18em] text-slate-300">Espace entreprise</p>
<nav class="mt-4 space-y-2"><a href="{{ route('employer.dashboard') }}" class="side-link bg-white/10">▦ <span>Tableau de bord</span></a><a href="{{ route('employer.dashboard') }}#new-request" class="side-link">＋ <span>Nouvelle demande</span></a></nav>
@endif
<div class="mt-[40vh] rounded-[10px] bg-auth-blue/15 p-4 text-sm"><p class="font-semibold">AuthDip · IAI</p><p class="mt-1 text-xs leading-5 text-slate-300">Plateforme sécurisée de vérification des diplômes.</p></div>
</aside>
<main class="min-w-0 flex-1"><header class="flex h-20 items-center justify-between border-b border-slate-200 bg-white px-5 sm:px-10"><div><p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Espace sécurisé</p><h1 class="text-lg font-bold text-auth-ink">AuthDip · {{ $isAdmin ? 'Administration IAI' : 'Espace entreprise' }}</h1></div><div class="flex items-center gap-3"><div class="hidden text-right sm:block"><p class="text-sm font-semibold">{{ ($admin ?? $user ?? null)?->name ?? 'Utilisateur' }}</p><p class="text-xs text-slate-400">{{ $isAdmin ? 'Administrateur IAI' : 'Entreprise vérifiée' }}</p></div><div class="grid h-10 w-10 place-items-center rounded-full bg-auth-blue font-bold text-auth-ink">AI</div><form method="POST" action="{{ $isAdmin ? route('admin.logout') : route('web.logout') }}">@csrf<button class="rounded-[10px] px-3 py-2 text-sm font-semibold text-slate-500 hover:bg-slate-100">Quitter</button></form></div></header><div class="p-5 sm:p-10">@yield('content')</div></main>
</div>
<style>.side-link{display:flex;align-items:center;gap:.75rem;border-radius:10px;padding:.75rem 1rem;font-size:.875rem;color:#e2e8f0}.side-link:hover{background:rgba(255,255,255,.1)}</style>
<script>document.addEventListener('DOMContentLoaded',()=>{const p=document.querySelectorAll('[data-panel]'),t=document.querySelectorAll('[data-tab]');const a=n=>{n=n||'overview';p.forEach(x=>x.classList.toggle('hidden',x.dataset.panel!==n));t.forEach(x=>x.classList.toggle('bg-white/10',x.dataset.tab===n));};t.forEach(x=>x.addEventListener('click',e=>{e.preventDefault();a(x.dataset.tab);history.replaceState(null,'','#'+x.dataset.tab);}));if(p.length)a(location.hash.replace('#','')||'overview');});</script>
</body></html>
