<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Groupe Bama - Gestion Doc</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        // ... (Ton script toastNotifications actuel reste inchangé ici)
        function toastNotifications() {
            return {
                notifications: [],
                addNotification(message, type = 'info', duration = 5000) {
                    const id = Date.now();
                    this.notifications.push({ id, message, type, show: true });
                    if (duration > 0) { setTimeout(() => { this.removeNotificationById(id); }, duration); }
                },
                removeNotification(index) { this.notifications.splice(index, 1); },
                removeNotificationById(id) {
                    const index = this.notifications.findIndex(n => n.id === id);
                    if (index > -1) { this.notifications.splice(index, 1); }
                },
                init() {
                    @if(session('success')) this.addNotification('{{ session('success') }}', 'success'); @endif
                    @if(session('error')) this.addNotification('{{ session('error') }}', 'error'); @endif
                    @if($errors->any()) @foreach($errors->all() as $error) this.addNotification('{{ $error }}', 'error'); @endforeach @endif
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 flex flex-col h-screen" x-data="{ sidebarOpen: false }">

    <nav class="bg-orange-600 p-4 text-white shadow-md z-50">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center gap-4">
                @auth
                <button @click="sidebarOpen = !sidebarOpen" class="text-white text-2xl md:hidden focus:outline-none">
                    <i class="fa-solid" :class="sidebarOpen ? 'fa-times' : 'fa-bars'"></i>
                </button>
                @endauth
                <a href="/" class="font-black text-2xl tracking-tighter">GROUPE <span class="text-white">BAMA</span></a>
            </div>
            
            @auth
            <div class="flex items-center gap-4">
                <span class="hidden sm:inline text-sm font-medium bg-orange-700 px-3 py-1 rounded-full">
                    <i class="fa-solid fa-user-circle mr-2"></i>{{ auth()->user()->full_name }}
                </span>
                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="bg-white text-orange-600 px-4 py-1 rounded shadow hover:bg-orange-50 transition font-bold text-sm">
                        <span class="sm:inline hidden">Déconnexion</span>
                        <i class="fa-solid fa-sign-out-alt sm:hidden"></i>
                    </button>
                </form>
            </div>
            @endauth
        </div>
    </nav>

    <div class="flex flex-1 overflow-hidden relative">
        @auth
        <div x-show="sidebarOpen" 
             @click="sidebarOpen = false" 
             class="fixed inset-0 bg-black bg-opacity-50 z-40 md:hidden transition-opacity"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
        </div>

        <aside 
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-40 w-64 bg-white border-r border-gray-200 shadow-sm transition-transform duration-300 transform md:relative md:translate-x-0 flex flex-col h-full">
            
            <div class="p-6 overflow-y-auto flex-1">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Menu Principal</p>
                <nav class="mt-4 space-y-2">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 p-3 rounded-lg {{ Request::is('dashboard*') ? 'bg-orange-50 text-orange-600 font-bold' : 'text-gray-600 hover:bg-orange-50 hover:text-orange-600' }} transition">
                        <i class="fa-solid fa-chart-line w-5"></i> Dashboard
                    </a>
                    <a href="{{ route('documents.index') }}" class="flex items-center gap-3 p-3 rounded-lg {{ Request::is('documents*') && !Request::is('documents/advanced-search') ? 'bg-orange-50 text-orange-600 font-bold' : 'text-gray-600 hover:bg-orange-50 hover:text-orange-600' }} transition">
                        <i class="fa-solid fa-file-word w-5"></i> Documents
                    </a>
                    <a href="{{ route('documents.advanced-search') }}" class="flex items-center gap-3 p-3 rounded-lg {{ Request::is('documents/advanced-search') ? 'bg-orange-50 text-orange-600 font-bold' : 'text-gray-600 hover:bg-orange-50 hover:text-orange-600' }} transition ml-6">
                        <i class="fa-solid fa-search w-4"></i> Recherche
                    </a>
                    <a href="{{ route('categories.index') }}" class="flex items-center gap-3 p-3 rounded-lg {{ Request::is('categories*') ? 'bg-orange-50 text-orange-600 font-bold' : 'text-gray-600 hover:bg-orange-50 hover:text-orange-600' }} transition">
                        <i class="fa-solid fa-folder-tree w-5"></i> Catégories
                    </a>
                </nav>

                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mt-10">Paramètres</p>
                <nav class="mt-4 space-y-2">
                    <a href="{{ route('users.index') }}" class="flex items-center gap-3 p-3 rounded-lg {{ Request::is('users*') ? 'bg-orange-50 text-orange-600 font-bold' : 'text-gray-600 hover:bg-orange-50 hover:text-orange-600' }} transition">
                        <i class="fa-solid fa-users w-5"></i> Utilisateurs
                    </a>
                    <a href="{{ route('settings.index') }}" class="flex items-center gap-3 p-3 rounded-lg {{ Request::is('settings*') ? 'bg-orange-50 text-orange-600 font-bold' : 'text-gray-600 hover:bg-orange-50 hover:text-orange-600' }} transition">
                        <i class="fa-solid fa-gear w-5"></i> Configuration
                    </a>
                </nav>
            </div>
            
            <div class="p-6 border-t border-gray-100 text-center">
                <p class="text-xs text-gray-400">&copy; {{ date('Y') }} Groupe Bama</p>
            </div>
        </aside>
        @endauth

        <main class="flex-1 overflow-y-auto p-4 md:p-8">
            {{-- Notifications Toast --}}
            <div x-data="toastNotifications()" class="fixed top-4 right-4 z-50 space-y-2 max-w-sm">
                <template x-for="(notification, index) in notifications" :key="index">
                    <div class="flex items-center p-4 text-sm border rounded-lg shadow-lg bg-white"
                         :class="{
                             'border-green-200 text-green-800': notification.type === 'success',
                             'border-red-200 text-red-800': notification.type === 'error'
                         }"
                         x-show="notification.show">
                        <div class="flex-1" x-text="notification.message"></div>
                        <button @click="removeNotification(index)" class="ml-3 text-gray-400"><i class="fa-solid fa-times"></i></button>
                    </div>
                </template>
            </div>

            @yield('content')
        </main>
    </div>

</body>
</html>