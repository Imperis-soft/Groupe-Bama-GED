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

            <div class="pt-4 pb-2">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] px-3">Compte</p>
            </div>

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
