{{-- Stockage MinIO de l'entreprise (création / édition).
     Variables Alpine du formulaire parent : bucket, bucketTouched, dedicated --}}
@php
    $organization ??= null;
    $bucketLocked ??= false;
    $platformEndpoint = config('filesystems.disks.s3.endpoint') ?: config('filesystems.disks.s3.url');
@endphp
<div class="space-y-5">
    <div class="flex gap-3 rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm text-slate-700">
        <i class="fa-solid fa-folder-closed mt-0.5 text-orange-500"></i>
        <p>
            @if($organization && $organization->hasDedicatedBucket())
                Cette entreprise utilise son propre bucket <span class="font-mono">{{ $organization->storage_bucket }}</span>.
            @elseif($organization)
                Les fichiers de cette entreprise sont rangés dans son dossier privé
                <span class="font-mono">{{ config('filesystems.disks.s3.bucket') }}/{{ $organization->storagePrefix() }}/</span>.
                @if($bucketLocked) Elle a déjà des documents : son emplacement ne peut plus être changé. @endif
            @else
                Par défaut, un dossier privé est créé pour l'entreprise dans le bucket
                <span class="font-mono">{{ config('filesystems.disks.s3.bucket') }}</span> (ex. <span class="font-mono">{{ config('filesystems.disks.s3.bucket') }}/nom-entreprise/</span>).
                Ne renseignez un bucket que pour lui donner un bucket séparé.
            @endif
        </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label class="label">Bucket séparé <span class="text-slate-400 font-normal">(facultatif)</span></label>
            <input type="text" name="storage_bucket" x-model="bucket" @input="bucketTouched = true"
                   maxlength="63" class="field font-mono lowercase" placeholder="ged-mon-entreprise" autocomplete="off"
                   pattern="[a-z0-9][a-z0-9.\-]{1,61}[a-z0-9]" title="3 à 63 caractères : minuscules, chiffres, points et tirets"
                   @if($bucketLocked) readonly @endif
                   :required="dedicated">
            <p class="hint">
                @if($bucketLocked)
                    <i class="fa-solid fa-lock text-[11px]"></i> Verrouillé : l'entreprise a déjà des documents dans ce bucket.
                @else
                    Vide = dossier privé dans le bucket partagé (recommandé). Obligatoire avec un serveur dédié.
                @endif
            </p>
        </div>
        <div class="flex items-end">
            <label class="flex items-start gap-3 cursor-pointer pb-2">
                <input type="hidden" name="storage_create_bucket" value="0">
                <input type="checkbox" name="storage_create_bucket" value="1" class="check mt-0.5" @checked(old('storage_create_bucket', true))>
                <span>
                    <span class="block text-sm font-semibold text-slate-900">Créer le bucket s'il n'existe pas</span>
                    <span class="block text-xs text-slate-500 mt-0.5">Sinon, le bucket doit déjà exister sur MinIO.</span>
                </span>
            </label>
        </div>
    </div>

    <label class="relative flex items-start gap-3 p-4 rounded-xl border cursor-pointer transition"
           :class="dedicated ? 'border-orange-400 bg-orange-50/50 ring-4 ring-orange-500/10' : 'border-slate-200 hover:border-slate-300'">
        <input type="hidden" name="storage_dedicated" value="0">
        <input type="checkbox" name="storage_dedicated" value="1" x-model="dedicated" class="check mt-0.5">
        <span>
            <span class="block text-sm font-bold text-slate-900">Serveur MinIO dédié</span>
            <span class="block text-xs text-slate-500 mt-0.5">
                Décoché : les fichiers sont stockés sur le serveur de la plateforme
                @if($platformEndpoint)(<span class="font-mono">{{ $platformEndpoint }}</span>)@endif.
            </span>
        </span>
    </label>

    <div x-show="dedicated" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div class="sm:col-span-2">
            <label class="label">Adresse du serveur (endpoint) <span class="text-orange-600">*</span></label>
            <input type="url" name="storage_endpoint" value="{{ old('storage_endpoint', $organization->storage_endpoint ?? '') }}" class="field font-mono" placeholder="https://minio.entreprise.com" :required="dedicated">
        </div>
        <div>
            <label class="label">Clé d'accès (access key) <span class="text-orange-600">*</span></label>
            <input type="text" name="storage_key" value="{{ old('storage_key', $organization->storage_key ?? '') }}" class="field font-mono" autocomplete="off" :required="dedicated">
        </div>
        <div>
            <label class="label">Clé secrète (secret key) @unless($organization?->hasDedicatedServer())<span class="text-orange-600">*</span>@endunless</label>
            <input type="password" name="storage_secret" class="field font-mono" autocomplete="new-password"
                   placeholder="{{ $organization?->hasDedicatedServer() ? '•••••••• (inchangée)' : '' }}"
                   @unless($organization?->hasDedicatedServer()) :required="dedicated" @endunless>
            @if($organization?->hasDedicatedServer())
                <p class="hint">Laissez vide pour conserver la clé actuelle.</p>
            @endif
        </div>
    </div>

    <details class="group" @if(old('storage_url', $organization->storage_url ?? null) || old('storage_region', $organization->storage_region ?? null)) open @endif>
        <summary class="cursor-pointer text-sm font-semibold text-slate-600 hover:text-slate-900 select-none">
            <i class="fa-solid fa-chevron-right text-[10px] transition group-open:rotate-90"></i> Options avancées
        </summary>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mt-4">
            <div>
                <label class="label">URL publique</label>
                <input type="url" name="storage_url" value="{{ old('storage_url', $organization->storage_url ?? '') }}" class="field font-mono" placeholder="Par défaut : l'adresse du serveur">
            </div>
            <div>
                <label class="label">Région</label>
                <input type="text" name="storage_region" value="{{ old('storage_region', $organization->storage_region ?? '') }}" class="field font-mono" placeholder="{{ config('filesystems.disks.s3.region') }}">
            </div>
        </div>
    </details>
</div>
