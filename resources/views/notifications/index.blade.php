@extends('layouts.app')

@section('content')
<div class="space-y-5" x-data="{ confirmClear: false }">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Notifications</h1>
            <p class="text-xs text-slate-400 mt-1">
                {{ $notifications->total() }} notification(s)
                @if($unreadCount > 0)
                    · <span class="text-orange-600 font-bold">{{ $unreadCount }} non lue(s)</span>
                @endif
            </p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            @if($unreadCount > 0)
            <form action="{{ route('notifications.read-all') }}" method="POST">
                @csrf
                <button type="submit"
                    class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm transition-all">
                    <i class="fa-solid fa-check-double text-[10px]"></i> Tout marquer lu
                </button>
            </form>
            @endif
            <button @click="confirmClear = true"
                class="inline-flex items-center gap-2 bg-white border border-red-100 hover:bg-red-50 text-red-500 text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm transition-all">
                <i class="fa-solid fa-trash-can text-[10px]"></i> Supprimer les lues
            </button>
        </div>
    </div>

    {{-- FILTRES --}}
    <form method="GET" action="{{ route('notifications.index') }}"
          class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-2 flex-wrap flex-1">
            <select name="type"
                    class="bg-slate-50 border border-slate-100 rounded-xl px-3 py-2 text-xs font-medium text-slate-600 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                <option value="">Tous les types</option>
                <option value="approval" {{ request('type') === 'approval' ? 'selected' : '' }}>Approbations</option>
                <option value="share"    {{ request('type') === 'share'    ? 'selected' : '' }}>Partages</option>
                <option value="comment"  {{ request('type') === 'comment'  ? 'selected' : '' }}>Commentaires</option>
                <option value="expir"    {{ request('type') === 'expir'    ? 'selected' : '' }}>Expirations</option>
            </select>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="unread" value="1" {{ request('unread') ? 'checked' : '' }}
                       class="w-4 h-4 text-orange-600 rounded border-slate-300 focus:ring-orange-500">
                <span class="text-xs font-bold text-slate-600">Non lues seulement</span>
            </label>
        </div>
        <button type="submit"
            class="bg-orange-600 hover:bg-orange-500 text-white px-4 py-2 rounded-xl text-xs font-black transition-all active:scale-95">
            <i class="fa-solid fa-filter"></i>
        </button>
        @if(request('type') || request('unread'))
        <a href="{{ route('notifications.index') }}"
           class="text-xs font-bold text-slate-400 hover:text-red-500 transition-colors">
            <i class="fa-solid fa-xmark mr-1"></i>Réinitialiser
        </a>
        @endif
    </form>

    {{-- LISTE --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        @if($notifications->isEmpty())
        <div class="flex flex-col items-center justify-center py-16 text-center">
            <div class="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center mb-4">
                <i class="fa-solid fa-bell-slash text-slate-300 text-xl"></i>
            </div>
            <p class="text-sm font-bold text-slate-400">Aucune notification</p>
            <p class="text-xs text-slate-300 mt-1">Vous êtes à jour !</p>
        </div>
        @else
        <div class="divide-y divide-slate-50">
            @foreach($notifications as $notif)
            @php
                $typeColor = match(true) {
                    str_contains($notif->type, 'approval') => ['bg' => 'bg-blue-100',   'icon' => 'fa-check-circle text-blue-500'],
                    str_contains($notif->type, 'share')    => ['bg' => 'bg-green-100',  'icon' => 'fa-share-nodes text-green-500'],
                    str_contains($notif->type, 'comment')  => ['bg' => 'bg-purple-100', 'icon' => 'fa-comment text-purple-500'],
                    str_contains($notif->type, 'expir')    => ['bg' => 'bg-red-100',    'icon' => 'fa-triangle-exclamation text-red-500'],
                    default                                 => ['bg' => 'bg-slate-100',  'icon' => 'fa-bell text-slate-400'],
                };
            @endphp
            <div class="flex items-start gap-4 px-5 py-4 transition-colors
                {{ $notif->is_read ? '' : 'bg-orange-50/40' }} hover:bg-slate-50/60"
                 x-data="{ removing: false }" x-show="!removing" x-transition>

                <div class="w-9 h-9 rounded-xl {{ $typeColor['bg'] }} flex items-center justify-center shrink-0 mt-0.5">
                    <i class="text-xs fa-solid {{ $typeColor['icon'] }}"></i>
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-sm font-bold text-slate-900 leading-tight">{{ $notif->title }}</p>
                        <span class="text-[9px] text-slate-400 font-mono shrink-0 mt-0.5">{{ $notif->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">{{ $notif->message }}</p>
                    <div class="flex items-center gap-3 mt-2 flex-wrap">
                        @if($notif->link)
                        <a href="{{ $notif->link }}"
                           class="text-[10px] font-black text-orange-600 hover:underline uppercase tracking-wider">
                            Voir <i class="fa-solid fa-arrow-right text-[8px]"></i>
                        </a>
                        @endif
                        @if(!$notif->is_read)
                        <form action="{{ route('notifications.read', $notif) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-[10px] font-bold text-slate-400 hover:text-green-600 transition-colors">
                                <i class="fa-solid fa-check text-[8px] mr-0.5"></i>Marquer lu
                            </button>
                        </form>
                        @endif
                        <form action="{{ route('notifications.destroy', $notif) }}" method="POST" class="inline"
                              @submit.prevent="removing = true; $el.submit()">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-[10px] font-bold text-slate-300 hover:text-red-500 transition-colors">
                                <i class="fa-solid fa-trash-can text-[8px] mr-0.5"></i>Supprimer
                            </button>
                        </form>
                    </div>
                </div>

                @if(!$notif->is_read)
                <span class="w-2 h-2 rounded-full bg-orange-500 shrink-0 mt-2.5"></span>
                @endif
            </div>
            @endforeach
        </div>

        @if($notifications->hasPages())
        <div class="px-5 py-4 border-t border-slate-50 flex flex-col sm:flex-row items-center justify-between gap-3">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                {{ $notifications->firstItem() }}–{{ $notifications->lastItem() }} sur {{ $notifications->total() }}
            </p>
            {{ $notifications->links() }}
        </div>
        @endif
        @endif
    </div>

    {{-- MODAL CONFIRMATION SUPPRESSION LUES --}}
    <div x-show="confirmClear" x-cloak
         class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div @click="confirmClear = false" class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
        <div @click.stop class="relative bg-white rounded-2xl shadow-2xl p-6 max-w-sm w-full">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-trash-can text-red-500"></i>
                </div>
                <div>
                    <p class="text-sm font-black text-slate-900">Supprimer les notifications lues</p>
                    <p class="text-xs text-slate-400 mt-0.5">Cette action est irréversible.</p>
                </div>
            </div>
            <div class="flex gap-3">
                <button @click="confirmClear = false"
                    class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-600 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider transition-all">
                    Annuler
                </button>
                <form action="{{ route('notifications.destroy-read') }}" method="POST" class="flex-1">
                    @csrf @method('DELETE')
                    <button type="submit"
                        class="w-full bg-red-600 hover:bg-red-500 active:scale-95 text-white py-2.5 rounded-xl font-black text-xs uppercase tracking-widest shadow-lg shadow-red-200 transition-all">
                        Supprimer
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
