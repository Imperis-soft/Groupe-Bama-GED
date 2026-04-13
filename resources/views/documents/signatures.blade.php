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
                            <span class="text-[9px] font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded">
                                <i class="fa-solid fa-shield-check mr-1"></i>Valide
                            </span>
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

        {{-- Signer --}}
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
            </div>
        </div>

    </div>
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
                el.textContent = 'Erreur lors de la signature.';
                el.className = 'text-[10px] text-center font-bold text-red-600';
            }
            el.classList.remove('hidden');
        }
    }
}
</script>
@endsection
