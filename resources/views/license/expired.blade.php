<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Licence Expirée — Groupe Bama GED</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        * { font-family: 'Plus Jakarta Sans', -apple-system, sans-serif; }
        @keyframes fadeUp  { from{opacity:0;transform:translateY(24px)} to{opacity:1;transform:translateY(0)} }
        @keyframes fadeIn  { from{opacity:0} to{opacity:1} }
        @keyframes pulse-ring { 0%{transform:scale(1);opacity:.5} 100%{transform:scale(1.6);opacity:0} }
        @keyframes shimmer { 0%{background-position:-200% 0} 100%{background-position:200% 0} }
        .fade-up { animation: fadeUp .6s cubic-bezier(.22,1,.36,1) forwards; }
        .fade-in { animation: fadeIn .5s ease forwards; }
        .d1{animation-delay:.05s;opacity:0} .d2{animation-delay:.13s;opacity:0}
        .d3{animation-delay:.21s;opacity:0} .d4{animation-delay:.29s;opacity:0}
        .d5{animation-delay:.37s;opacity:0} .d6{animation-delay:.45s;opacity:0}
        .d7{animation-delay:.53s;opacity:0}
        .pulse-ring::after {
            content:''; position:absolute; inset:-8px; border-radius:inherit;
            border:2px solid rgba(239,68,68,.4);
            animation: pulse-ring 2s ease-out infinite;
        }
        .input-field {
            width:100%; background:#f8fafc; border:1.5px solid #e2e8f0;
            border-radius:12px; padding:11px 14px 11px 40px;
            font-size:13px; font-weight:600; color:#1e293b;
            transition:all .2s; outline:none;
        }
        .input-field::placeholder { color:#94a3b8; font-weight:500; }
        .input-field:focus { background:#fff; border-color:#f97316; box-shadow:0 0 0 4px rgba(249,115,22,.1); }
        .input-field.error { background:#fff5f5; border-color:#fca5a5; }
        textarea.input-field { padding-left:14px; resize:none; }
        .tab-btn { transition:all .2s; }
        .tab-btn.active { background:#fff; box-shadow:0 1px 4px rgba(0,0,0,.08); color:#ea580c; }
        .contact-card { transition:transform .2s, box-shadow .2s; }
        .contact-card:hover { transform:translateY(-2px); box-shadow:0 8px 24px -4px rgba(0,0,0,.1); }
        .shimmer-bar {
            background: linear-gradient(90deg,#fee2e2 25%,#fecaca 50%,#fee2e2 75%);
            background-size:200% 100%;
            animation: shimmer 2s infinite;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 via-orange-50/30 to-slate-100 antialiased"
      x-data="licenseApp()">

{{-- ===== LAYOUT PRINCIPAL ===== --}}
<div class="min-h-screen flex flex-col lg:flex-row">

    {{-- ===== PANNEAU GAUCHE — STATUT LICENCE ===== --}}
    <div class="lg:w-[42%] xl:w-[38%] bg-gradient-to-b from-red-600 via-red-700 to-red-800 relative flex flex-col justify-between p-10 xl:p-14 overflow-hidden">

        {{-- Grille SVG décorative --}}
        <svg class="absolute inset-0 w-full h-full opacity-[0.06]" xmlns="http://www.w3.org/2000/svg">
            <defs><pattern id="g" width="48" height="48" patternUnits="userSpaceOnUse">
                <path d="M 48 0 L 0 0 0 48" fill="none" stroke="white" stroke-width="1"/>
            </pattern></defs>
            <rect width="100%" height="100%" fill="url(#g)"/>
        </svg>
        <div class="absolute -top-24 -left-24 w-72 h-72 bg-white/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -right-24 w-80 h-80 bg-red-900/30 rounded-full blur-3xl pointer-events-none"></div>

        {{-- Contenu gauche --}}
        <div class="relative z-10">
            {{-- Logo Groupe Bama --}}
            <div class="flex items-center gap-3 mb-12 fade-up d1">
                <div class="w-10 h-10 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center border border-white/30">
                    <i class="fa-solid fa-file-shield text-white text-base"></i>
                </div>
                <div>
                    <p class="text-white font-black text-sm leading-none tracking-tight">Groupe Bama</p>
                    <p class="text-red-200 text-[9px] font-bold uppercase tracking-widest mt-0.5">GED Platform</p>
                </div>
            </div>

            {{-- Icône expiration --}}
            <div class="relative inline-block mb-8 fade-up d2">
                <div class="pulse-ring relative w-20 h-20 bg-white/15 backdrop-blur rounded-[1.75rem] flex items-center justify-center border border-white/20">
                    <i class="fa-solid fa-shield-xmark text-white text-4xl"></i>
                </div>
            </div>

            <div class="fade-up d2">
                <p class="text-[10px] font-black text-red-300 uppercase tracking-[0.3em] mb-2">Accès suspendu</p>
                <h1 class="text-3xl xl:text-4xl font-black text-white leading-tight tracking-tight mb-4">
                    Licence<br><span class="text-red-200">Expirée</span>
                </h1>
                <p class="text-red-100/80 text-sm font-medium leading-relaxed max-w-xs">
                    La licence de la plateforme GED Groupe Bama a expiré. Contactez Imperis SARL pour le renouvellement.
                </p>
            </div>

            {{-- Détails licence --}}
            @if($license)
            <div class="mt-8 fade-up d3 space-y-3">
                <div class="shimmer-bar rounded-xl p-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <p class="text-[9px] font-black text-red-500 uppercase tracking-widest mb-0.5">Expirée le</p>
                            <p class="text-sm font-black text-red-800">{{ $license->expires_at->format('d/m/Y') }}</p>
                        </div>
                        <div>
                            <p class="text-[9px] font-black text-red-500 uppercase tracking-widest mb-0.5">Depuis</p>
                            <p class="text-sm font-black text-red-800">{{ abs($license->daysRemaining()) }} jours</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white/10 border border-white/15 rounded-xl p-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <p class="text-[9px] font-black text-red-300 uppercase tracking-widest mb-0.5">Client</p>
                            <p class="text-xs font-bold text-white">{{ $license->licensed_to }}</p>
                        </div>
                        <div>
                            <p class="text-[9px] font-black text-red-300 uppercase tracking-widest mb-0.5">Émise par</p>
                            <p class="text-xs font-bold text-white">{{ $license->issued_by }}</p>
                        </div>
                    </div>
                </div>
            </div>
            @else
            <div class="mt-8 fade-up d3 bg-white/10 border border-white/15 rounded-xl p-4">
                <p class="text-xs font-bold text-red-200">
                    <i class="fa-solid fa-circle-info mr-2"></i>
                    Aucune licence enregistrée dans le système.
                </p>
            </div>
            @endif
        </div>

        {{-- Footer gauche --}}
        <div class="relative z-10 fade-up d4 mt-8">
            <div class="flex items-center gap-2 bg-white/10 border border-white/20 rounded-xl px-3 py-2 w-fit">
                <div class="w-1.5 h-1.5 rounded-full bg-red-300 animate-pulse"></div>
                <span class="text-white/70 text-[9px] font-bold uppercase tracking-wider">Système suspendu</span>
            </div>
            <p class="text-white/25 text-[9px] font-black tracking-[0.2em] uppercase mt-3">
                © {{ date('Y') }} Groupe Bama
            </p>
        </div>
    </div>


    {{-- ===== PANNEAU DROIT — ACTIONS ===== --}}
    <div class="flex-1 flex items-start justify-center px-6 py-10 sm:px-10 lg:px-12 xl:px-16 overflow-y-auto">
        <div class="w-full max-w-[480px] pt-4">

            {{-- Titre panneau droit --}}
            <div class="fade-up d3 mb-7">
                <p class="text-[10px] font-black text-orange-600 uppercase tracking-[0.3em] mb-1">Que souhaitez-vous faire ?</p>
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">Renouveler l'accès</h2>
                <p class="text-xs text-slate-400 font-medium mt-1.5 leading-relaxed">
                    Choisissez une option ci-dessous pour rétablir l'accès à votre plateforme GED.
                </p>
            </div>

            {{-- Onglets --}}
            <div class="fade-up d4 bg-slate-100 rounded-2xl p-1 flex gap-1 mb-7">
                <button @click="tab='contact'"
                        :class="tab==='contact' ? 'active' : 'text-slate-500 hover:text-slate-700'"
                        class="tab-btn flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl text-xs font-bold">
                    <i class="fa-solid fa-headset text-[11px]"></i>
                    Contacter Imperis
                </button>
                <button @click="tab='request'"
                        :class="tab==='request' ? 'active' : 'text-slate-500 hover:text-slate-700'"
                        class="tab-btn flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl text-xs font-bold">
                    <i class="fa-solid fa-paper-plane text-[11px]"></i>
                    Demande de renouvellement
                </button>
                <button @click="tab='activate'"
                        :class="tab==='activate' ? 'active' : 'text-slate-500 hover:text-slate-700'"
                        class="tab-btn flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl text-xs font-bold">
                    <i class="fa-solid fa-key text-[11px]"></i>
                    Activer une clé
                </button>
            </div>


            {{-- ===== ONGLET 1 : CONTACTER IMPERIS ===== --}}
            <div x-show="tab==='contact'" x-transition:enter="fade-in" class="space-y-4">

                {{-- Bannière info --}}
                <div class="fade-up d5 bg-orange-50 border border-orange-100 rounded-2xl p-4 flex items-start gap-3">
                    <div class="w-8 h-8 rounded-xl bg-orange-100 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-circle-info text-orange-600 text-xs"></i>
                    </div>
                    <p class="text-[11px] text-orange-800 font-medium leading-relaxed">
                        <strong>Seul Imperis SARL</strong> est habilité à émettre et renouveler les licences de la plateforme GED Groupe Bama.
                    </p>
                </div>

                {{-- Carte téléphone --}}
                <a href="tel:+22366756642"
                   class="contact-card fade-up d5 flex items-center gap-4 bg-white border border-slate-100 rounded-2xl p-5 shadow-sm hover:border-orange-200 group">
                    <div class="w-12 h-12 rounded-2xl bg-green-50 border border-green-100 flex items-center justify-center shrink-0 group-hover:bg-green-100 transition-colors">
                        <i class="fa-solid fa-phone text-green-600 text-base"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Téléphone / WhatsApp</p>
                        <p class="text-sm font-black text-slate-900">+223 66 75 66 42</p>
                        <p class="text-[10px] text-slate-400 font-medium">Disponible du lundi au vendredi</p>
                    </div>
                    <i class="fa-solid fa-arrow-up-right-from-square text-slate-300 text-xs group-hover:text-orange-500 transition-colors"></i>
                </a>

                {{-- Carte email --}}
                <a href="mailto:contact@imperis.com?subject=Renouvellement%20Licence%20GED%20Groupe%20Bama&body=Bonjour%20Imperis%20SARL%2C%0A%0ANous%20souhaitons%20renouveler%20la%20licence%20de%20notre%20plateforme%20GED.%0A%0AClient%20%3A%20Groupe%20Bama%0ADate%20d%27expiration%20%3A%20{{ $license ? urlencode($license->expires_at->format('d/m/Y')) : 'N/A' }}%0A%0AMerci%20de%20nous%20contacter.%0A%0ACordialement%2C%0AGroupe%20Bama"
                   class="contact-card fade-up d5 flex items-center gap-4 bg-white border border-slate-100 rounded-2xl p-5 shadow-sm hover:border-orange-200 group">
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 border border-blue-100 flex items-center justify-center shrink-0 group-hover:bg-blue-100 transition-colors">
                        <i class="fa-solid fa-envelope text-blue-600 text-base"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Email</p>
                        <p class="text-sm font-black text-slate-900">contact@imperis.com</p>
                        <p class="text-[10px] text-slate-400 font-medium">Réponse sous 24h ouvrées</p>
                    </div>
                    <i class="fa-solid fa-arrow-up-right-from-square text-slate-300 text-xs group-hover:text-orange-500 transition-colors"></i>
                </a>

                {{-- Carte adresse --}}
                <div class="contact-card fade-up d6 flex items-center gap-4 bg-white border border-slate-100 rounded-2xl p-5 shadow-sm">
                    <div class="w-12 h-12 rounded-2xl bg-purple-50 border border-purple-100 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-location-dot text-purple-600 text-base"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Adresse</p>
                        <p class="text-sm font-black text-slate-900">Hamdalaye ACI</p>
                        <p class="text-[10px] text-slate-400 font-medium">Bamako, Mali</p>
                    </div>
                </div>

                {{-- Carte identité Imperis --}}
                <div class="fade-up d7 bg-gradient-to-r from-slate-900 to-slate-800 rounded-2xl p-5 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-orange-500 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-building text-white text-base"></i>
                    </div>
                    <div>
                        <p class="text-white font-black text-sm leading-none mb-1">Imperis SARL</p>
                        <p class="text-slate-400 text-[10px] font-medium">Éditeur officiel de la solution GED Groupe Bama</p>
                        <p class="text-orange-400 text-[9px] font-bold uppercase tracking-wider mt-1">Bamako, Mali · {{ date('Y') }}</p>
                    </div>
                </div>
            </div>


            {{-- ===== ONGLET 2 : DEMANDE DE RENOUVELLEMENT ===== --}}
            <div x-show="tab==='request'" x-transition:enter="fade-in" class="space-y-5">

                {{-- Succès envoi --}}
                <div x-show="sent" x-transition
                     class="bg-green-50 border border-green-100 rounded-2xl p-5 text-center">
                    <div class="w-12 h-12 rounded-2xl bg-green-100 flex items-center justify-center mx-auto mb-3">
                        <i class="fa-solid fa-check text-green-600 text-lg"></i>
                    </div>
                    <p class="text-sm font-black text-green-800 mb-1">Demande envoyée !</p>
                    <p class="text-[11px] text-green-700 font-medium leading-relaxed">
                        Votre demande a été transmise à Imperis SARL. Vous serez contacté dans les plus brefs délais.
                    </p>
                    <button @click="sent=false; tab='contact'"
                            class="mt-4 text-[11px] font-bold text-green-700 hover:underline">
                        <i class="fa-solid fa-arrow-left mr-1"></i> Voir les contacts
                    </button>
                </div>

                {{-- Formulaire --}}
                <form x-show="!sent" @submit.prevent="submitRequest()" class="space-y-4">

                    <div class="bg-orange-50 border border-orange-100 rounded-xl p-3.5 flex items-start gap-2.5">
                        <i class="fa-solid fa-circle-info text-orange-500 text-xs mt-0.5 shrink-0"></i>
                        <p class="text-[10px] text-orange-800 font-medium leading-relaxed">
                            Ce formulaire génère un email pré-rempli à destination d'Imperis SARL pour demander le renouvellement de votre licence.
                        </p>
                    </div>

                    {{-- Nom du responsable --}}
                    <div>
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">
                            Nom du responsable <span class="text-red-400">*</span>
                        </label>
                        <div class="relative">
                            <i class="fa-solid fa-user absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-300 text-xs pointer-events-none"></i>
                            <input type="text" x-model="form.name" required placeholder="Ex : Mamadou Traoré"
                                   class="input-field">
                        </div>
                    </div>

                    {{-- Poste --}}
                    <div>
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">
                            Poste / Fonction <span class="text-red-400">*</span>
                        </label>
                        <div class="relative">
                            <i class="fa-solid fa-briefcase absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-300 text-xs pointer-events-none"></i>
                            <input type="text" x-model="form.role" required placeholder="Ex : Directeur Général"
                                   class="input-field">
                        </div>
                    </div>

                    {{-- Téléphone --}}
                    <div>
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">
                            Téléphone de contact <span class="text-red-400">*</span>
                        </label>
                        <div class="relative">
                            <i class="fa-solid fa-phone absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-300 text-xs pointer-events-none"></i>
                            <input type="tel" x-model="form.phone" required placeholder="+223 XX XX XX XX"
                                   class="input-field">
                        </div>
                    </div>

                    {{-- Message --}}
                    <div>
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">
                            Message complémentaire
                        </label>
                        <textarea x-model="form.message" rows="3" placeholder="Informations supplémentaires..."
                                  class="input-field"></textarea>
                    </div>

                    {{-- Bouton --}}
                    <button type="submit" :disabled="loading"
                            class="w-full inline-flex items-center justify-center gap-2 bg-gradient-to-r from-orange-600 to-orange-500 text-white px-6 py-3.5 rounded-xl font-bold text-sm shadow-lg shadow-orange-200 hover:shadow-xl transition-all hover:-translate-y-0.5 disabled:opacity-70 disabled:cursor-not-allowed">
                        <span x-show="!loading">
                            <i class="fa-solid fa-paper-plane text-xs mr-1"></i>
                            Envoyer la demande par email
                        </span>
                        <span x-show="loading" class="flex items-center gap-2">
                            <i class="fa-solid fa-spinner fa-spin text-xs"></i>
                            Ouverture du client mail...
                        </span>
                    </button>
                    <p class="text-[9px] text-slate-400 text-center font-medium">
                        Ouvre votre client email avec un message pré-rempli pour Imperis SARL
                    </p>
                </form>
            </div>


            {{-- ===== ONGLET 3 : ACTIVER UNE CLÉ ===== --}}
            <div x-show="tab==='activate'" x-transition:enter="fade-in">
                <form action="{{ route('license.activate.submit') }}" method="POST" class="space-y-5"
                      x-data="{ loading: false }" @submit="loading = true">
                    @csrf

                    @if($errors->any())
                    <div class="flex items-start gap-3 bg-red-50 border border-red-100 rounded-2xl px-4 py-3.5">
                        <div class="w-7 h-7 rounded-lg bg-red-100 flex items-center justify-center shrink-0 mt-0.5">
                            <i class="fa-solid fa-triangle-exclamation text-red-500 text-xs"></i>
                        </div>
                        <div>
                            @foreach($errors->all() as $error)
                            <p class="text-xs font-bold text-red-700 leading-relaxed">{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if(session('success'))
                    <div class="flex items-start gap-3 bg-green-50 border border-green-100 rounded-2xl px-4 py-3.5">
                        <div class="w-7 h-7 rounded-lg bg-green-100 flex items-center justify-center shrink-0 mt-0.5">
                            <i class="fa-solid fa-check text-green-600 text-xs"></i>
                        </div>
                        <p class="text-xs font-bold text-green-700 leading-relaxed">{{ session('success') }}</p>
                    </div>
                    @endif

                    <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4 flex items-start gap-3">
                        <div class="w-8 h-8 rounded-xl bg-orange-100 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-key text-orange-600 text-xs"></i>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-700 mb-0.5">Vous avez reçu une clé de licence ?</p>
                            <p class="text-[10px] text-slate-500 font-medium leading-relaxed">
                                Entrez la clé fournie par Imperis SARL pour réactiver immédiatement l'accès à la plateforme.
                            </p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">
                            Clé de licence <span class="text-red-400">*</span>
                        </label>
                        <div class="relative">
                            <i class="fa-solid fa-key absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-300 text-xs pointer-events-none"></i>
                            <input type="text" name="license_key" required
                                   placeholder="IMPERIS-XXXX-XXXX-XXXX-EXP20270601"
                                   value="{{ old('license_key') }}"
                                   class="input-field uppercase tracking-wider {{ $errors->has('license_key') ? 'error' : '' }}">
                        </div>
                        <p class="text-[9px] text-slate-400 font-medium mt-1.5">
                            Format : IMPERIS-XXXX-XXXX-XXXX-EXPYYYYMMDD
                        </p>
                    </div>

                    <button type="submit" :disabled="loading"
                            class="w-full inline-flex items-center justify-center gap-2 bg-gradient-to-r from-orange-600 to-orange-500 text-white px-6 py-3.5 rounded-xl font-bold text-sm shadow-lg shadow-orange-200 hover:shadow-xl transition-all hover:-translate-y-0.5 disabled:opacity-70 disabled:cursor-not-allowed">
                        <span x-show="!loading">
                            <i class="fa-solid fa-check-circle text-xs mr-1"></i>
                            Activer la licence
                        </span>
                        <span x-show="loading" class="flex items-center gap-2">
                            <i class="fa-solid fa-spinner fa-spin text-xs"></i>
                            Vérification en cours...
                        </span>
                    </button>

                    <div class="text-center">
                        <button type="button" @click="tab='contact'"
                                class="text-[11px] font-bold text-slate-400 hover:text-orange-600 transition-colors">
                            <i class="fa-solid fa-headset mr-1"></i>
                            Je n'ai pas encore de clé — Contacter Imperis
                        </button>
                    </div>
                </form>
            </div>

            {{-- Footer panneau droit --}}
            <div class="fade-up d7 mt-8 pt-6 border-t border-slate-100 text-center">
                <p class="text-[9px] text-slate-300 font-bold uppercase tracking-widest">
                    Groupe Bama GED · Solution développée par
                    <span class="text-slate-400">Imperis SARL</span> · Bamako {{ date('Y') }}
                </p>
            </div>

        </div>
    </div>
</div>


<script>
function licenseApp() {
    return {
        tab: 'contact',
        sent: false,
        loading: false,
        form: {
            name: '',
            role: '',
            phone: '',
            message: '',
        },

        submitRequest() {
            if (!this.form.name || !this.form.role || !this.form.phone) return;

            this.loading = true;

            const licenseInfo = `{{ $license ? "Clé : " . $license->license_key . " | Expirée le : " . $license->expires_at->format('d/m/Y') : "Aucune licence enregistrée" }}`;

            const subject = encodeURIComponent('Demande de renouvellement de licence — GED Groupe Bama');
            const body = encodeURIComponent(
                `Bonjour Imperis SARL,\n\n` +
                `Nous vous contactons pour le renouvellement de la licence de notre plateforme GED.\n\n` +
                `--- INFORMATIONS DU DEMANDEUR ---\n` +
                `Nom       : ${this.form.name}\n` +
                `Fonction  : ${this.form.role}\n` +
                `Téléphone : ${this.form.phone}\n` +
                `Société   : Groupe Bama\n\n` +
                `--- INFORMATIONS LICENCE ---\n` +
                `${licenseInfo}\n\n` +
                (this.form.message ? `--- MESSAGE COMPLÉMENTAIRE ---\n${this.form.message}\n\n` : '') +
                `Merci de nous contacter dans les meilleurs délais pour procéder au renouvellement.\n\n` +
                `Cordialement,\n${this.form.name}\n${this.form.role} — Groupe Bama`
            );

            window.location.href = `mailto:contact@imperis.com?subject=${subject}&body=${body}`;

            setTimeout(() => {
                this.loading = false;
                this.sent = true;
            }, 1200);
        }
    }
}
</script>

</body>
</html>
