@extends('layouts.app')

@section('content')
<div class="space-y-5">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 font-medium mb-1">
                <a href="{{ route('documents.show', $document) }}" class="hover:text-orange-600 transition-colors">{{ $document->title }}</a>
                <i class="fa-solid fa-chevron-right text-[8px]"></i>
                <span class="text-slate-600 font-bold">Partages</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Partage du document</h1>
        </div>
        <a href="{{ route('documents.show', $document) }}"
           class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm transition-all self-start sm:self-auto">
            <i class="fa-solid fa-arrow-left text-[10px]"></i> Retour
        </a>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

        {{-- Liste des partages --}}
        <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-2 px-5 py-4 border-b border-slate-50">
                <span class="w-2 h-2 rounded-full bg-green-500"></span>
                <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Partages actifs</h2>
            </div>

            @if($shares->isEmpty())
            <div class="flex flex-col items-center justify-center py-12 text-center">
                <div class="w-10 h-10 rounded-2xl bg-slate-50 flex items-center justify-center mb-3">
                    <i class="fa-solid fa-share-nodes text-slate-300 text-lg"></i>
                </div>
                <p class="text-xs font-bold text-slate-400">Aucun partage actif</p>
            </div>
            @else
            <div class="divide-y divide-slate-50">
                @foreach($shares as $share)
                <div class="flex items-start gap-4 px-5 py-4 {{ !$share->isValid() ? 'opacity-50' : '' }}">
                    <div class="w-9 h-9 rounded-xl bg-green-50 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-share-nodes text-green-500 text-sm"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm font-bold text-slate-800">
                                {{ $share->sharedWith?->full_name ?? 'Lien public' }}
                            </p>
                            <span class="px-2 py-0.5 rounded-lg text-[9px] font-black uppercase
                                {{ $share->access_level === 'edit' ? 'bg-orange-50 text-orange-600' :
                                   ($share->access_level === 'comment' ? 'bg-purple-50 text-purple-600' : 'bg-slate-100 text-slate-500') }}">
                                {{ $share->access_level }}
                            </span>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-0.5">
                            Par {{ $share->sharedBy->full_name }} · {{ $share->created_at->diffForHumans() }}
                            @if($share->expires_at) · Expire {{ $share->expires_at->format('d/m/Y') }} @endif
                        </p>
                        @if($share->share_token)
                        <div class="flex items-center gap-2 mt-2">
                            <code class="text-[9px] bg-slate-50 border border-slate-100 rounded px-2 py-1 font-mono text-slate-600 truncate max-w-[200px]">
                                {{ url('/share/' . $share->share_token) }}
                            </code>
                            <button onclick="navigator.clipboard.writeText('{{ url('/share/' . $share->share_token) }}')"
                                class="text-[9px] text-slate-400 hover:text-orange-600 transition-colors">
                                <i class="fa-solid fa-copy"></i>
                            </button>
                        </div>
                        @endif
                    </div>
                    <form action="{{ route('documents.shares.revoke', [$document, $share]) }}" method="POST" class="inline">
                        @csrf @method('DELETE')
                        <button type="submit"
                            class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600 transition-all">
                            <i class="fa-solid fa-xmark text-xs"></i>
                        </button>
                    </form>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Nouveau partage --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest mb-4">Nouveau partage</h2>
            <form action="{{ route('documents.shares.store', $document) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Partager avec</label>
                    <select name="shared_with"
                            class="w-full bg-slate-50 border {{ $errors->has('shared_with') ? 'border-red-300' : 'border-slate-100' }} rounded-xl px-3 py-2.5 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <option value="">Sélectionner un utilisateur</option>
                        @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ old('shared_with') == $user->id ? 'selected' : '' }}>{{ $user->full_name }}</option>
                        @endforeach
                    </select>
                    @error('shared_with')
                    <p class="mt-1.5 flex items-center gap-1.5 text-[10px] font-bold text-red-600">
                        <i class="fa-solid fa-circle-exclamation text-[9px]"></i> {{ $message }}
                    </p>
                    @enderror
                </div>
                <div>
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Niveau d'accès</label>
                    <select name="access_level"
                            class="w-full bg-slate-50 border border-slate-100 rounded-xl px-3 py-2.5 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <option value="view">Lecture seule</option>
                        <option value="comment">Lecture + Commentaires</option>
                        <option value="edit">Édition complète</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Message (optionnel)</label>
                    <textarea name="message" rows="2" placeholder="Message pour le destinataire..."
                        class="w-full bg-slate-50 border border-slate-100 rounded-xl px-3 py-2 text-xs font-medium text-slate-700 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 resize-none"></textarea>
                </div>
                <div>
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Expiration</label>
                    <input type="date" name="expires_at"
                           class="w-full bg-slate-50 border border-slate-100 rounded-xl px-3 py-2 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="generate_link" value="1" class="w-4 h-4 text-orange-600 rounded">
                    <span class="text-xs font-medium text-slate-600">Générer un lien public</span>
                </label>
                <button type="submit"
                    class="w-full bg-orange-600 hover:bg-orange-500 text-white py-2.5 rounded-xl font-black text-xs uppercase tracking-widest shadow-lg shadow-orange-200 transition-all active:scale-95">
                    <i class="fa-solid fa-share-nodes mr-1.5"></i> Partager
                </button>
            </form>
        </div>

    </div>
</div>
@endsection
