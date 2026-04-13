<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Groupe Bama — GED</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 99px; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
    </style>
    <script>
        function toastNotifications() {
            return {
                notifications: [],
                addNotification(message, type = 'info', duration = 5000) {
                    const id = Date.now() + Math.random();
                    this.notifications.push({ id, message, type });
                    if (duration > 0) setTimeout(() => this.removeById(id), duration);
                },
                removeById(id) {
                    this.notifications = this.notifications.filter(n => n.id !== id);
                },
                init() {
                    @if(session('success')) this.addNotification(@json(session('success')), 'success'); @endif
                    @if(session('success_password')) this.addNotification(@json(session('success_password')), 'success'); @endif
                    @if(session('error')) this.addNotification(@json(session('error')), 'error'); @endif
                    @if($errors->any()) @foreach($errors->all() as $error) this.addNotification(@json($error), 'error'); @endforeach @endif
                }
            }
        }
    </script>
</head>
<body class="h-full bg-slate-50 antialiased" x-data="{ sidebarOpen: false }">

<div class="flex h-full">

    {{-- ======= SIDEBAR ======= --}}
    @auth

    {{-- Overlay mobile --}}
    <div x-show="sidebarOpen" x-cloak
         @click="sidebarOpen = false"
         x-transition:enter="transition-opacity ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm lg:hidden">
    </div>

    {{-- Sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-white border-r border-slate-100 transition-transform duration-300 lg:static lg:translate-x-0"
           :class="sidebarOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full lg:translate-x-0'">

        {{-- Logo --}}
        <div class="flex h-16 shrink-0 items-center gap-3 px-5 border-b border-slate-100">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-orange-600 shadow-lg shadow-orange-200">
                <i class="fa-solid fa-file-shield text-white text-sm"></i>
            </div>
            <div>
                <p class="text-sm font-black text-slate-900 tracking-tight leading-none">Groupe Bama</p>
                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">GED Platform</p>
            </div>
            <button @click="sidebarOpen = false"
                class="ml-auto flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition lg:hidden">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 overflow-y-auto px-3 py-5 space-y-0.5">

            {{-- Recherche rapide --}}
            <form action="{{ route('documents.index') }}" method="GET" class="mb-3">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-[10px]"></i>
                    <input type="text" name="q" placeholder="Recherche rapide..."
                           class="w-full bg-slate-50 border border-slate-100 rounded-xl pl-8 pr-3 py-2 text-xs font-medium text-slate-700 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                </div>
            </form>

            <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] px-3 pb-2">Principal</p>

            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all
                      {{ Request::is('dashboard*') ? 'bg-orange-500 text-white shadow-md shadow-orange-200' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900' }}">
                <i class="fa-solid fa-chart-pie w-4 text-center text-[13px]"></i>
                <span>Tableau de bord</span>
                @if(Request::is('dashboard*'))
                    <span class="ml-auto w-1.5 h-1.5 rounded-full bg-white/60"></span>
                @endif
            </a>

            <a href="{{ route('documents.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all
                      {{ Request::is('documents*') && !Request::is('documents/advanced-search') ? 'bg-orange-500 text-white shadow-md shadow-orange-200' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900' }}">
                <i class="fa-solid fa-file-lines w-4 text-center text-[13px]"></i>
                <span>Documents</span>
            </a>

            <a href="{{ route('documents.advanced-search') }}"
               class="flex items-center gap-3 pl-9 pr-3 py-2 rounded-xl text-xs font-semibold transition-all
                      {{ Request::is('documents/advanced-search') ? 'bg-orange-500 text-white shadow-md shadow-orange-200' : 'text-slate-400 hover:bg-slate-100 hover:text-slate-700' }}">
                <i class="fa-solid fa-magnifying-glass w-3 text-center"></i>
                <span>Recherche avancée</span>
            </a>

            <a href="{{ route('documents.favorites') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all
                      {{ Request::is('favorites*') ? 'bg-orange-500 text-white shadow-md shadow-orange-200' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900' }}">
                <i class="fa-solid fa-star w-4 text-center text-[13px]"></i>
                <span>Favoris</span>
            </a>

            <a href="{{ route('categories.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all
                      {{ Request::is('categories*') ? 'bg-orange-500 text-white shadow-md shadow-orange-200' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900' }}">
                <i class="fa-solid fa-folder-tree w-4 text-center text-[13px]"></i>
                <span>Catégories</span>
            </a>

            <div class="pt-4 pb-2">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] px-3">Administration</p>
            </div>

            <a href="{{ route('users.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all
                      {{ Request::is('users*') ? 'bg-orange-500 text-white shadow-md shadow-orange-200' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900' }}">
                <i class="fa-solid fa-users w-4 text-center text-[13px]"></i>
                <span>Utilisateurs</span>
            </a>

            <a href="{{ route('settings.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all
                      {{ Request::is('settings*') ? 'bg-orange-500 text-white shadow-md shadow-orange-200' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900' }}">
                <i class="fa-solid fa-sliders w-4 text-center text-[13px]"></i>
                <span>Configuration</span>
            </a>

            @if(auth()->user()->hasRole('admin'))
            <a href="{{ route('reports.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all
                      {{ Request::is('reports*') ? 'bg-orange-500 text-white shadow-md shadow-orange-200' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900' }}">
                <i class="fa-solid fa-chart-bar w-4 text-center text-[13px]"></i>
                <span>Rapports</span>
            </a>
            <a href="{{ route('trash.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all
                      {{ Request::is('trash*') ? 'bg-orange-500 text-white shadow-md shadow-orange-200' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900' }}">
                <i class="fa-solid fa-trash-can w-4 text-center text-[13px]"></i>
                <span>Corbeille</span>
                @php $trashCount = \App\Models\Document::onlyTrashed()->count(); @endphp
                @if($trashCount > 0)
                <span class="ml-auto bg-red-100 text-red-600 text-[8px] font-black rounded-full px-1.5 py-0.5">{{ $trashCount }}</span>
                @endif
            </a>
            @endif

            <div class="pt-4 pb-2">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] px-3">Compte</p>
            </div>

            <a href="{{ route('notifications.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all
                      {{ Request::is('notifications*') ? 'bg-orange-500 text-white shadow-md shadow-orange-200' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900' }}">
                <i class="fa-solid fa-bell w-4 text-center text-[13px]"></i>
                <span>Notifications</span>
                @php $unread = \App\Models\GedNotification::where('user_id', auth()->id())->where('is_read', false)->count(); @endphp
                @if($unread > 0)
                <span id="notif-badge" class="ml-auto bg-red-500 text-white text-[8px] font-black rounded-full min-w-[16px] h-4 px-1 flex items-center justify-center">
                    {{ $unread > 9 ? '9+' : $unread }}
                </span>
                @else
                <span id="notif-badge" class="ml-auto hidden bg-red-500 text-white text-[8px] font-black rounded-full min-w-[16px] h-4 px-1 flex items-center justify-center"></span>
                @endif
            </a>

            <a href="{{ route('profile.show') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all
                      {{ Request::is('profile*') ? 'bg-orange-500 text-white shadow-md shadow-orange-200' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900' }}">
                <i class="fa-solid fa-circle-user w-4 text-center text-[13px]"></i>
                <span>Mon profil</span>
            </a>

        </nav>

        {{-- User card --}}
        <div class="shrink-0 p-3 border-t border-slate-100">
            <div class="flex items-center gap-3 rounded-xl bg-slate-50 border border-slate-100 px-3 py-2.5">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-orange-100 text-orange-600 font-black text-sm select-none">
                    {{ strtoupper(substr(auth()->user()->full_name, 0, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs font-bold text-slate-800 leading-tight">{{ auth()->user()->full_name }}</p>
                    <p class="truncate text-[9px] text-slate-400 mt-0.5">{{ auth()->user()->email }}</p>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" title="Déconnexion"
                        class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-500 transition-colors">
                        <i class="fa-solid fa-arrow-right-from-bracket text-xs"></i>
                    </button>
                </form>
            </div>
        </div>

    </aside>
    @endauth

    {{-- ======= MAIN ======= --}}
    <div class="flex flex-1 flex-col min-w-0 overflow-hidden">

        {{-- Topbar --}}
        <header class="flex h-16 shrink-0 items-center gap-3 border-b border-slate-100 bg-white px-4 md:px-6 shadow-sm z-30">

            @auth
            <button @click="sidebarOpen = true"
                class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 text-slate-500 hover:bg-slate-50 transition lg:hidden">
                <i class="fa-solid fa-bars text-sm"></i>
            </button>
            @endauth

            {{-- Brand mobile --}}
            <a href="/" class="flex items-center gap-2 lg:hidden">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-orange-600">
                    <i class="fa-solid fa-file-shield text-white text-xs"></i>
                </div>
                <span class="font-black text-slate-900 text-sm">Groupe Bama</span>
            </a>

            {{-- Breadcrumb desktop --}}
            <div class="hidden lg:flex items-center gap-2 text-xs text-slate-400 font-medium">
                <i class="fa-solid fa-house text-slate-300 text-[10px]"></i>
                <span class="text-slate-300">/</span>
                <span class="text-slate-700 font-bold capitalize">
                    {{ ucfirst(Request::segment(1) ?: 'accueil') }}
                </span>
            </div>

            <div class="ml-auto flex items-center gap-2">

                {{-- Date --}}
                <div class="hidden md:flex items-center gap-1.5 bg-slate-50 border border-slate-100 rounded-lg px-3 py-1.5">
                    <i class="fa-regular fa-calendar text-orange-400 text-[10px]"></i>
                    <span class="text-[10px] font-bold text-slate-500">{{ now()->translatedFormat('d M Y') }}</span>
                </div>

                @auth
                {{-- Profile dropdown --}}
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open"
                        class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition shadow-sm">
                        <div class="flex h-6 w-6 items-center justify-center rounded-lg bg-orange-100 text-orange-600 font-black text-xs select-none">
                            {{ strtoupper(substr(auth()->user()->full_name, 0, 1)) }}
                        </div>
                        <span class="hidden sm:block max-w-[110px] truncate">{{ auth()->user()->full_name }}</span>
                        <i class="fa-solid fa-chevron-down text-[8px] text-slate-400 transition-transform duration-150"
                           :class="open ? 'rotate-180' : ''"></i>
                    </button>

                    <div x-show="open" x-cloak @click.outside="open = false"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-52 origin-top-right rounded-2xl bg-white border border-slate-100 shadow-xl shadow-slate-200/70 overflow-hidden z-50">

                        <div class="px-4 py-3 bg-slate-50 border-b border-slate-100">
                            <p class="text-xs font-bold text-slate-900 truncate">{{ auth()->user()->full_name }}</p>
                            <p class="text-[10px] text-slate-400 truncate mt-0.5">{{ auth()->user()->email }}</p>
                        </div>

                        <div class="py-1.5">
                            <a href="{{ route('profile.show') }}"
                               class="flex items-center gap-3 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition">
                                <i class="fa-solid fa-circle-user text-slate-400 w-3.5 text-center"></i> Mon profil
                            </a>
                            <a href="{{ route('settings.index') }}"
                               class="flex items-center gap-3 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition">
                                <i class="fa-solid fa-sliders text-slate-400 w-3.5 text-center"></i> Paramètres
                            </a>
                        </div>

                        <div class="border-t border-slate-100 py-1.5">
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit"
                                    class="flex w-full items-center gap-3 px-4 py-2 text-xs font-semibold text-red-500 hover:bg-red-50 transition">
                                    <i class="fa-solid fa-arrow-right-from-bracket w-3.5 text-center"></i> Déconnexion
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                @endauth

            </div>
        </header>

        {{-- Content --}}
        <main class="flex-1 overflow-y-auto">
            <div class="mx-auto max-w-7xl px-4 py-6 md:px-6 md:py-8">
                @yield('content')
            </div>
        </main>

    </div>
</div>

{{-- ======= GUIDE INTERACTIF ======= --}}
@auth
<div x-data="gedGuide()" x-init="init()" x-cloak>

    {{-- Bouton flottant pour rouvrir le guide --}}
    <button @click="open = true; step = 0"
        x-show="!open"
        class="fixed bottom-20 right-5 z-[90] flex items-center gap-2 bg-slate-900 hover:bg-orange-600 text-white text-[10px] font-black uppercase tracking-widest px-4 py-2.5 rounded-xl shadow-xl transition-all"
        title="Guide d'utilisation">
        <i class="fa-solid fa-circle-question text-sm"></i>
        <span class="hidden sm:inline">Guide</span>
    </button>

    {{-- Overlay --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[95] bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">

        {{-- Modal guide --}}
        <div @click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden">

            {{-- Header --}}
            <div class="bg-gradient-to-r from-slate-900 to-slate-800 px-6 py-5 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-orange-600/20 rounded-full -translate-y-8 translate-x-8"></div>
                <div class="relative flex items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-[9px] font-black text-orange-400 uppercase tracking-widest">
                                Étape <span x-text="step + 1"></span> / <span x-text="steps.length"></span>
                            </span>
                        </div>
                        <h2 class="text-lg font-black text-white leading-tight" x-text="steps[step]?.title"></h2>
                        <p class="text-[10px] text-slate-400 mt-1" x-text="steps[step]?.subtitle"></p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-orange-600 flex items-center justify-center shrink-0 shadow-lg shadow-orange-900/30">
                        <i class="fa-solid text-white text-lg" :class="steps[step]?.icon"></i>
                    </div>
                </div>

                {{-- Progress bar --}}
                <div class="mt-4 bg-slate-700 rounded-full h-1.5">
                    <div class="bg-orange-500 h-1.5 rounded-full transition-all duration-300"
                         :style="'width: ' + ((step + 1) / steps.length * 100) + '%'"></div>
                </div>
            </div>

            {{-- Contenu --}}
            <div class="px-6 py-5">
                <div class="space-y-3">
                    <template x-for="(item, i) in steps[step]?.items" :key="i">
                        <div class="flex items-start gap-3 p-3 rounded-xl bg-slate-50 hover:bg-orange-50 transition-colors">
                            <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 mt-0.5"
                                 :class="item.color || 'bg-orange-100'">
                                <i class="fa-solid text-xs" :class="item.icon + ' ' + (item.iconColor || 'text-orange-500')"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-800" x-text="item.title"></p>
                                <p class="text-[10px] text-slate-500 mt-0.5 leading-relaxed" x-text="item.desc"></p>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Tip --}}
                <div x-show="steps[step]?.tip" class="mt-4 flex items-start gap-2 bg-amber-50 border border-amber-100 rounded-xl px-4 py-3">
                    <i class="fa-solid fa-lightbulb text-amber-500 text-sm shrink-0 mt-0.5"></i>
                    <p class="text-[10px] text-amber-800 font-medium leading-relaxed" x-text="steps[step]?.tip"></p>
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-6 pb-5 flex items-center justify-between gap-3">
                <button @click="prev()"
                    x-show="step > 0"
                    class="inline-flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold px-4 py-2.5 rounded-xl transition-all">
                    <i class="fa-solid fa-arrow-left text-[10px]"></i> Précédent
                </button>
                <span x-show="step === 0"></span>

                <div class="flex items-center gap-2 ml-auto">
                    <button @click="close()"
                        class="text-xs font-bold text-slate-400 hover:text-slate-600 px-3 py-2.5 transition-colors">
                        Fermer
                    </button>
                    <button @click="next()"
                        x-show="step < steps.length - 1"
                        class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-500 active:scale-95 text-white text-xs font-black uppercase tracking-widest px-5 py-2.5 rounded-xl shadow-lg shadow-orange-200 transition-all">
                        Suivant <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </button>
                    <button @click="close()"
                        x-show="step === steps.length - 1"
                        class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-500 active:scale-95 text-white text-xs font-black uppercase tracking-widest px-5 py-2.5 rounded-xl shadow-lg shadow-green-200 transition-all">
                        <i class="fa-solid fa-check text-[10px]"></i> Commencer !
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
function gedGuide() {
    const isAdmin  = {{ auth()->user()->hasRole('admin') ? 'true' : 'false' }};
    const isEditor = {{ auth()->user()->hasRole('editor') ? 'true' : 'false' }};
    const userName = @json(explode(' ', auth()->user()->full_name)[0]);

    const adminSteps = [
        {
            title: 'Bienvenue, ' + userName + ' !',
            subtitle: 'Guide administrateur — Groupe Bama GED',
            icon: 'fa-shield-halved',
            items: [
                { icon: 'fa-chart-pie',    color: 'bg-blue-100',   iconColor: 'text-blue-500',   title: 'Tableau de bord',    desc: 'Vue d\'ensemble : documents, catégories, utilisateurs, alertes.' },
                { icon: 'fa-file-lines',   color: 'bg-orange-100', iconColor: 'text-orange-500', title: 'Documents',          desc: 'Créez, gérez et archivez tous les documents de l\'organisation.' },
                { icon: 'fa-users',        color: 'bg-purple-100', iconColor: 'text-purple-500', title: 'Utilisateurs',       desc: 'Gérez les comptes, rôles et permissions de chaque membre.' },
            ],
            tip: 'En tant qu\'administrateur, vous avez accès à toutes les fonctionnalités du système.'
        },
        {
            title: 'Gestion des documents',
            subtitle: 'Créer, éditer, archiver',
            icon: 'fa-file-lines',
            items: [
                { icon: 'fa-plus',         color: 'bg-green-100',  iconColor: 'text-green-500',  title: 'Créer un document',  desc: 'Cliquez sur "Nouveau" dans la liste des documents. Un DOCX avec QR code est généré automatiquement.' },
                { icon: 'fa-pen',          color: 'bg-blue-100',   iconColor: 'text-blue-500',   title: 'Éditer en ligne',    desc: 'Modifiez le contenu directement dans le navigateur sans téléchargement.' },
                { icon: 'fa-brands fa-microsoft', color: 'bg-orange-100', iconColor: 'text-orange-500', title: 'Éditer dans Word', desc: 'Téléchargez, modifiez dans Word, puis réimportez via le panneau "Éditer dans Word".' },
                { icon: 'fa-box-archive',  color: 'bg-amber-100',  iconColor: 'text-amber-500',  title: 'Archiver',           desc: 'Archivez les documents obsolètes — ils restent consultables mais ne sont plus actifs.' },
            ],
            tip: 'Chaque modification crée automatiquement une nouvelle version. L\'historique complet est conservé.'
        },
        {
            title: 'Workflow d\'approbation',
            subtitle: 'Valider les documents officiels',
            icon: 'fa-list-check',
            items: [
                { icon: 'fa-paper-plane',  color: 'bg-blue-100',   iconColor: 'text-blue-500',   title: 'Configurer',         desc: 'Sur la page d\'un document → "Workflow d\'approbation" → sélectionnez les approbateurs dans l\'ordre.' },
                { icon: 'fa-check-circle', color: 'bg-green-100',  iconColor: 'text-green-500',  title: 'Approuver',          desc: 'Les approbateurs reçoivent une notification et peuvent approuver ou rejeter avec un commentaire.' },
                { icon: 'fa-signature',    color: 'bg-purple-100', iconColor: 'text-purple-500', title: 'Signer',             desc: 'Après approbation, les signataires peuvent apposer leur signature numérique.' },
            ],
            tip: 'Activez "Approbation obligatoire" dans les Paramètres pour forcer ce workflow sur tous les documents.'
        },
        {
            title: 'Sécurité & Conformité',
            subtitle: 'Audit, partage, vérification',
            icon: 'fa-shield-check',
            items: [
                { icon: 'fa-shield-halved', color: 'bg-purple-100', iconColor: 'text-purple-500', title: 'Journal d\'audit',  desc: 'Chaque action est tracée : qui a fait quoi, quand, depuis quelle IP.' },
                { icon: 'fa-share-nodes',  color: 'bg-green-100',  iconColor: 'text-green-500',  title: 'Partage sécurisé',  desc: 'Partagez un document avec un lien temporaire ou avec un utilisateur spécifique.' },
                { icon: 'fa-qrcode',       color: 'bg-slate-100',  iconColor: 'text-slate-500',  title: 'QR Code',           desc: 'Chaque document contient un QR code de vérification d\'authenticité.' },
                { icon: 'fa-sliders',      color: 'bg-orange-100', iconColor: 'text-orange-500', title: 'Paramètres',        desc: 'Configurez les notifications email, les délais de rétention et les règles d\'approbation.' },
            ],
            tip: 'Configurez votre serveur SMTP dans Paramètres → Notifications pour activer les alertes email.'
        },
    ];

    const editorSteps = [
        {
            title: 'Bienvenue, ' + userName + ' !',
            subtitle: 'Guide éditeur — Groupe Bama GED',
            icon: 'fa-pen-to-square',
            items: [
                { icon: 'fa-chart-pie',    color: 'bg-blue-100',   iconColor: 'text-blue-500',   title: 'Tableau de bord',    desc: 'Consultez les documents récents, les alertes et les statistiques.' },
                { icon: 'fa-file-lines',   color: 'bg-orange-100', iconColor: 'text-orange-500', title: 'Documents',          desc: 'Créez et modifiez les documents de votre périmètre.' },
                { icon: 'fa-magnifying-glass', color: 'bg-slate-100', iconColor: 'text-slate-500', title: 'Recherche avancée', desc: 'Trouvez rapidement n\'importe quel document par titre, référence, tags ou date.' },
            ],
            tip: 'En tant qu\'éditeur, vous pouvez créer et modifier des documents mais pas les supprimer.'
        },
        {
            title: 'Créer et éditer',
            subtitle: 'Votre flux de travail quotidien',
            icon: 'fa-file-lines',
            items: [
                { icon: 'fa-plus',         color: 'bg-green-100',  iconColor: 'text-green-500',  title: 'Nouveau document',   desc: 'Documents → "Nouveau" → remplissez le titre et la catégorie → le fichier DOCX est créé automatiquement.' },
                { icon: 'fa-pen',          color: 'bg-blue-100',   iconColor: 'text-blue-500',   title: 'Éditer en ligne',    desc: 'Cliquez sur "Éditer en ligne" pour modifier le contenu directement dans le navigateur.' },
                { icon: 'fa-brands fa-microsoft', color: 'bg-orange-100', iconColor: 'text-orange-500', title: 'Éditer dans Word', desc: 'Téléchargez → modifiez dans Word → réimportez via le panneau orange sur la page du document.' },
            ],
            tip: 'Après modification dans Word, glissez-déposez le fichier dans la zone "Étape 3" pour créer une nouvelle version.'
        },
        {
            title: 'Collaboration',
            subtitle: 'Travailler avec l\'équipe',
            icon: 'fa-share-nodes',
            items: [
                { icon: 'fa-comment',      color: 'bg-purple-100', iconColor: 'text-purple-500', title: 'Commentaires',       desc: 'Ajoutez des commentaires sur chaque document. Répondez aux notes de vos collègues.' },
                { icon: 'fa-share-nodes',  color: 'bg-green-100',  iconColor: 'text-green-500',  title: 'Partager',           desc: 'Partagez un document avec un collègue ou générez un lien temporaire.' },
                { icon: 'fa-eye',          color: 'bg-slate-100',  iconColor: 'text-slate-500',  title: 'Prévisualiser',      desc: 'Visualisez le contenu d\'un document sans le télécharger.' },
            ],
            tip: 'Les notifications vous alertent quand quelqu\'un commente ou partage un document avec vous.'
        },
    ];

    const viewerSteps = [
        {
            title: 'Bienvenue, ' + userName + ' !',
            subtitle: 'Guide consultation — Groupe Bama GED',
            icon: 'fa-eye',
            items: [
                { icon: 'fa-file-lines',   color: 'bg-orange-100', iconColor: 'text-orange-500', title: 'Consulter les documents', desc: 'Accédez à tous les documents auxquels vous avez accès depuis la liste.' },
                { icon: 'fa-magnifying-glass', color: 'bg-blue-100', iconColor: 'text-blue-500', title: 'Rechercher',          desc: 'Utilisez la recherche avancée pour trouver un document par titre, référence ou contenu.' },
                { icon: 'fa-download',     color: 'bg-green-100',  iconColor: 'text-green-500',  title: 'Télécharger',         desc: 'Téléchargez les documents pour les consulter hors ligne.' },
            ],
            tip: 'Vous êtes en mode consultation. Contactez un administrateur pour obtenir des droits d\'édition.'
        },
        {
            title: 'Vérification & Partage',
            subtitle: 'Authenticité des documents',
            icon: 'fa-qrcode',
            items: [
                { icon: 'fa-qrcode',       color: 'bg-slate-100',  iconColor: 'text-slate-500',  title: 'QR Code',             desc: 'Scannez le QR code sur un document imprimé pour vérifier son authenticité.' },
                { icon: 'fa-eye',          color: 'bg-blue-100',   iconColor: 'text-blue-500',   title: 'Prévisualiser',       desc: 'Visualisez le contenu d\'un document directement dans le navigateur.' },
                { icon: 'fa-comment',      color: 'bg-purple-100', iconColor: 'text-purple-500', title: 'Commenter',           desc: 'Ajoutez des commentaires et posez des questions sur les documents.' },
            ],
            tip: 'Vous pouvez accéder aux documents partagés avec vous via les liens reçus par email.'
        },
    ];

    return {
        open: false,
        step: 0,
        steps: isAdmin ? adminSteps : (isEditor ? editorSteps : viewerSteps),

        init() {
            const key = 'ged_guide_seen_{{ auth()->id() }}';
            if (!localStorage.getItem(key)) {
                setTimeout(() => { this.open = true; }, 800);
            }
        },

        next() { if (this.step < this.steps.length - 1) this.step++; },
        prev() { if (this.step > 0) this.step--; },

        close() {
            this.open = false;
            localStorage.setItem('ged_guide_seen_{{ auth()->id() }}', '1');
        }
    }
}
</script>
@endauth

@auth
{{-- Polling notifications toutes les 30s --}}
<script>
(function pollNotifications() {
    setInterval(async () => {
        try {
            const res  = await fetch('{{ route('notifications.count') }}', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            const badge = document.getElementById('notif-badge');
            if (!badge) return;
            if (data.count > 0) {
                badge.textContent = data.count > 9 ? '9+' : data.count;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        } catch(e) {}
    }, 30000);
})();
</script>
@endauth

{{-- ======= TOASTS ======= --}}
<div x-data="toastNotifications()"
     class="fixed bottom-5 right-5 z-[100] flex flex-col-reverse gap-2 max-w-xs w-full pointer-events-none">
    <template x-for="n in notifications" :key="n.id">
        <div class="pointer-events-auto flex items-start gap-3 rounded-2xl border bg-white px-4 py-3 shadow-xl shadow-slate-200/60"
             :class="{
                 'border-green-200': n.type === 'success',
                 'border-red-200':   n.type === 'error',
                 'border-blue-200':  n.type === 'info'
             }"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-3"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0 translate-y-2">

            <div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full"
                 :class="{
                     'bg-green-100 text-green-600': n.type === 'success',
                     'bg-red-100 text-red-600':     n.type === 'error',
                     'bg-blue-100 text-blue-600':   n.type === 'info'
                 }">
                <i class="text-[10px]"
                   :class="{
                       'fa-solid fa-check':  n.type === 'success',
                       'fa-solid fa-xmark':  n.type === 'error',
                       'fa-solid fa-info':   n.type === 'info'
                   }"></i>
            </div>

            <p class="flex-1 text-xs font-semibold text-slate-700 leading-relaxed" x-text="n.message"></p>

            <button @click="removeById(n.id)"
                class="shrink-0 text-slate-300 hover:text-slate-500 transition mt-0.5">
                <i class="fa-solid fa-xmark text-xs"></i>
            </button>
        </div>
    </template>
</div>

</body>
</html>
