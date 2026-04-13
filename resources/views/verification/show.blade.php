@extends('layouts.guest')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100">
    <!-- Header professionnel -->
    <div class="bg-white shadow-lg border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4 sm:py-6">
                <div class="flex items-center space-x-3 sm:space-x-4">
                    <div class="bg-gradient-to-r from-orange-500 to-red-600 p-2 sm:p-3 rounded-xl shadow-lg">
                        <i class="fas fa-shield-alt text-white text-xl sm:text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-slate-800">GROUPE BAMA</h1>
                        <p class="text-xs sm:text-sm text-slate-600">Système de Vérification Documentaire</p>
                    </div>
                </div>
                <div class="text-right hidden sm:block">
                    <p class="text-xs text-slate-500">Vérification #{{ substr($verification->verification_code ?? 'N/A', 0, 8) }}</p>
                    <p class="text-xs text-slate-400">{{ now()->format('d/m/Y H:i') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-16">
        @if($verification && $verification->document)
            <!-- DOCUMENT AUTHENTIQUE -->
            <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden">
                <!-- Header de succès -->
                <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 sm:px-8 py-4 sm:py-6">
                    <div class="flex items-center justify-center space-x-4">
                        <div class="bg-white bg-opacity-20 p-3 rounded-full">
                            <i class="fas fa-check-circle text-white text-3xl sm:text-4xl"></i>
                        </div>
                        <div class="text-center">
                            <h1 class="text-2xl sm:text-3xl font-bold text-white mb-1">DOCUMENT AUTHENTIQUE</h1>
                            <p class="text-green-100 text-sm sm:text-base">Ce document a été vérifié avec succès</p>
                        </div>
                    </div>
                </div>

                <!-- Contenu du document -->
                <div class="p-6 sm:p-8">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8">

                        <!-- Informations principales -->
                        <div class="space-y-4 sm:space-y-6">
                            <div class="bg-slate-50 rounded-xl p-4 sm:p-6 border border-slate-200">
                                <div class="flex items-center space-x-3 mb-4">
                                    <div class="bg-blue-100 p-2 rounded-lg">
                                        <i class="fas fa-file-alt text-blue-600 text-lg"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-slate-800 text-lg">Informations du Document</h3>
                                        <p class="text-slate-600 text-sm">Détails de vérification</p>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <div class="flex justify-between items-center py-2 border-b border-slate-200">
                                        <span class="text-slate-600 font-medium">Référence:</span>
                                        <span class="text-slate-800 font-bold">{{ $verification->document->reference }}</span>
                                    </div>

                                    <div class="flex justify-between items-center py-2 border-b border-slate-200">
                                        <span class="text-slate-600 font-medium">Titre:</span>
                                        <span class="text-slate-800">{{ Str::limit($verification->document->title, 30) }}</span>
                                    </div>

                                    <div class="flex justify-between items-center py-2 border-b border-slate-200">
                                        <span class="text-slate-600 font-medium">Créé par:</span>
                                        <span class="text-slate-800">{{ $verification->document->creator->full_name }}</span>
                                    </div>

                                    <div class="flex justify-between items-center py-2 border-b border-slate-200">
                                        <span class="text-slate-600 font-medium">Date de création:</span>
                                        <span class="text-slate-800">{{ $verification->document->created_at->format('d/m/Y') }}</span>
                                    </div>

                                    <div class="flex justify-between items-center py-2">
                                        <span class="text-slate-600 font-medium">Statut:</span>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                            @if($verification->document->status === 'approved') bg-green-100 text-green-800
                                            @elseif($verification->document->status === 'review') bg-yellow-100 text-yellow-800
                                            @elseif($verification->document->status === 'archived') bg-slate-100 text-slate-800
                                            @else bg-blue-100 text-blue-800 @endif">
                                            @if($verification->document->status === 'approved') Approuvé
                                            @elseif($verification->document->status === 'review') En révision
                                            @elseif($verification->document->status === 'archived') Archivé
                                            @else Brouillon @endif
                                        </span>
                                    </div>
                                </div>
                            </div>

                            @if($verification->document->category)
                            <div class="bg-slate-50 rounded-xl p-4 sm:p-6 border border-slate-200">
                                <div class="flex items-center space-x-3 mb-4">
                                    <div class="bg-purple-100 p-2 rounded-lg">
                                        <i class="fas fa-folder text-purple-600 text-lg"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-slate-800 text-lg">Catégorie</h3>
                                        <p class="text-slate-600 text-sm">Classification du document</p>
                                    </div>
                                </div>
                                <p class="text-slate-800 font-semibold text-center">{{ $verification->document->category->name }}</p>
                            </div>
                            @endif
                        </div>

                        <!-- Informations de sécurité -->
                        <div class="space-y-4 sm:space-y-6">
                            <div class="bg-slate-50 rounded-xl p-4 sm:p-6 border border-slate-200">
                                <div class="flex items-center space-x-3 mb-4">
                                    <div class="bg-green-100 p-2 rounded-lg">
                                        <i class="fas fa-shield-alt text-green-600 text-lg"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-slate-800 text-lg">Sécurité & Authentification</h3>
                                        <p class="text-slate-600 text-sm">Informations de vérification</p>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <div class="flex justify-between items-center py-2 border-b border-slate-200">
                                        <span class="text-slate-600 font-medium">Code de vérification:</span>
                                        <span class="text-slate-800 font-mono text-sm">{{ substr($verification->verification_code, 0, 12) }}...</span>
                                    </div>

                                    <div class="flex justify-between items-center py-2 border-b border-slate-200">
                                        <span class="text-slate-600 font-medium">Vérifié le:</span>
                                        <span class="text-slate-800">{{ now()->format('d/m/Y H:i') }}</span>
                                    </div>

                                    <div class="flex justify-between items-center py-2">
                                        <span class="text-slate-600 font-medium">Adresse IP:</span>
                                        <span class="text-slate-800 font-mono text-sm">{{ request()->ip() }}</span>
                                    </div>
                                </div>
                            </div>

                            @if($verification->document->is_confidential)
                            <div class="bg-gradient-to-r from-red-50 to-pink-50 border border-red-200 rounded-xl p-4 sm:p-6">
                                <div class="flex items-center space-x-3 mb-3">
                                    <div class="bg-red-100 p-2 rounded-lg">
                                        <i class="fas fa-lock text-red-600"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-red-800 text-lg">Document Confidentiel</h3>
                                        <p class="text-red-600 text-sm">Accès restreint - Ne pas partager</p>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- QR Code pour revérification -->
                            <div class="bg-slate-50 rounded-xl p-4 sm:p-6 border border-slate-200">
                                <div class="flex items-center space-x-3 mb-4">
                                    <div class="bg-blue-100 p-2 rounded-lg">
                                        <i class="fas fa-qrcode text-blue-600 text-lg"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-slate-800 text-lg">Code QR</h3>
                                        <p class="text-slate-600 text-sm">Pour vérification future</p>
                                    </div>
                                </div>
                                <div class="flex justify-center">
                                    <div class="bg-white p-4 rounded-lg border-2 border-slate-300">
                                        {!! QrCode::size(120)->style('round')->eye('circle')->generate(route('verification.show', $verification->verification_code)) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <!-- DOCUMENT FALSIFIÉ -->
            <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden">
                <!-- Header d'erreur -->
                <div class="bg-gradient-to-r from-red-500 to-pink-600 px-6 sm:px-8 py-4 sm:py-6">
                    <div class="flex items-center justify-center space-x-4">
                        <div class="bg-white bg-opacity-20 p-3 rounded-full">
                            <i class="fas fa-times-circle text-white text-3xl sm:text-4xl"></i>
                        </div>
                        <div class="text-center">
                            <h1 class="text-2xl sm:text-3xl font-bold text-white mb-1">DOCUMENT FALSIFIÉ</h1>
                            <p class="text-red-100 text-sm sm:text-base">Ce document n'est pas authentique</p>
                        </div>
                    </div>
                </div>

                <!-- Contenu d'erreur -->
                <div class="p-6 sm:p-8">
                    <div class="text-center">
                        <div class="w-20 h-20 sm:w-24 sm:h-24 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-exclamation-triangle text-3xl sm:text-4xl text-red-600"></i>
                        </div>

                        <h2 class="text-xl sm:text-2xl font-bold text-slate-800 mb-4">Alerte de Sécurité</h2>

                        <div class="bg-red-50 border border-red-200 rounded-xl p-4 sm:p-6 mb-6">
                            <div class="flex items-start space-x-3">
                                <div class="bg-red-100 p-2 rounded-lg flex-shrink-0">
                                    <i class="fas fa-shield-alt text-red-600 text-lg"></i>
                                </div>
                                <div class="text-left">
                                    <h3 class="font-bold text-red-800 mb-2">Document Non Authentique</h3>
                                    <p class="text-red-700 text-sm sm:text-base leading-relaxed">
                                        Ce code de vérification ne correspond à aucun document enregistré dans notre système.
                                        Ce document pourrait être falsifié ou le code de vérification pourrait être incorrect.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                            <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                                <div class="flex items-center space-x-2 mb-2">
                                    <i class="fas fa-clock text-slate-500"></i>
                                    <span class="text-xs font-semibold text-slate-600 uppercase tracking-wide">Tentative de vérification</span>
                                </div>
                                <p class="text-lg font-bold text-slate-800">{{ now()->format('d/m/Y') }}</p>
                                <p class="text-sm text-slate-600">{{ now()->format('H:i:s') }}</p>
                            </div>

                            <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                                <div class="flex items-center space-x-2 mb-2">
                                    <i class="fas fa-globe text-slate-500"></i>
                                    <span class="text-xs font-semibold text-slate-600 uppercase tracking-wide">Adresse IP</span>
                                </div>
                                <p class="text-sm font-mono text-slate-800">{{ request()->ip() }}</p>
                            </div>
                        </div>

                        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 sm:p-6">
                            <div class="flex items-start space-x-3">
                                <div class="bg-yellow-100 p-2 rounded-lg flex-shrink-0">
                                    <i class="fas fa-info-circle text-yellow-600 text-lg"></i>
                                </div>
                                <div class="text-left">
                                    <h3 class="font-bold text-yellow-800 mb-2">Que faire maintenant ?</h3>
                                    <ul class="text-yellow-700 text-sm space-y-1">
                                        <li>• Vérifiez que le code QR est bien scanné depuis le document original</li>
                                        <li>• Contactez l'émetteur du document pour obtenir un nouveau code</li>
                                        <li>• Signalez ce document suspect aux autorités compétentes</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Footer -->
        <div class="mt-8 sm:mt-12 text-center">
            <div class="bg-white rounded-xl shadow-lg border border-slate-200 p-4 sm:p-6">
                <div class="flex flex-col sm:flex-row items-center justify-center space-y-2 sm:space-y-0 sm:space-x-4 text-slate-600">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-shield-alt text-green-500"></i>
                        <span class="text-xs sm:text-sm">Sécurisé</span>
                    </div>
                    <div class="hidden sm:block w-px h-4 bg-slate-300"></div>
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-clock text-blue-500"></i>
                        <span class="text-xs sm:text-sm">Temps réel</span>
                    </div>
                    <div class="hidden sm:block w-px h-4 bg-slate-300"></div>
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-check-circle text-emerald-500"></i>
                        <span class="text-xs sm:text-sm">Certifié</span>
                    </div>
                </div>
                <p class="text-xs text-slate-500 mt-3 sm:mt-4">
                    © 2026 Groupe Bama - Tous droits réservés. Système de vérification documentaire sécurisé.
                </p>
            </div>
        </div>
    </div>
</div>

<style>
/* Animations personnalisées */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-in-up {
    animation: fadeInUp 0.6s ease-out;
}

/* Style pour le QR code */
.qr-code {
    filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
}

/* Hover effects */
.card-hover {
    transition: all 0.3s ease;
}

.card-hover:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

/* Responsive QR code */
@media (max-width: 640px) {
    .qr-code svg {
        width: 180px !important;
        height: 180px !important;
    }
}
</style>
@endsection