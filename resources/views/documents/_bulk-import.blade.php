{{--
    Import multiple : file d'envoi côté navigateur (un document créé par fichier).
    Ouvert via la variable parente « uploadModal » ; accepte aussi un glisser-déposer
    de fichiers ou de dossiers n'importe où sur la page.
--}}
@php
    $isAdmin = auth()->user()->hasRole('admin');
@endphp
<div x-data="bulkImport({
        url: @js(route('documents.store')),
        extensions: @js(\App\Support\FileType::extensions()),
        maxBytes: {{ config('ged.max_upload_kb') * 1024 }},
        isAdmin: @js($isAdmin),
        categoryId: @js($openFolderId ?? ''),
     })"
     @dragenter.window="onWindowDragEnter($event)"
     @dragover.window.prevent="onWindowDragOver($event)"
     @dragleave.window="onWindowDragLeave($event)"
     @drop.window.prevent="onWindowDrop($event)"
     @beforeunload.window="if (running) { $event.preventDefault(); $event.returnValue = ''; }">

    {{-- Surcouche de dépôt sur toute la page --}}
    <div x-show="pageDragging" x-cloak
         class="fixed inset-0 z-[110] bg-orange-600/10 backdrop-blur-[2px] border-4 border-dashed border-orange-400 flex items-center justify-center pointer-events-none">
        <div class="bg-white rounded-3xl shadow-2xl px-10 py-8 text-center">
            <i class="fa-solid fa-cloud-arrow-up text-orange-500 text-4xl mb-3"></i>
            <p class="text-base font-black text-slate-900">Déposez vos fichiers pour les importer</p>
            <p class="text-xs text-slate-400 mt-1">Fichiers ou dossiers entiers</p>
        </div>
    </div>

    {{-- MODALE --}}
    <div x-show="uploadModal" x-cloak class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4"
         @keydown.escape.window="uploadModal && close()">
        <div @click="close()" class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>

        <div @click.stop class="relative bg-white w-full sm:max-w-3xl rounded-t-3xl sm:rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[92vh]">

            {{-- En-tête --}}
            <div class="border-b border-slate-100 px-6 py-4 flex items-center justify-between shrink-0">
                <div>
                    <h2 class="text-base font-black text-slate-900">Ajouter des documents</h2>
                    <p class="text-[10px] text-slate-400 mt-0.5">Un document est créé pour chaque fichier · {{ \App\Support\FileType::summary() }}</p>
                </div>
                <button @click="close()" class="w-8 h-8 flex items-center justify-center rounded-xl bg-slate-100 text-slate-500 hover:bg-slate-200 transition-colors">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-6 space-y-5">

                {{-- Zone de dépôt --}}
                <div class="border-2 border-dashed rounded-2xl text-center cursor-pointer transition-all"
                     :class="[dragging ? 'border-orange-400 bg-orange-50' : 'border-slate-200 hover:border-orange-300', items.length ? 'p-4' : 'p-10']"
                     @click="!running && $refs.files.click()"
                     @dragover.prevent.stop="dragging = true"
                     @dragleave.prevent.stop="dragging = false"
                     @drop.prevent.stop="dragging = false; addFromDataTransfer($event.dataTransfer)">
                    <input type="file" multiple x-ref="files" class="hidden"
                           accept="{{ \App\Support\FileType::acceptAttribute() }}"
                           @change="addFiles([...$event.target.files]); $event.target.value = ''">
                    <i class="fa-solid fa-cloud-arrow-up text-slate-300" :class="[items.length ? 'text-lg mr-2' : 'text-3xl mb-3 block', dragging && 'text-orange-500']"></i>
                    <span class="text-xs font-bold text-slate-500">
                        <span x-text="items.length ? 'Ajouter d\'autres fichiers' : 'Glissez vos fichiers ou dossiers ici, ou cliquez pour parcourir'"></span>
                    </span>
                    <p x-show="!items.length" class="text-[10px] text-slate-300 mt-1">Max {{ intdiv(config('ged.max_upload_kb'), 1024) }} Mo par fichier</p>
                </div>

                {{-- Paramètres communs --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" x-show="items.length">
                    <div>
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Dossier</label>
                        <select x-model="settings.category_id" :disabled="running"
                                class="w-full bg-slate-50 border border-slate-100 rounded-xl px-3 py-2.5 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-500">
                            <option value="">Sans dossier</option>
                            @foreach($fileableFolders ?? $categories as $cat)
                            <option value="{{ $cat->id }}">{{ str_repeat("\u{00A0}\u{00A0}\u{00A0}", $cat->depth ?? 0) }}{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Statut</label>
                        <p class="w-full bg-slate-50 border border-slate-100 rounded-xl px-3 py-2.5 text-xs font-bold text-slate-500">Brouillon — circuit de la catégorie lancé automatiquement</p>
                    </div>
                    <div>
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Mots-clés (séparés par des virgules)</label>
                        <input type="text" x-model="settings.tags" :disabled="running" placeholder="Ex : factures, 2026"
                               class="w-full bg-slate-50 border border-slate-100 rounded-xl px-3 py-2.5 text-xs font-medium text-slate-700 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                    <label class="flex items-center gap-2.5 bg-slate-50 border border-slate-100 rounded-xl px-3 py-2.5 sm:mt-5 cursor-pointer">
                        <input type="checkbox" x-model="settings.is_confidential" :disabled="running"
                               class="rounded border-slate-300 text-red-600 focus:ring-red-500">
                        <span class="text-xs font-bold text-slate-600"><i class="fa-solid fa-lock text-red-400 text-[10px] mr-1"></i>Confidentiels</span>
                    </label>
                </div>

                {{-- Liste des fichiers --}}
                <div x-show="items.length" class="border border-slate-100 rounded-2xl overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-2.5 bg-slate-50 border-b border-slate-100">
                        <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                            <span x-text="items.length"></span> fichier(s) · <span x-text="formatBytes(totalBytes)"></span>
                        </span>
                        <button type="button" x-show="!running && !finished" @click="items = []" class="text-[10px] font-bold text-slate-400 hover:text-red-500">Tout retirer</button>
                    </div>
                    <div class="divide-y divide-slate-50 max-h-[40vh] overflow-y-auto">
                        <template x-for="item in items" :key="item.id">
                            <div class="px-4 py-2.5" :class="item.state === 'invalid' || item.state === 'error' ? 'bg-red-50/40' : (item.state === 'duplicate' ? 'bg-amber-50/50' : '')">
                                <div class="flex items-center gap-3">
                                    <i class="fa-solid text-base shrink-0" :class="fileIcon(item.file.name)"></i>
                                    <div class="flex-1 min-w-0">
                                        <input type="text" x-model="item.title" :disabled="!['pending', 'invalid', 'error', 'duplicate'].includes(item.state) || running"
                                               class="w-full bg-transparent border-0 border-b border-transparent hover:border-slate-200 focus:border-orange-400 focus:ring-0 px-0 py-0.5 text-sm font-bold text-slate-800 disabled:text-slate-500">
                                        <p class="text-[10px] text-slate-400 truncate">
                                            <span x-text="item.path || item.file.name"></span> · <span x-text="formatBytes(item.file.size)"></span>
                                        </p>
                                    </div>

                                    {{-- État --}}
                                    <div class="shrink-0 text-right w-36">
                                        <template x-if="item.state === 'pending'">
                                            <span class="text-[10px] font-bold text-slate-400">En attente</span>
                                        </template>
                                        <template x-if="item.state === 'uploading'">
                                            <div>
                                                <span class="text-[10px] font-black text-orange-600" x-text="item.progress < 100 ? item.progress + ' %' : 'Traitement…'"></span>
                                                <div class="w-full bg-slate-100 rounded-full h-1.5 mt-1 overflow-hidden">
                                                    <div class="bg-orange-500 h-1.5 rounded-full transition-all duration-200" :style="'width: ' + item.progress + '%'"></div>
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="item.state === 'done'">
                                            <a :href="item.url" target="_blank" class="text-[10px] font-black text-emerald-600 hover:underline">
                                                <i class="fa-solid fa-circle-check mr-1"></i><span x-text="item.reference"></span>
                                            </a>
                                        </template>
                                        <template x-if="item.state === 'skipped'">
                                            <span class="text-[10px] font-bold text-slate-400">Ignoré</span>
                                        </template>
                                        <template x-if="item.state === 'invalid' || item.state === 'error'">
                                            <span class="text-[10px] font-black text-red-600"><i class="fa-solid fa-circle-exclamation mr-1"></i>Erreur</span>
                                        </template>
                                        <template x-if="item.state === 'duplicate'">
                                            <span class="text-[10px] font-black" :class="item.duplicate?.blocking ? 'text-red-600' : 'text-amber-600'">
                                                <i class="fa-solid fa-clone mr-1"></i><span x-text="item.duplicate?.blocking ? 'Existe déjà' : 'Doc. proche'"></span>
                                            </span>
                                        </template>
                                    </div>

                                    <button type="button" x-show="!running && ['pending', 'invalid', 'error', 'duplicate'].includes(item.state)" @click="remove(item)"
                                            class="w-6 h-6 flex items-center justify-center rounded-lg text-slate-300 hover:text-red-500 hover:bg-red-50 shrink-0" title="Retirer">
                                        <i class="fa-solid fa-xmark text-xs"></i>
                                    </button>
                                </div>

                                {{-- Détail erreur / doublon --}}
                                <p x-show="item.message" class="text-[10px] mt-1.5 ml-7"
                                   :class="item.state === 'error' ? 'text-red-600' : 'text-amber-700'" x-text="item.message"></p>
                                <div x-show="item.state === 'duplicate' && !running" class="flex items-center gap-3 mt-1.5 ml-7">
                                    <a x-show="item.duplicate?.url" :href="item.duplicate?.url" target="_blank" class="text-[10px] font-bold text-slate-500 hover:underline">Voir l'existant</a>
                                    {{-- Doublon avéré : seul un administrateur peut forcer ; ressemblance : chacun peut confirmer --}}
                                    <button type="button" x-show="!item.duplicate?.blocking || isAdmin"
                                            @click="item.allowDuplicate = true; item.state = 'pending'; item.message = ''" class="text-[10px] font-black text-amber-700 hover:underline">Importer quand même</button>
                                    <button type="button" @click="item.state = 'skipped'; item.message = ''" class="text-[10px] font-bold text-slate-400 hover:underline">Ignorer</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Pied : progression et actions --}}
            <div class="border-t border-slate-100 px-6 py-4 shrink-0 space-y-3" x-show="items.length">
                <div x-show="running || finished" class="space-y-1.5">
                    <div class="flex items-center justify-between text-[10px] font-bold">
                        <span class="text-slate-500">
                            <span x-text="counts.done"></span> importé(s)
                            <span x-show="counts.duplicate" class="text-amber-600">· <span x-text="counts.duplicate"></span> doublon(s)</span>
                            <span x-show="counts.failed" class="text-red-600">· <span x-text="counts.failed"></span> erreur(s)</span>
                        </span>
                        <span class="text-orange-600" x-text="overallProgress + ' %'"></span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                        <div class="bg-orange-500 h-2 rounded-full transition-all duration-300" :style="'width: ' + overallProgress + '%'"></div>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button type="button" x-show="running" @click="cancel()"
                        class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-600 py-3 rounded-xl font-bold text-xs uppercase tracking-wider transition-all">
                        Arrêter
                    </button>
                    <button type="button" x-show="!running" @click="close()"
                        class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-600 py-3 rounded-xl font-bold text-xs uppercase tracking-wider transition-all"
                        x-text="counts.done ? 'Fermer' : 'Annuler'"></button>
                    <button type="button" x-show="!running && toSend > 0" @click="start()"
                        class="flex-1 bg-orange-600 hover:bg-orange-500 active:scale-95 text-white py-3 rounded-xl font-black text-xs uppercase tracking-widest shadow-lg shadow-orange-200 transition-all">
                        <i class="fa-solid fa-cloud-arrow-up mr-1.5"></i>
                        <span x-text="'Importer ' + toSend + ' fichier' + (toSend > 1 ? 's' : '')"></span>
                    </button>
                    <button type="button" x-show="running" disabled
                        class="flex-1 bg-orange-600/70 text-white py-3 rounded-xl font-black text-xs uppercase tracking-widest cursor-wait">
                        <i class="fa-solid fa-spinner fa-spin mr-1.5"></i> Envoi en cours…
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function bulkImport(config) {
    const CONCURRENCY = 2;
    let nextId = 1;

    return {
        isAdmin: !!config.isAdmin,
        items: [],
        settings: { category_id: String(config.categoryId || ''), tags: '', is_confidential: false },
        running: false,
        cancelled: false,
        dragging: false,
        pageDragging: false,
        dragDepth: 0,
        active: [],

        get totalBytes() { return this.items.reduce((sum, i) => sum + i.file.size, 0); },
        get toSend() { return this.items.filter(i => i.state === 'pending').length; },
        get finished() { return !this.running && this.items.some(i => ['done', 'error', 'duplicate', 'skipped'].includes(i.state)) && this.toSend === 0; },
        get counts() {
            return {
                done: this.items.filter(i => i.state === 'done').length,
                duplicate: this.items.filter(i => i.state === 'duplicate').length,
                failed: this.items.filter(i => ['error', 'invalid'].includes(i.state)).length,
            };
        },
        // Progression pondérée par la taille des fichiers envoyés dans cette session
        get overallProgress() {
            const batch = this.items.filter(i => i.inBatch);
            const total = batch.reduce((s, i) => s + i.file.size, 0) || 1;
            const sent = batch.reduce((s, i) => s + i.file.size * (['done', 'error', 'duplicate', 'skipped'].includes(i.state) ? 1 : (i.progress || 0) / 100), 0);
            return Math.round(sent / total * 100);
        },

        // --- Ajout de fichiers ---
        addFiles(files, paths = []) {
            files.forEach((file, index) => {
                // Pas deux fois le même fichier dans la file
                if (this.items.some(i => i.file.name === file.name && i.file.size === file.size && i.file.lastModified === file.lastModified)) return;
                const item = {
                    id: nextId++, file, path: paths[index] || '', progress: 0,
                    title: file.name.replace(/\.[^.]+$/, '').replace(/[_]+/g, ' ').trim() || file.name,
                    state: 'pending', message: '', allowDuplicate: false, inBatch: false,
                };
                const ext = file.name.split('.').pop().toLowerCase();
                if (!config.extensions.includes(ext)) {
                    item.state = 'invalid';
                    item.message = 'Format .' + ext + ' non pris en charge.';
                } else if (file.size > config.maxBytes) {
                    item.state = 'invalid';
                    item.message = 'Fichier trop volumineux (max ' + this.formatBytes(config.maxBytes) + ').';
                } else if (file.size === 0) {
                    item.state = 'invalid';
                    item.message = 'Fichier vide.';
                }
                this.items.push(item);
            });
        },

        // Fichiers et dossiers déposés (parcours récursif des dossiers)
        async addFromDataTransfer(dataTransfer) {
            const entries = [...(dataTransfer.items || [])]
                .map(i => i.webkitGetAsEntry ? i.webkitGetAsEntry() : null)
                .filter(Boolean);
            if (!entries.length) {
                this.addFiles([...dataTransfer.files]);
                return;
            }
            const files = [], paths = [];
            const walk = async (entry, prefix) => {
                if (entry.isFile) {
                    const file = await new Promise((resolve, reject) => entry.file(resolve, reject));
                    // Fichiers système ignorés (.DS_Store, Thumbs.db…)
                    if (!/^(\.|thumbs\.db$|desktop\.ini$)/i.test(file.name)) {
                        files.push(file);
                        paths.push(prefix + file.name);
                    }
                } else if (entry.isDirectory) {
                    const reader = entry.createReader();
                    let batch;
                    do {
                        batch = await new Promise((resolve, reject) => reader.readEntries(resolve, reject));
                        for (const child of batch) await walk(child, prefix + entry.name + '/');
                    } while (batch.length);
                }
            };
            for (const entry of entries) await walk(entry, '');
            this.addFiles(files, paths);
        },

        remove(item) { this.items = this.items.filter(i => i !== item); },

        // --- Glisser-déposer sur toute la page ---
        hasFiles(e) { return [...(e.dataTransfer?.types || [])].includes('Files'); },
        onWindowDragEnter(e) { if (!this.hasFiles(e) || this.running) return; this.dragDepth++; this.pageDragging = !this.uploadModal; },
        onWindowDragOver(e) { if (this.hasFiles(e) && !this.uploadModal && !this.running) this.pageDragging = true; },
        onWindowDragLeave(e) { if (!this.hasFiles(e)) return; this.dragDepth = Math.max(0, this.dragDepth - 1); if (!this.dragDepth) this.pageDragging = false; },
        onWindowDrop(e) {
            const wasPage = this.pageDragging;
            this.pageDragging = false; this.dragDepth = 0;
            if (!wasPage || !this.hasFiles(e)) return;
            this.uploadModal = true;
            this.addFromDataTransfer(e.dataTransfer);
        },

        // --- Envoi ---
        start() {
            if (this.running) return;
            this.items.forEach(i => { i.inBatch = i.state === 'pending'; });
            this.running = true;
            this.cancelled = false;
            for (let n = 0; n < CONCURRENCY; n++) this.pump();
        },

        pump() {
            if (this.cancelled) return this.settle();
            const item = this.items.find(i => i.state === 'pending' && i.inBatch);
            if (!item) return this.settle();
            this.send(item).then(() => this.pump());
        },

        settle() {
            if (this.active.length === 0 && (this.cancelled || !this.items.some(i => i.state === 'pending' && i.inBatch))) {
                this.running = false;
            }
        },

        send(item) {
            return new Promise(resolve => {
                item.state = 'uploading';
                item.progress = 0;
                item.message = '';

                const fd = new FormData();
                fd.append('import_mode', '1');
                fd.append('title', (item.title || item.file.name).slice(0, 255));
                fd.append('import_file', item.file, item.file.name);
                fd.append('is_confidential', this.settings.is_confidential ? '1' : '0');
                if (this.settings.category_id) fd.append('category_id', this.settings.category_id);
                if (this.settings.tags.trim()) fd.append('tags', this.settings.tags.trim());
                if (item.allowDuplicate) fd.append('allow_duplicate', '1');

                const xhr = new XMLHttpRequest();
                this.active.push(xhr);
                const finish = () => { this.active = this.active.filter(x => x !== xhr); resolve(); };

                xhr.open('POST', config.url);
                xhr.timeout = 600000;
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name=csrf-token]').content);
                xhr.upload.onprogress = e => { if (e.lengthComputable) item.progress = Math.round(e.loaded / e.total * 100); };
                xhr.onload = () => {
                    let data = {};
                    try { data = JSON.parse(xhr.responseText || '{}'); } catch (e) {}
                    if (xhr.status === 201) {
                        // Circuit imposé mais impossible à lancer : signalé, jamais ignoré
                        Object.assign(item, { state: 'done', progress: 100, url: data.url, reference: data.reference, message: data.workflow || null });
                    } else if (xhr.status === 409 && data.duplicate) {
                        Object.assign(item, { state: 'duplicate', message: data.message, duplicate: data.duplicate });
                    } else {
                        item.state = 'error';
                        item.message = this.errorMessage(xhr.status, data);
                    }
                    finish();
                };
                xhr.onerror = () => { item.state = 'error'; item.message = 'Erreur réseau. Vérifiez votre connexion puis relancez.'; finish(); };
                xhr.ontimeout = () => { item.state = 'error'; item.message = 'Délai dépassé : l\'envoi a pris trop de temps.'; finish(); };
                xhr.onabort = () => { item.state = 'pending'; item.progress = 0; finish(); };
                xhr.send(fd);
            });
        },

        errorMessage(status, data) {
            if (status === 413) return 'Fichier trop volumineux pour le serveur.';
            if (status === 419) return 'Session expirée : rechargez la page puis relancez l\'import.';
            if (status === 403) return data.message || 'Vous n\'avez pas le droit d\'importer des documents.';
            if (data.errors) return Object.values(data.errors).flat()[0];
            return data.message || ('Erreur serveur (' + status + ').');
        },

        cancel() {
            this.cancelled = true;
            this.active.forEach(xhr => xhr.abort());
        },

        close() {
            if (this.running) {
                if (!confirm('Des fichiers sont en cours d\'envoi. Arrêter l\'import ?')) return;
                this.cancel();
            }
            const imported = this.counts.done > 0;
            this.uploadModal = false;
            // Les documents importés apparaissent dans la liste
            if (imported) {
                window.location.reload();
                return;
            }
            this.items = [];
        },

        formatBytes(bytes) {
            if (!bytes) return '0 o';
            const units = ['o', 'Ko', 'Mo', 'Go'];
            const i = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
            return (bytes / Math.pow(1024, i)).toFixed(i ? 1 : 0).replace('.', ',') + ' ' + units[i];
        },
    };
}
</script>
