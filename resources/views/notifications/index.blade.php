@extends('layouts.app')

@section('content')
<div class="space-y-5">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Notifications</h1>
            <p class="text-xs text-slate-400 mt-1">{{ $notifications->total() }} notification(s)</p>
        </div>
        @if($notifications->where('is_read', false)->count() > 0)
        <form action="{{ route('notifications.read-all') }}" method="POST">
            @csrf
            <button type="submit"
                class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm transition-all">
                <i class="fa-solid fa-check-double text-[10px]"></i> Tout marquer comme lu
            </button>
        </form>
        @endif
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        @if($notifications->isEmpty())
        <div class="flex flex-col items-center justify-center py-16 text-center">
            <div class="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center mb-4">
                <i class="fa-solid fa-bell-slash text-slate-300 text-xl"></i>
            </div>
            <p class="text-sm font-bold text-slate-400">Aucune notification</p>
        </div>
        @else
        <div class="divide-y divide-slate-50">
            @foreach($notifications as $notif)
            <div class="flex items-start gap-4 px-5 py-4 {{ $notif->is_read ? 'opacity-60' : 'bg-orange-50/30' }} hover:bg-slate-50/60 transition-colors">
                {{-- Icône type --}}
                <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 mt-0.5
                    {{ match(true) {
                        str_contains($notif->type, 'approval') => 'bg-blue-100',
                        str_contains($notif->type, 'share')    => 'bg-green-100',
                        str_contains($notif->type, 'comment')  => 'bg-purple-100',
                        str_contains($notif->type, 'expir')    => 'bg-red-100',
                        default                                 => 'bg-slate-100',
                    } }}">
                    <i class="text-xs fa-solid
                        {{ match(true) {
                            str_contains($notif->type, 'approval') => 'fa-check-circle text-blue-500',
                            str_contains($notif->type, 'share')    => 'fa-share-nodes text-green-500',
                            str_contains($notif->type, 'comment')  => 'fa-comment text-purple-500',
                            str_contains($notif->type, 'expir')    => 'fa-triangle-exclamation text-red-500',
                            default                                 => 'fa-bell text-slate-400',
                        } }}"></i>
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-sm font-bold text-slate-900 leading-tight">{{ $notif->title }}</p>
                        <span class="text-[9px] text-slate-400 font-mono shrink-0">{{ $notif->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">{{ $notif->message }}</p>
                    <div class="flex items-center gap-3 mt-2">
                        @if($notif->link)
                        <a href="{{ $notif->link }}"
                           class="text-[10px] font-black text-orange-600 hover:underline uppercase tracking-wider">
                            Voir <i class="fa-solid fa-arrow-right text-[8px]"></i>
                        </a>
                        @endif
                        @if(!$notif->is_read)
                        <form action="{{ route('notifications.read', $notif) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-[10px] font-bold text-slate-400 hover:text-slate-600">
                                Marquer comme lu
                            </button>
                        </form>
                        @endif
                        <form action="{{ route('notifications.destroy', $notif) }}" method="POST" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-[10px] font-bold text-red-400 hover:text-red-600">
                                Supprimer
                            </button>
                        </form>
                    </div>
                </div>

                @if(!$notif->is_read)
                <span class="w-2 h-2 rounded-full bg-orange-500 shrink-0 mt-2"></span>
                @endif
            </div>
            @endforeach
        </div>

        @if($notifications->hasPages())
        <div class="px-5 py-4 border-t border-slate-50">
            {{ $notifications->links() }}
        </div>
        @endif
        @endif
    </div>
</div>
@endsection
