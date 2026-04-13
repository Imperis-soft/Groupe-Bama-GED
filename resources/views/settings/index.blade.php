@extends('layouts.app')

@section('content')
<div class="space-y-5">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Configuration</h1>
            <p class="text-xs text-slate-400 font-medium mt-1">Paramètres globaux de la plateforme</p>
        </div>
        <div class="flex items-center gap-2 bg-white border border-slate-100 rounded-xl px-3 py-2 shadow-sm self-start sm:self-auto">
            <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Système actif</span>
        </div>
    </div>

    <form method="POST" action="{{ route('settings.update') }}" class="space-y-4">
        @csrf

        {{-- Identité --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-2 px-5 py-4 border-b border-slate-50">
                <div class="w-7 h-7 rounded-lg bg-orange-100 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-building text-orange-500 text-xs"></i>
                </div>
                <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Identité de la plateforme</h2>
            </div>
            <div class="p-5 space-y-5">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                    <div>
                        <p class="text-xs font-bold text-slate-700">Nom du site</p>
                        <p class="text-[10px] text-slate-400 mt-0.5 leading-relaxed">Label affiché sur l'interface et les emails.</p>
                    </div>
                    <div class="md:col-span-2">
                        <input type="text" name="app_name"
                               value="{{ $settings['app_name'] ?? config('app.name') }}"
                               placeholder="Groupe Bama"
                               class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-800 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all">
                    </div>
                </div>

                <div class="h-px bg-slate-50"></div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                    <div>
                        <p class="text-xs font-bold text-slate-700">URL de base</p>
                        <p class="text-[10px] text-slate-400 mt-0.5 leading-relaxed">Utilisée pour les liens WebDAV et les QR codes de vérification.</p>
                    </div>
                    <div class="md:col-span-2">
                        <div class="relative">
                            <i class="fa-solid fa-link absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                            <input type="text" name="app_url"
                                   value="{{ $settings['app_url'] ?? config('app.url') }}"
                                   placeholder="https://exemple.com"
                                   class="w-full bg-slate-50 border border-slate-100 rounded-xl pl-9 pr-4 py-2.5 text-sm font-mono font-bold text-orange-500 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all">
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- Stockage --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-2 px-5 py-4 border-b border-slate-50">
                <div class="w-7 h-7 rounded-lg bg-blue-100 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-server text-blue-500 text-xs"></i>
                </div>
                <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Stockage MinIO</h2>
            </div>
            <div class="p-5 space-y-5">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                    <div>
                        <p class="text-xs font-bold text-slate-700">Endpoint</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">URL du serveur MinIO/S3.</p>
                    </div>
                    <div class="md:col-span-2">
                        <input type="text" name="minio_endpoint"
                               value="{{ $settings['minio_endpoint'] ?? config('filesystems.disks.s3.endpoint') }}"
                               placeholder="https://api.storage.exemple.com"
                               class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-sm font-mono font-medium text-slate-700 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                    </div>
                </div>

                <div class="h-px bg-slate-50"></div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                    <div>
                        <p class="text-xs font-bold text-slate-700">Bucket</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">Nom du bucket de stockage.</p>
                    </div>
                    <div class="md:col-span-2">
                        <input type="text" name="minio_bucket"
                               value="{{ $settings['minio_bucket'] ?? config('filesystems.disks.s3.bucket') }}"
                               placeholder="groupebama"
                               class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-sm font-mono font-medium text-slate-700 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                    </div>
                </div>

            </div>
        </div>

        {{-- Infos système --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-2 px-5 py-4 border-b border-slate-50">
                <div class="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-circle-info text-slate-500 text-xs"></i>
                </div>
                <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Informations système</h2>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="bg-slate-50 rounded-xl p-3 text-center">
                        <p class="text-lg font-black text-slate-900">{{ PHP_MAJOR_VERSION }}.{{ PHP_MINOR_VERSION }}</p>
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-wider mt-0.5">PHP</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3 text-center">
                        <p class="text-lg font-black text-slate-900">{{ app()->version() }}</p>
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-wider mt-0.5">Laravel</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3 text-center">
                        <p class="text-lg font-black text-slate-900">{{ strtoupper(config('database.default')) }}</p>
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-wider mt-0.5">Base de données</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3 text-center">
                        <p class="text-lg font-black text-slate-900">{{ strtoupper(config('app.env')) }}</p>
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-wider mt-0.5">Environnement</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- CTA --}}
        <div class="bg-slate-900 rounded-2xl p-5 text-white shadow-xl shadow-slate-200 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-orange-600/20 rounded-full -translate-y-8 translate-x-8"></div>
            <div class="absolute bottom-0 left-0 w-16 h-16 bg-orange-600/10 rounded-full translate-y-6 -translate-x-4"></div>
            <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-orange-600 flex items-center justify-center shrink-0 shadow-lg shadow-orange-900/30">
                        <i class="fa-solid fa-cloud-arrow-up text-sm"></i>
                    </div>
                    <div>
                        <p class="text-sm font-black leading-tight">Appliquer les modifications</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">Les changements prennent effet immédiatement.</p>
                    </div>
                </div>
                <button type="submit"
                    class="inline-flex items-center justify-center gap-2 bg-white hover:bg-orange-600 hover:text-white text-slate-900 text-xs font-black uppercase tracking-widest px-6 py-3 rounded-xl transition-all active:scale-95 shrink-0">
                    <i class="fa-solid fa-floppy-disk text-[10px]"></i> Enregistrer
                </button>
            </div>
        </div>

    </form>

    {{-- Footer --}}
    <div class="flex items-center justify-center gap-4 py-2 opacity-40">
        <span class="h-px bg-slate-200 flex-1"></span>
        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Solution gérée par Imperis Sarl</p>
        <span class="h-px bg-slate-200 flex-1"></span>
    </div>

</div>
@endsection
