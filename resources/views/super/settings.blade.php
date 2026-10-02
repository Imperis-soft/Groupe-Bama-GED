@extends('layouts.super')
{{-- Paramètres de la plateforme ($organization null) ou configuration d'une entreprise --}}
@php
    $forOrg = (bool) $organization;
    $platformSettings ??= [];
    $platformHost = $platformSettings['mail_host'] ?? '';
    $platformMailOn = ($platformSettings['mail_enabled'] ?? '0') === '1' && $platformHost !== '';
    $defaultLock = $platformSettings['lock_timeout_min'] ?? '30';
@endphp
@section('title', $forOrg ? 'Configuration — ' . $organization->name : 'Paramètres')

@section('header')
@if($forOrg)
    <a href="{{ route('super.organizations.show', $organization) }}" class="back-link"><i class="fa-solid fa-arrow-left text-xs"></i> {{ $organization->name }}</a>
@endif
<div class="page-header">
    <div>
        @if($forOrg)
            <p class="eyebrow mb-3"><i class="fa-solid fa-sliders"></i> Entreprise</p>
            <h1 class="page-title">Configuration de {{ $organization->name }}</h1>
            <p class="page-subtitle">Réglages propres à cette entreprise. Les champs laissés vides reprennent les paramètres de la plateforme.</p>
        @else
            <p class="eyebrow mb-3"><i class="fa-solid fa-gear"></i> Administration</p>
            <h1 class="page-title">Paramètres de la plateforme</h1>
            <p class="page-subtitle">Configuration globale de {{ config('saas.platform_name') }} et valeurs par défaut des entreprises.</p>
        @endif
    </div>
</div>
@endsection

@section('content')

<form method="POST" action="{{ $forOrg ? route('super.organizations.settings.update', $organization) : route('super.settings.update') }}"
      class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start"
      x-data="{
          enabled: @js(($settings['mail_enabled'] ?? '0') === '1'),
          host: @js($settings['mail_host'] ?? ''),
          port: @js($settings['mail_port'] ?? '587'),
          encryption: @js(in_array($settings['mail_encryption'] ?? 'tls', ['tls', 'ssl'], true) ? ($settings['mail_encryption'] ?? 'tls') : ''),
          fromAddress: @js($settings['mail_from_address'] ?? ''),
          fromName: @js($settings['mail_from_name'] ?? ''),
          lock: @js($settings['lock_timeout_min'] ?? ''),
      }">
    @csrf

    {{-- ======= Formulaire ======= --}}
    <div class="lg:col-span-2 space-y-6">
        <section class="card">
            <div class="card-header">
                <div class="flex items-center gap-4">
                    <span class="section-icon"><i class="fa-solid fa-server text-sm"></i></span>
                    <div>
                        <h2 class="card-title">{{ $forOrg ? 'Serveur d\'envoi dédié' : 'Serveur d\'envoi (SMTP)' }}</h2>
                        <p class="card-subtitle">{{ $forOrg ? 'Facultatif : sinon le serveur de la plateforme est utilisé' : 'Connexion au serveur de messagerie' }}</p>
                    </div>
                </div>
                {{-- Interrupteur --}}
                <label class="inline-flex items-center gap-3 h-10 pl-4 pr-2 rounded-full border cursor-pointer select-none transition"
                       :class="enabled ? 'border-orange-200 bg-orange-50/60' : 'border-slate-200 bg-white'">
                    <span class="text-[13px] font-semibold" :class="enabled ? 'text-orange-700' : 'text-slate-500'"
                          x-text="enabled ? @js($forOrg ? 'SMTP dédié actif' : 'Envoi activé') : @js($forOrg ? 'SMTP dédié inactif' : 'Envoi désactivé')"></span>
                    <input type="checkbox" name="mail_enabled" value="1" x-model="enabled" class="sr-only peer">
                    <span class="relative w-11 h-6 rounded-full transition peer-focus-visible:ring-4 peer-focus-visible:ring-orange-500/20" :class="enabled ? 'bg-orange-600' : 'bg-slate-200'">
                        <span class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow transition" :class="enabled ? 'translate-x-5' : ''"></span>
                    </span>
                </label>
            </div>

            <div class="card-body space-y-5">
                @if($forOrg)
                    <div x-show="!enabled" x-cloak class="flex gap-3 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600">
                        <i class="fa-solid fa-circle-info text-slate-400 mt-0.5"></i>
                        <p>Les emails de cette entreprise partent par le serveur de la plateforme
                            @if($platformMailOn)(<span class="font-mono text-[13px]">{{ $platformHost }}</span>).@else— <a href="{{ route('super.settings.index') }}" class="link">qui n'est pas encore configuré</a>.@endif
                        </p>
                    </div>
                @else
                    <div x-show="!enabled" x-cloak class="flex gap-3 rounded-xl border border-amber-200 bg-amber-50/70 px-4 py-3 text-sm text-amber-800">
                        <i class="fa-solid fa-triangle-exclamation text-amber-500 mt-0.5"></i>
                        <p>L'envoi est désactivé : aucun email système ne partira (mots de passe oubliés, rappels d'abonnement…). Vous pouvez quand même préparer la configuration.</p>
                    </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-6 gap-5">
                    <div class="sm:col-span-4">
                        <label class="label">Serveur SMTP</label>
                        <input type="text" name="mail_host" x-model="host" placeholder="smtp.exemple.com" class="field font-mono text-[13px]">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Port</label>
                        <input type="number" name="mail_port" x-model="port" class="field tabular-nums">
                    </div>
                    <div class="sm:col-span-6">
                        <label class="label">Chiffrement</label>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach(['tls' => ['TLS', 'Port 587, recommandé'], 'ssl' => ['SSL', 'Port 465'], '' => ['Aucun', 'Déconseillé']] as $value => [$label, $hint])
                                <label class="flex flex-col gap-0.5 rounded-xl border px-4 py-3 cursor-pointer transition"
                                       :class="encryption === @js($value) ? 'border-orange-400 bg-orange-50/50 ring-4 ring-orange-500/10' : 'border-slate-200 hover:border-slate-300'">
                                    <input type="radio" name="mail_encryption" value="{{ $value }}" x-model="encryption" class="sr-only">
                                    <span class="text-sm font-bold text-slate-900">{{ $label }}</span>
                                    <span class="text-xs text-slate-500">{{ $hint }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="sm:col-span-3">
                        <label class="label">Utilisateur</label>
                        <input type="text" name="mail_username" value="{{ $settings['mail_username'] ?? '' }}" class="field" autocomplete="off">
                    </div>
                    <div class="sm:col-span-3" x-data="{ show: false }">
                        <label class="label">Mot de passe</label>
                        <div class="relative">
                            <input :type="show ? 'text' : 'password'" name="mail_password" value="" autocomplete="new-password"
                                   placeholder="{{ $settings['has_mail_password'] ? '••••••••' : '' }}" class="field !pr-11">
                            <button type="button" @click="show = !show" class="absolute right-1 top-1/2 -translate-y-1/2 btn btn-ghost btn-sm btn-icon" :title="show ? 'Masquer' : 'Afficher'">
                                <i class="fa-regular" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                        @if($settings['has_mail_password'])
                            <p class="hint">Laisser vide pour conserver le mot de passe enregistré.</p>
                        @endif
                    </div>
                </div>

                <div class="pt-5 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="label">Email expéditeur</label>
                        <input type="email" name="mail_from_address" x-model="fromAddress" placeholder="{{ $forOrg ? ($organization->email ?: 'contact@entreprise.com') : config('saas.support_email') }}" class="field">
                    </div>
                    <div>
                        <label class="label">Nom expéditeur</label>
                        <input type="text" name="mail_from_name" x-model="fromName" placeholder="{{ $forOrg ? $organization->name : config('saas.platform_name') }}" class="field">
                    </div>
                </div>
            </div>
        </section>

        <section class="card">
            <div class="card-header justify-start">
                <span class="section-icon"><i class="fa-solid fa-file-lines text-sm"></i></span>
                <div>
                    <h2 class="card-title">Documents</h2>
                    <p class="card-subtitle">{{ $forOrg ? 'Laisser vide pour reprendre la valeur de la plateforme' : 'Valeurs par défaut appliquées à toutes les entreprises' }}</p>
                </div>
            </div>
            <div class="card-body grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="label">Verrouillage d'un document en édition</label>
                    <div class="relative">
                        <input type="number" name="lock_timeout_min" x-model="lock" min="1" max="1440"
                               placeholder="{{ $forOrg ? $defaultLock : '30' }}" class="field tabular-nums !pr-20">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm text-slate-400 pointer-events-none">minutes</span>
                    </div>
                    <p class="hint">Durée après laquelle un document verrouillé est libéré automatiquement{{ $forOrg ? ' (plateforme : ' . $defaultLock . ' min)' : '' }}.</p>
                </div>
            </div>
        </section>

        <div class="flex justify-end gap-3">
            <a href="{{ $forOrg ? route('super.organizations.show', $organization) : route('super.settings.index') }}" class="btn btn-light">Annuler</a>
            <button class="btn btn-primary"><i class="fa-solid fa-check"></i> Enregistrer</button>
        </div>
    </div>

    {{-- ======= Panneau latéral ======= --}}
    <aside class="space-y-6 lg:sticky lg:top-24">
        <div class="card p-6">
            <p class="kicker mb-4">{{ $forOrg ? 'Envoi des emails' : 'État du service' }}</p>
            @if($forOrg)
                <div class="flex items-center gap-3">
                    <span class="w-11 h-11 rounded-xl flex items-center justify-center"
                          :class="enabled ? 'bg-orange-50 text-orange-600 ring-1 ring-orange-100' : 'bg-slate-100 text-slate-500'">
                        <i class="fa-solid" :class="enabled ? 'fa-building' : 'fa-tower-broadcast'"></i>
                    </span>
                    <div>
                        <p class="text-[15px] font-bold text-slate-900" x-text="enabled ? 'Serveur dédié' : 'Serveur de la plateforme'"></p>
                        <p class="text-xs text-slate-500" x-text="enabled ? 'Configuré pour cette entreprise' : @js($platformMailOn ? 'Hérité de la plateforme' : 'Plateforme non configurée')"></p>
                    </div>
                </div>
            @else
                <div class="flex items-center gap-3">
                    <span class="w-11 h-11 rounded-xl flex items-center justify-center"
                          :class="enabled ? 'bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100' : 'bg-slate-100 text-slate-400'">
                        <i class="fa-solid" :class="enabled ? 'fa-circle-check' : 'fa-circle-pause'"></i>
                    </span>
                    <div>
                        <p class="text-[15px] font-bold text-slate-900" x-text="enabled ? 'Emails actifs' : 'Emails en pause'"></p>
                        <p class="text-xs text-slate-500" x-text="enabled ? 'Les emails système sont envoyés' : 'Aucun email n\'est envoyé'"></p>
                    </div>
                </div>
            @endif

            <dl class="mt-5">
                <div class="kv">
                    <dt>Serveur</dt>
                    <dd class="font-mono text-[13px] truncate max-w-[60%]"
                        x-text="{{ $forOrg ? 'enabled' : 'true' }} ? (host ? host + ':' + port : '—') : @js($platformHost ?: '—')"></dd>
                </div>
                <div class="kv">
                    <dt>Expéditeur</dt>
                    <dd class="truncate max-w-[60%]" x-text="fromAddress || @js($forOrg ? ($platformSettings['mail_from_address'] ?? config('saas.support_email')) : config('saas.support_email'))"></dd>
                </div>
                <div class="kv">
                    <dt>Verrouillage</dt>
                    <dd x-text="(lock || @js($forOrg ? $defaultLock : '30')) + ' min'"></dd>
                </div>
            </dl>
        </div>

        @if($forOrg)
            <div class="card p-6">
                <p class="kicker mb-4">Raccourcis</p>
                <div class="space-y-2">
                    <a href="{{ route('super.organizations.edit', $organization) }}" class="btn btn-light w-full !justify-start"><i class="fa-solid fa-pen"></i> Modifier la fiche</a>
                    <a href="{{ route('super.settings.index') }}" class="btn btn-light w-full !justify-start"><i class="fa-solid fa-gear"></i> Paramètres de la plateforme</a>
                </div>
            </div>
        @else
            <div class="card p-6">
                <p class="kicker mb-4">Utilisé pour</p>
                <ul class="space-y-3.5 text-sm text-slate-600">
                    <li class="flex gap-3"><i class="fa-solid fa-key text-orange-500 text-xs mt-1 w-4 text-center"></i> Les mots de passe oubliés</li>
                    <li class="flex gap-3"><i class="fa-solid fa-bell text-orange-500 text-xs mt-1 w-4 text-center"></i> Les rappels d'abonnement</li>
                    <li class="flex gap-3"><i class="fa-solid fa-building text-orange-500 text-xs mt-1 w-4 text-center"></i> Les entreprises sans configuration propre (modifiable depuis chaque fiche entreprise)</li>
                </ul>
            </div>
        @endif
    </aside>
</form>
@endsection
