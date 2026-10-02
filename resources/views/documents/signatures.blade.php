@extends('layouts.app')

@section('content')
<div class="space-y-5">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 font-medium mb-1">
                <a href="{{ route('documents.show', $document) }}" class="hover:text-orange-600 transition-colors">{{ $document->title }}</a>
                <i class="fa-solid fa-chevron-right text-[8px]"></i>
                <span class="text-slate-600 font-bold">Signatures</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Signatures numériques</h1>
        </div>
        <a href="{{ route('documents.show', $document) }}"
           class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm transition-all self-start sm:self-auto">
            <i class="fa-solid fa-arrow-left text-[10px]"></i> Retour
        </a>
    </div>


    @include('documents._workflow-banner')

    {{-- Demande de signature adressée à l'utilisateur --}}
    @if($myRequest)
    <div class="bg-purple-50 border border-purple-100 rounded-2xl px-5 py-4 flex flex-col md:flex-row md:items-center gap-3" x-data="{ declining: false }">
        <div class="flex items-start gap-3 flex-1 min-w-0">
            <div class="w-9 h-9 rounded-xl bg-white flex items-center justify-center shrink-0">
                <i class="fa-solid fa-signature text-purple-500 text-sm"></i>
            </div>
            <div class="min-w-0">
                <p class="text-sm font-bold text-purple-900">
                    {{ $myRequest->requester?->full_name ?? 'Un collaborateur' }} vous demande de signer ce document
                    @if($myRequest->due_at)
                    <span class="text-xs font-bold {{ $myRequest->isOverdue() ? 'text-red-600' : 'text-purple-500' }}">· avant le {{ $myRequest->due_at->format('d/m/Y') }}</span>
                    @endif
                </p>
                @if($myRequest->message)
                <p class="text-xs text-purple-700/80 italic mt-0.5">« {{ $myRequest->message }} »</p>
                @endif
                <form x-show="declining" x-cloak method="POST" action="{{ route('documents.signature-requests.decline', [$document, $myRequest]) }}" class="mt-3 flex flex-col sm:flex-row gap-2">
                    @csrf
                    <input type="text" name="reason" required maxlength="1000" placeholder="Motif du refus"
                           class="flex-1 bg-white border border-purple-100 rounded-xl px-3 py-2 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-400">
                    <button type="submit" class="bg-red-600 hover:bg-red-500 text-white text-[10px] font-black uppercase px-4 py-2 rounded-xl">Confirmer le refus</button>
                </form>
            </div>
        </div>
        <button type="button" x-show="!declining" @click="declining = true"
            class="self-start md:self-auto text-[10px] font-black uppercase text-red-600 bg-white border border-red-100 hover:bg-red-50 px-3 py-2 rounded-lg transition-all">
            Refuser de signer
        </button>
    </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

        {{-- Signatures existantes --}}
        <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-2 px-5 py-4 border-b border-slate-50">
                <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Signatures enregistrées</h2>
            </div>

            @if($signatures->isEmpty())
            <div class="flex flex-col items-center justify-center py-12 text-center">
                <div class="w-10 h-10 rounded-2xl bg-slate-50 flex items-center justify-center mb-3">
                    <i class="fa-solid fa-signature text-slate-300 text-lg"></i>
                </div>
                <p class="text-xs font-bold text-slate-400">Aucune signature</p>
            </div>
            @else
            <div class="divide-y divide-slate-50">
                @foreach($signatures as $sig)
                <div class="flex items-start gap-4 px-5 py-4">
                    <div class="w-9 h-9 rounded-xl bg-purple-50 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-signature text-purple-500 text-sm"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm font-bold text-slate-800">{{ $sig->user->full_name }}</p>
                            <span class="text-[9px] font-mono text-slate-400">{{ $sig->signed_at->format('d/m/Y H:i') }}</span>
                        </div>
                        @if($sig->reason)
                        <p class="text-xs text-slate-500 mt-0.5 italic">{{ $sig->reason }}</p>
                        @endif
                        <div class="flex items-center gap-3 mt-2">
                            <span class="font-mono text-[9px] text-slate-400 bg-slate-50 px-2 py-0.5 rounded truncate max-w-[200px]">
                                {{ substr($sig->signature_hash, 0, 20) }}...
                            </span>
                            @if($sig->document_checksum && $currentChecksum && hash_equals($sig->document_checksum, $currentChecksum))
                            <span class="text-[9px] font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded">
                                <i class="fa-solid fa-shield-check mr-1"></i>Version actuelle (v{{ $sig->document_version }})
                            </span>
                            @else
                            <span class="text-[9px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded" title="Le document a changé depuis cette signature : elle ne vaut pas pour le contenu actuel">
                                <i class="fa-solid fa-triangle-exclamation mr-1"></i>Version {{ $sig->document_version ?? '?' }} — contenu modifié depuis
                            </span>
                            @endif
                        </div>
                        @if($sig->signature_data)
                        <img src="{{ $sig->signature_data }}" alt="Signature" class="mt-2 h-12 border border-slate-100 rounded-lg bg-white p-1">
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Signer : seulement les personnes sollicitées, pendant la série de signatures --}}
        @if($myRequest && $document->status === 'signing')
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5" x-data="signaturePad()">
            <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest mb-4">Signer ce document</h2>

            <div class="space-y-4">
                <div>
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Votre signature</label>
                    <canvas id="signatureCanvas"
                        class="w-full h-32 border-2 border-dashed border-slate-200 rounded-xl bg-slate-50 cursor-crosshair touch-none"
                        @mousedown="startDraw($event)" @mousemove="draw($event)" @mouseup="stopDraw()"
                        @touchstart.prevent="startDraw($event.touches[0])" @touchmove.prevent="draw($event.touches[0])" @touchend="stopDraw()">
                    </canvas>
                    <button @click="clearCanvas()" class="text-[9px] text-slate-400 hover:text-red-500 mt-1 transition-colors">
                        <i class="fa-solid fa-eraser mr-1"></i> Effacer
                    </button>
                </div>

                <div>
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Raison (optionnel)</label>
                    <input type="text" x-model="reason" placeholder="Ex: Approbation finale"
                           class="w-full bg-slate-50 border border-slate-100 rounded-xl px-3 py-2 text-xs font-medium text-slate-700 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>

                <button @click="submitSignature()"
                    :disabled="!hasSignature"
                    :class="hasSignature ? 'bg-orange-600 hover:bg-orange-500 shadow-lg shadow-orange-200' : 'bg-slate-200 cursor-not-allowed'"
                    class="w-full text-white py-2.5 rounded-xl font-black text-xs uppercase tracking-widest transition-all active:scale-95">
                    <i class="fa-solid fa-signature mr-1.5"></i> Signer
                </button>

                <p id="signResult" class="text-[10px] text-center font-bold hidden"></p>
                <p class="text-[9px] text-slate-400 text-center">Votre signature sera liée à la version {{ $document->version }} du fichier (empreinte SHA-256).</p>
            </div>
        </div>
        @else
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 flex flex-col items-center justify-center text-center">
            <i class="fa-solid fa-lock text-slate-200 text-2xl mb-3"></i>
            <p class="text-xs font-bold text-slate-500">Aucune signature ne vous est demandée sur ce document.</p>
            <p class="text-[10px] text-slate-400 mt-1">Seules les personnes sollicitées par le gestionnaire du document peuvent le signer.</p>
        </div>
        @endif

    </div>

    {{-- Demandes de signature --}}
    @if($requests->isNotEmpty() || $document->canManage())
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-2 px-5 py-4 border-b border-slate-50">
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Demandes de signature</h2>
            </div>
            @forelse($requests as $sr)
            @php
                $statusStyle = [
                    'pending'   => 'bg-amber-50 text-amber-600',
                    'signed'    => 'bg-emerald-50 text-emerald-600',
                    'declined'  => 'bg-red-50 text-red-600',
                    'cancelled' => 'bg-slate-100 text-slate-400',
                ][$sr->status] ?? 'bg-slate-100 text-slate-500';
            @endphp
            <div class="flex flex-col sm:flex-row sm:items-center gap-2 px-5 py-3 border-b border-slate-50 last:border-0">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-bold text-slate-800">{{ $sr->signer?->full_name ?? '—' }}</p>
                    <p class="text-[10px] text-slate-400 mt-0.5">
                        Demandé par {{ $sr->requester?->full_name ?? '—' }} {{ $sr->created_at->diffForHumans() }}
                        @if($sr->due_at) · échéance {{ $sr->due_at->format('d/m/Y') }} @endif
                        @if($sr->isOverdue())<span class="text-red-600 font-bold">· en retard</span>@endif
                    </p>
                    @if($sr->status === 'declined' && $sr->decline_reason)
                    <p class="text-xs text-red-600/80 mt-1">« {{ $sr->decline_reason }} »</p>
                    @endif
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="px-2 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider {{ $statusStyle }}">
                        {{ \App\Models\SignatureRequest::STATUSES[$sr->status] ?? $sr->status }}
                    </span>
                    @if($sr->isPending() && ($document->canManage() || $sr->requested_by === auth()->id()))
                    <form method="POST" action="{{ route('documents.signature-requests.cancel', [$document, $sr]) }}"
                          onsubmit="return confirm('Annuler cette demande de signature ?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-[10px] font-bold text-slate-400 hover:text-red-600 transition-colors">Annuler</button>
                    </form>
                    @endif
                </div>
            </div>
            @empty
            <p class="text-xs text-slate-300 text-center py-10">Aucune demande de signature</p>
            @endforelse
        </div>

        @if($document->canManage() && !$document->canBeSentForSignature() && !$document->isArchived())
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 flex flex-col items-center justify-center text-center">
            <i class="fa-solid fa-list-check text-slate-200 text-2xl mb-3"></i>
            <p class="text-xs font-bold text-slate-500">
                {{ $document->status === 'review' ? 'Le circuit d\'approbation est en cours : les signatures pourront être demandées une fois le document approuvé.' : 'Cette catégorie exige une approbation avant signature.' }}
            </p>
            @if($document->status === 'draft')
            <a href="{{ route('documents.approval', $document) }}" class="mt-3 text-[10px] font-black text-orange-600 hover:underline uppercase">Lancer le circuit d'approbation</a>
            @endif
        </div>
        @elseif($document->canManage() && !$document->isArchived())
        <form method="POST" action="{{ route('documents.signature-requests.store', $document) }}"
              class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-4" x-data="{ search: '' }">
            @csrf
            <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Demander une signature</h2>
            <div>
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Signataires</label>
                <input type="text" x-model="search" placeholder="Rechercher…"
                       class="w-full bg-slate-50 border border-slate-100 rounded-xl px-3 py-2 text-xs font-medium text-slate-700 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 mb-2">
                <div class="max-h-44 overflow-y-auto space-y-0.5 border border-slate-100 rounded-xl p-1.5">
                    @foreach($users as $user)
                    <label class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-50 cursor-pointer"
                           x-show="!search || @js(mb_strtolower($user->full_name)).includes(search.toLowerCase())">
                        <input type="checkbox" name="signers[]" value="{{ $user->id }}"
                               {{ in_array($user->id, old('signers', [])) ? 'checked' : '' }}
                               class="rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                        <span class="text-xs font-semibold text-slate-700 truncate">{{ $user->full_name }}</span>
                    </label>
                    @endforeach
                </div>
                @error('signers') <p class="text-red-500 text-[9px] font-bold mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Message (optionnel)</label>
                <textarea name="message" rows="2" maxlength="1000" placeholder="Ex : Merci de signer le contrat avant vendredi"
                    class="w-full bg-slate-50 border border-slate-100 rounded-xl px-3 py-2 text-xs font-medium text-slate-700 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 resize-none">{{ old('message') }}</textarea>
            </div>
            <div>
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Délai (jours, optionnel)</label>
                <input type="number" name="due_days" min="1" max="365" value="{{ old('due_days') }}" placeholder="Ex : 3"
                       class="w-full bg-slate-50 border border-slate-100 rounded-xl px-3 py-2 text-xs font-medium text-slate-700 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>
            <button type="submit"
                class="w-full bg-orange-600 hover:bg-orange-500 text-white py-2.5 rounded-xl font-black text-xs uppercase tracking-widest shadow-lg shadow-orange-200 transition-all active:scale-95">
                <i class="fa-solid fa-paper-plane mr-1.5"></i> Envoyer la demande
            </button>
        </form>
        @endif
    </div>
    @endif
</div>

<script>
function signaturePad() {
    return {
        drawing: false,
        hasSignature: false,
        reason: '',
        lastX: 0, lastY: 0,
        canvas: null, ctx: null,

        init() {
            this.canvas = document.getElementById('signatureCanvas');
            this.ctx = this.canvas.getContext('2d');
            this.canvas.width = this.canvas.offsetWidth;
            this.canvas.height = this.canvas.offsetHeight;
            this.ctx.strokeStyle = '#1e293b';
            this.ctx.lineWidth = 2;
            this.ctx.lineCap = 'round';
        },

        getPos(e) {
            const rect = this.canvas.getBoundingClientRect();
            return { x: e.clientX - rect.left, y: e.clientY - rect.top };
        },

        startDraw(e) {
            this.drawing = true;
            const pos = this.getPos(e);
            this.lastX = pos.x; this.lastY = pos.y;
        },

        draw(e) {
            if (!this.drawing) return;
            const pos = this.getPos(e);
            this.ctx.beginPath();
            this.ctx.moveTo(this.lastX, this.lastY);
            this.ctx.lineTo(pos.x, pos.y);
            this.ctx.stroke();
            this.lastX = pos.x; this.lastY = pos.y;
            this.hasSignature = true;
        },

        stopDraw() { this.drawing = false; },

        clearCanvas() {
            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
            this.hasSignature = false;
        },

        async submitSignature() {
            const data = this.canvas.toDataURL('image/png');
            const res = await fetch('{{ route('documents.signatures.store', $document) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ signature_data: data, reason: this.reason }),
            });
            const json = await res.json();
            const el = document.getElementById('signResult');
            if (json.success) {
                el.textContent = '✓ Signature enregistrée — Hash: ' + json.hash.substring(0, 16) + '...';
                el.className = 'text-[10px] text-center font-bold text-green-600';
                this.clearCanvas();
                setTimeout(() => location.reload(), 1500);
            } else {
                el.textContent = json.message || 'Erreur lors de la signature.';
                el.className = 'text-[10px] text-center font-bold text-red-600';
            }
            el.classList.remove('hidden');
        }
    }
}
</script>
@endsection
