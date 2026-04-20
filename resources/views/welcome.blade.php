<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Groupe Bama — GED Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@200;400;600;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass { background: rgba(255,255,255,0.80); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); }
        .grid-pattern {
            background-image: radial-gradient(circle at 1px 1px, #e5e7eb 1px, transparent 0);
            background-size: 32px 32px;
        }
        .feature-card { transition: transform .25s ease, box-shadow .25s ease; }
        .feature-card:hover { transform: translateY(-5px); box-shadow: 0 24px 48px -12px rgba(0,0,0,.10); }
        @keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-12px)} }
        .float { animation: float 5s ease-in-out infinite; }
        @keyframes fadeUp { from{opacity:0;transform:translateY(28px)} to{opacity:1;transform:translateY(0)} }
        .fade-up { animation: fadeUp .7s ease forwards; }
        .d1{animation-delay:.05s;opacity:0} .d2{animation-delay:.15s;opacity:0}
        .d3{animation-delay:.25s;opacity:0} .d4{animation-delay:.35s;opacity:0}
        .d5{animation-delay:.45s;opacity:0} .d6{animation-delay:.55s;opacity:0}
        .gradient-text { background: linear-gradient(135deg, #ea580c, #f97316); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .orange-glow { box-shadow: 0 0 60px 0 rgba(234,88,12,.18); }
    </style>
</head>
<body class="antialiased text-slate-900 bg-white grid-pattern">

{{-- ===== NAVBAR ===== --}}
<header class="fixed top-0 w-full z-50 px-4 py-4">
    <nav class="max-w-7xl mx-auto flex items-center justify-between glass border border-white/60 px-5 py-3 rounded-2xl shadow-sm" id="navbar">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 bg-orange-600 rounded-xl flex items-center justify-center shadow-lg shadow-orange-200">
                <i class="fa-solid fa-file-shield text-white text-sm"></i>
            </div>
            <div>
                <span class="block text-sm font-black tracking-tight leading-none">Bama <span class="text-orange-600">GED</span></span>
                <span class="text-[8px] font-bold text-slate-400 uppercase tracking-widest">Plateforme documentaire</span>
            </div>
        </div>
        <div class="hidden md:flex items-center gap-6 text-[11px] font-bold text-slate-500">
            <a href="#fonctionnalites" class="hover:text-orange-600 transition-colors">Fonctionnalités</a>
            <a href="#securite" class="hover:text-orange-600 transition-colors">Sécurité</a>
            <a href="#workflow" class="hover:text-orange-600 transition-colors">Workflow</a>
            <a href="#tech" class="hover:text-orange-600 transition-colors">Technologie</a>
        </div>
        <div>
            @auth
                <a href="{{ route('dashboard') }}" class="bg-slate-900 text-white px-5 py-2.5 rounded-xl text-[11px] font-black uppercase tracking-wider hover:bg-orange-600 transition-all shadow-lg">
                    <i class="fa-solid fa-chart-pie mr-1.5 text-[10px]"></i>Tableau de bord
                </a>
            @else
                <a href="{{ route('login') }}" class="bg-orange-600 text-white px-5 py-2.5 rounded-xl text-[11px] font-black uppercase tracking-wider hover:bg-orange-700 transition-all shadow-lg shadow-orange-200">
                    <i class="fa-solid fa-arrow-right-to-bracket mr-1.5 text-[10px]"></i>Connexion
                </a>
            @endauth
        </div>
    </nav>
</header>

{{-- ===== HERO ===== --}}
<section class="relative pt-36 pb-24 px-4 overflow-hidden">
    {{-- Blobs décoratifs --}}
    <div class="absolute top-20 left-1/4 w-96 h-96 bg-orange-100/60 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-0 right-1/4 w-80 h-80 bg-slate-100/80 rounded-full blur-3xl pointer-events-none"></div>

    <div class="max-w-7xl mx-auto flex flex-col items-center text-center relative z-10">

        <div class="fade-up d1 inline-flex items-center gap-2 bg-white border border-orange-100 px-4 py-1.5 rounded-full shadow-sm mb-8">
            <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Système opérationnel · Groupe Bama</span>
        </div>

        <h1 class="fade-up d2 text-5xl md:text-7xl lg:text-8xl font-black tracking-tighter leading-[0.88] max-w-5xl mb-8">
            La mémoire<br>
            <span class="text-slate-300">documentaire</span> du<br>
            <span class="gradient-text">Groupe Bama</span>
        </h1>

        <p class="fade-up d3 text-slate-500 max-w-2xl text-base md:text-lg font-medium leading-relaxed mb-10">
            Une plateforme GED complète — gestion, approbation, signature, partage et archivage de tous vos documents d'entreprise. Développée sur mesure par <span class="text-slate-900 font-bold">Imperis Sarl</span>.
        </p>

        <div class="fade-up d4 flex flex-col sm:flex-row gap-4">
            <a href="{{ route('login') }}"
               class="group bg-slate-900 text-white px-8 py-4 rounded-2xl font-black text-sm flex items-center justify-center gap-3 hover:bg-orange-600 transition-all shadow-2xl shadow-slate-200 active:scale-95 uppercase tracking-widest">
                Accéder à la plateforme
                <i class="fa-solid fa-arrow-right group-hover:translate-x-1.5 transition-transform text-[11px]"></i>
            </a>
            <a href="#fonctionnalites"
               class="group bg-white border border-slate-200 text-slate-700 px-8 py-4 rounded-2xl font-black text-sm flex items-center justify-center gap-3 hover:border-orange-300 hover:text-orange-600 transition-all shadow-sm uppercase tracking-widest">
                <i class="fa-solid fa-play text-[10px]"></i>Découvrir
            </a>
        </div>

        {{-- Stats rapides --}}
        <div class="fade-up d5 mt-16 grid grid-cols-2 md:grid-cols-4 gap-4 w-full max-w-3xl">
            @php
                $docsCount  = \App\Models\Document::count();
                $usersCount = \App\Models\User::count();
                $catsCount  = \App\Models\Category::count();
                $approvedCount = \App\Models\Document::where('status','approved')->count();
            @endphp
            <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-sm text-center">
                <p class="text-3xl font-black text-slate-900 stat-num">{{ number_format($docsCount) }}</p>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1">Documents</p>
            </div>
            <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-sm text-center">
                <p class="text-3xl font-black text-orange-600 stat-num">{{ number_format($approvedCount) }}</p>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1">Approuvés</p>
            </div>
            <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-sm text-center">
                <p class="text-3xl font-black text-slate-900 stat-num">{{ number_format($usersCount) }}</p>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1">Utilisateurs</p>
            </div>
            <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-sm text-center">
                <p class="text-3xl font-black text-slate-900 stat-num">{{ number_format($catsCount) }}</p>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1">Catégories</p>
            </div>
        </div>
    </div>
</section>

{{-- ===== SECTION : CE QUE C'EST ===== --}}
<section class="py-20 px-4 bg-slate-900 relative overflow-hidden">
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-orange-600/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-[400px] h-[400px] bg-blue-600/5 rounded-full blur-3xl"></div>
    </div>
    <div class="max-w-7xl mx-auto relative z-10">
        <div class="text-center mb-14">
            <span class="text-[10px] font-black text-orange-500 uppercase tracking-[0.4em]">Le projet</span>
            <h2 class="text-3xl md:text-5xl font-black text-white tracking-tight mt-2 leading-tight">
                Qu'est-ce que la GED Bama ?
            </h2>
            <p class="text-slate-400 mt-4 max-w-2xl mx-auto text-sm leading-relaxed">
                La GED Bama est le système central de gestion documentaire du Groupe Bama. Elle remplace les dossiers papier, les emails et les partages de fichiers désorganisés par une plateforme unique, sécurisée et traçable.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div class="bg-white/5 border border-white/10 rounded-2xl p-6 hover:bg-white/10 transition-colors">
                <div class="w-10 h-10 rounded-xl bg-orange-600/20 flex items-center justify-center mb-4">
                    <i class="fa-solid fa-file-circle-plus text-orange-400 text-sm"></i>
                </div>
                <h3 class="text-white font-black text-base mb-2">Créer & Importer</h3>
                <p class="text-slate-400 text-xs leading-relaxed">Générez des documents Word avec QR code intégré en un clic, ou importez vos fichiers existants (.docx). Chaque document reçoit une référence unique automatique.</p>
            </div>
            <div class="bg-white/5 border border-white/10 rounded-2xl p-6 hover:bg-white/10 transition-colors">
                <div class="w-10 h-10 rounded-xl bg-blue-600/20 flex items-center justify-center mb-4">
                    <i class="fa-solid fa-arrows-spin text-blue-400 text-sm"></i>
                </div>
                <h3 class="text-white font-black text-base mb-2">Cycle de vie complet</h3>
                <p class="text-slate-400 text-xs leading-relaxed">De la création au brouillon, en révision, approuvé, puis archivé — chaque étape est tracée, notifiée et auditable. Rien ne se perd, tout est retrouvable.</p>
            </div>
            <div class="bg-white/5 border border-white/10 rounded-2xl p-6 hover:bg-white/10 transition-colors">
                <div class="w-10 h-10 rounded-xl bg-green-600/20 flex items-center justify-center mb-4">
                    <i class="fa-solid fa-users-gear text-green-400 text-sm"></i>
                </div>
                <h3 class="text-white font-black text-base mb-2">Multi-utilisateurs & Rôles</h3>
                <p class="text-slate-400 text-xs leading-relaxed">Administrateurs, éditeurs, lecteurs — chaque profil a ses droits. Les documents sont visibles uniquement par leurs auteurs ou les personnes autorisées.</p>
            </div>
        </div>
    </div>
</section>

{{-- ===== SECTION : FONCTIONNALITÉS ===== --}}
<section id="fonctionnalites" class="py-24 px-4">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-16">
            <span class="text-[10px] font-black text-orange-600 uppercase tracking-[0.4em]">Fonctionnalités</span>
            <h2 class="text-3xl md:text-5xl font-black text-slate-900 tracking-tight mt-2">Tout ce dont vous avez besoin</h2>
            <p class="text-slate-500 mt-3 max-w-xl mx-auto text-sm">Une suite complète d'outils pensés pour la réalité du terrain au Groupe Bama.</p>
        </div>

        {{-- Grille principale --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">

            {{-- Gestion documentaire --}}
            <div class="feature-card bg-white border border-slate-100 rounded-3xl p-7 shadow-sm">
                <div class="w-11 h-11 rounded-2xl bg-orange-50 flex items-center justify-center mb-5">
                    <i class="fa-solid fa-file-lines text-orange-600 text-base"></i>
                </div>
                <h3 class="text-base font-black text-slate-900 mb-2">Gestion documentaire</h3>
                <p class="text-slate-500 text-xs leading-relaxed mb-4">Créez, modifiez, archivez et supprimez vos documents. Chaque fichier Word est stocké sur MinIO avec une référence unique (ex: BAMA-XXXXXX).</p>
                <div class="flex flex-wrap gap-1.5">
                    <span class="px-2 py-0.5 bg-orange-50 text-orange-600 rounded-lg text-[9px] font-bold">DOCX natif</span>
                    <span class="px-2 py-0.5 bg-orange-50 text-orange-600 rounded-lg text-[9px] font-bold">Import/Export</span>
                    <span class="px-2 py-0.5 bg-orange-50 text-orange-600 rounded-lg text-[9px] font-bold">Corbeille</span>
                </div>
            </div>

            {{-- Versioning --}}
            <div class="feature-card bg-white border border-slate-100 rounded-3xl p-7 shadow-sm">
                <div class="w-11 h-11 rounded-2xl bg-blue-50 flex items-center justify-center mb-5">
                    <i class="fa-solid fa-code-branch text-blue-600 text-base"></i>
                </div>
                <h3 class="text-base font-black text-slate-900 mb-2">Versioning automatique</h3>
                <p class="text-slate-500 text-xs leading-relaxed mb-4">Chaque modification crée une nouvelle version. Consultez l'historique complet, comparez les versions et restaurez n'importe quelle version précédente.</p>
                <div class="flex flex-wrap gap-1.5">
                    <span class="px-2 py-0.5 bg-blue-50 text-blue-600 rounded-lg text-[9px] font-bold">Historique complet</span>
                    <span class="px-2 py-0.5 bg-blue-50 text-blue-600 rounded-lg text-[9px] font-bold">Restauration</span>
                    <span class="px-2 py-0.5 bg-blue-50 text-blue-600 rounded-lg text-[9px] font-bold">Checksum SHA-256</span>
                </div>
            </div>

            {{-- Workflow approbation --}}
            <div class="feature-card bg-orange-600 rounded-3xl p-7 shadow-xl shadow-orange-200 text-white">
                <div class="w-11 h-11 rounded-2xl bg-white/20 flex items-center justify-center mb-5">
                    <i class="fa-solid fa-list-check text-white text-base"></i>
                </div>
                <h3 class="text-base font-black mb-2">Workflow d'approbation</h3>
                <p class="text-orange-100 text-xs leading-relaxed mb-4">Configurez des circuits de validation séquentiels. Chaque approbateur est notifié, peut approuver ou rejeter avec commentaire. Le document passe automatiquement au statut "Approuvé".</p>
                <div class="flex flex-wrap gap-1.5">
                    <span class="px-2 py-0.5 bg-white/20 text-white rounded-lg text-[9px] font-bold">Multi-étapes</span>
                    <span class="px-2 py-0.5 bg-white/20 text-white rounded-lg text-[9px] font-bold">Notifications</span>
                    <span class="px-2 py-0.5 bg-white/20 text-white rounded-lg text-[9px] font-bold">Rejet motivé</span>
                </div>
            </div>

            {{-- Signatures numériques --}}
            <div class="feature-card bg-white border border-slate-100 rounded-3xl p-7 shadow-sm">
                <div class="w-11 h-11 rounded-2xl bg-purple-50 flex items-center justify-center mb-5">
                    <i class="fa-solid fa-signature text-purple-600 text-base"></i>
                </div>
                <h3 class="text-base font-black text-slate-900 mb-2">Signatures numériques</h3>
                <p class="text-slate-500 text-xs leading-relaxed mb-4">Apposez votre signature numérique directement dans le navigateur. Chaque signature est horodatée, liée à votre identité et vérifiable par hash SHA-256.</p>
                <div class="flex flex-wrap gap-1.5">
                    <span class="px-2 py-0.5 bg-purple-50 text-purple-600 rounded-lg text-[9px] font-bold">Signature canvas</span>
                    <span class="px-2 py-0.5 bg-purple-50 text-purple-600 rounded-lg text-[9px] font-bold">Vérification hash</span>
                    <span class="px-2 py-0.5 bg-purple-50 text-purple-600 rounded-lg text-[9px] font-bold">Horodatage</span>
                </div>
            </div>

            {{-- Partage & Collaboration --}}
            <div class="feature-card bg-white border border-slate-100 rounded-3xl p-7 shadow-sm">
                <div class="w-11 h-11 rounded-2xl bg-green-50 flex items-center justify-center mb-5">
                    <i class="fa-solid fa-share-nodes text-green-600 text-base"></i>
                </div>
                <h3 class="text-base font-black text-slate-900 mb-2">Partage & Collaboration</h3>
                <p class="text-slate-500 text-xs leading-relaxed mb-4">Partagez un document avec un utilisateur interne (vue/édition/commentaire) ou générez un lien public temporaire. Révoquez l'accès à tout moment.</p>
                <div class="flex flex-wrap gap-1.5">
                    <span class="px-2 py-0.5 bg-green-50 text-green-600 rounded-lg text-[9px] font-bold">Lien public</span>
                    <span class="px-2 py-0.5 bg-green-50 text-green-600 rounded-lg text-[9px] font-bold">Expiration</span>
                    <span class="px-2 py-0.5 bg-green-50 text-green-600 rounded-lg text-[9px] font-bold">Révocation</span>
                </div>
            </div>

            {{-- Recherche avancée --}}
            <div class="feature-card bg-white border border-slate-100 rounded-3xl p-7 shadow-sm">
                <div class="w-11 h-11 rounded-2xl bg-amber-50 flex items-center justify-center mb-5">
                    <i class="fa-solid fa-magnifying-glass text-amber-600 text-base"></i>
                </div>
                <h3 class="text-base font-black text-slate-900 mb-2">Recherche avancée</h3>
                <p class="text-slate-500 text-xs leading-relaxed mb-4">Recherche plein texte PostgreSQL en français. Filtrez par catégorie, statut, créateur, date, tags, confidentialité. Résultats instantanés parmi tous vos documents.</p>
                <div class="flex flex-wrap gap-1.5">
                    <span class="px-2 py-0.5 bg-amber-50 text-amber-600 rounded-lg text-[9px] font-bold">Full-text FR</span>
                    <span class="px-2 py-0.5 bg-amber-50 text-amber-600 rounded-lg text-[9px] font-bold">Multi-filtres</span>
                    <span class="px-2 py-0.5 bg-amber-50 text-amber-600 rounded-lg text-[9px] font-bold">Tags</span>
                </div>
            </div>

            {{-- Commentaires --}}
            <div class="feature-card bg-white border border-slate-100 rounded-3xl p-7 shadow-sm">
                <div class="w-11 h-11 rounded-2xl bg-indigo-50 flex items-center justify-center mb-5">
                    <i class="fa-solid fa-comments text-indigo-600 text-base"></i>
                </div>
                <h3 class="text-base font-black text-slate-900 mb-2">Commentaires & Annotations</h3>
                <p class="text-slate-500 text-xs leading-relaxed mb-4">Échangez directement sur un document avec des commentaires threadés. Les commentaires internes (admin) restent invisibles aux autres utilisateurs.</p>
                <div class="flex flex-wrap gap-1.5">
                    <span class="px-2 py-0.5 bg-indigo-50 text-indigo-600 rounded-lg text-[9px] font-bold">Réponses</span>
                    <span class="px-2 py-0.5 bg-indigo-50 text-indigo-600 rounded-lg text-[9px] font-bold">Commentaires internes</span>
                    <span class="px-2 py-0.5 bg-indigo-50 text-indigo-600 rounded-lg text-[9px] font-bold">Notifications</span>
                </div>
            </div>

            {{-- Catégories --}}
            <div class="feature-card bg-white border border-slate-100 rounded-3xl p-7 shadow-sm">
                <div class="w-11 h-11 rounded-2xl bg-teal-50 flex items-center justify-center mb-5">
                    <i class="fa-solid fa-folder-tree text-teal-600 text-base"></i>
                </div>
                <h3 class="text-base font-black text-slate-900 mb-2">Catégories hiérarchiques</h3>
                <p class="text-slate-500 text-xs leading-relaxed mb-4">Organisez vos documents en catégories et sous-catégories. Créez une arborescence qui reflète la structure réelle de votre organisation.</p>
                <div class="flex flex-wrap gap-1.5">
                    <span class="px-2 py-0.5 bg-teal-50 text-teal-600 rounded-lg text-[9px] font-bold">Parent/Enfant</span>
                    <span class="px-2 py-0.5 bg-teal-50 text-teal-600 rounded-lg text-[9px] font-bold">Slug URL</span>
                    <span class="px-2 py-0.5 bg-teal-50 text-teal-600 rounded-lg text-[9px] font-bold">Compteurs</span>
                </div>
            </div>

            {{-- Favoris & Tableau de bord --}}
            <div class="feature-card bg-slate-900 rounded-3xl p-7 text-white">
                <div class="w-11 h-11 rounded-2xl bg-amber-500/20 flex items-center justify-center mb-5">
                    <i class="fa-solid fa-star text-amber-400 text-base"></i>
                </div>
                <h3 class="text-base font-black mb-2">Favoris & Tableau de bord</h3>
                <p class="text-slate-400 text-xs leading-relaxed mb-4">Épinglez vos documents importants en favoris. Le tableau de bord s'adapte à votre rôle : les admins voient tout, les éditeurs voient leurs documents et ceux partagés.</p>
                <div class="flex flex-wrap gap-1.5">
                    <span class="px-2 py-0.5 bg-white/10 text-slate-300 rounded-lg text-[9px] font-bold">Dashboard adaptatif</span>
                    <span class="px-2 py-0.5 bg-white/10 text-slate-300 rounded-lg text-[9px] font-bold">Favoris</span>
                    <span class="px-2 py-0.5 bg-white/10 text-slate-300 rounded-lg text-[9px] font-bold">KPIs temps réel</span>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- ===== SECTION : SÉCURITÉ & CONFORMITÉ ===== --}}
<section id="securite" class="py-24 px-4 bg-slate-50">
    <div class="max-w-7xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div>
                <span class="text-[10px] font-black text-orange-600 uppercase tracking-[0.4em]">Sécurité & Conformité</span>
                <h2 class="text-3xl md:text-4xl font-black text-slate-900 tracking-tight mt-2 mb-6 leading-tight">
                    Chaque action est<br>tracée et protégée
                </h2>
                <p class="text-slate-500 text-sm leading-relaxed mb-8">
                    La GED Bama a été conçue avec la sécurité comme priorité absolue. Aucune action ne passe sans être enregistrée. Chaque document est vérifiable, chaque accès est contrôlé.
                </p>
                <div class="space-y-4">
                    <div class="flex items-start gap-4">
                        <div class="w-9 h-9 rounded-xl bg-green-100 flex items-center justify-center shrink-0 mt-0.5">
                            <i class="fa-solid fa-shield-halved text-green-600 text-sm"></i>
                        </div>
                        <div>
                            <p class="text-sm font-black text-slate-900">Journal d'audit complet</p>
                            <p class="text-xs text-slate-500 mt-0.5">Chaque consultation, modification, téléchargement, approbation ou suppression est enregistrée avec l'IP, l'heure et l'utilisateur.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-9 h-9 rounded-xl bg-blue-100 flex items-center justify-center shrink-0 mt-0.5">
                            <i class="fa-solid fa-qrcode text-blue-600 text-sm"></i>
                        </div>
                        <div>
                            <p class="text-sm font-black text-slate-900">QR Code de vérification</p>
                            <p class="text-xs text-slate-500 mt-0.5">Chaque document généré contient un QR code unique dans son pied de page. Scannez-le pour vérifier l'authenticité du document en temps réel.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-9 h-9 rounded-xl bg-red-100 flex items-center justify-center shrink-0 mt-0.5">
                            <i class="fa-solid fa-lock text-red-600 text-sm"></i>
                        </div>
                        <div>
                            <p class="text-sm font-black text-slate-900">Contrôle d'accès granulaire</p>
                            <p class="text-xs text-slate-500 mt-0.5">3 rôles globaux (Admin, Éditeur, Lecteur) + permissions par document (voir, éditer, supprimer, approuver, partager, commenter).</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-9 h-9 rounded-xl bg-amber-100 flex items-center justify-center shrink-0 mt-0.5">
                            <i class="fa-solid fa-file-shield text-amber-600 text-sm"></i>
                        </div>
                        <div>
                            <p class="text-sm font-black text-slate-900">Watermark automatique</p>
                            <p class="text-xs text-slate-500 mt-0.5">Les documents confidentiels sont téléchargés avec un watermark "CONFIDENTIEL — [Nom] — [Date]" injecté automatiquement.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-9 h-9 rounded-xl bg-purple-100 flex items-center justify-center shrink-0 mt-0.5">
                            <i class="fa-solid fa-clock-rotate-left text-purple-600 text-sm"></i>
                        </div>
                        <div>
                            <p class="text-sm font-black text-slate-900">Rétention & Expiration</p>
                            <p class="text-xs text-slate-500 mt-0.5">Définissez une durée de rétention par document. Des rappels automatiques sont envoyés 7 jours et 1 jour avant expiration.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-sm text-center">
                    <div class="w-12 h-12 rounded-2xl bg-green-50 flex items-center justify-center mx-auto mb-3">
                        <i class="fa-solid fa-check-double text-green-600 text-lg"></i>
                    </div>
                    <p class="text-2xl font-black text-slate-900">100%</p>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-wider mt-1">Actions tracées</p>
                </div>
                <div class="bg-orange-600 rounded-2xl p-5 shadow-xl shadow-orange-200 text-center text-white orange-glow">
                    <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center mx-auto mb-3">
                        <i class="fa-solid fa-user-shield text-white text-lg"></i>
                    </div>
                    <p class="text-2xl font-black">RBAC</p>
                    <p class="text-[9px] font-black text-orange-200 uppercase tracking-wider mt-1">Contrôle par rôle</p>
                </div>
                <div class="bg-slate-900 rounded-2xl p-5 shadow-xl text-center text-white">
                    <div class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center mx-auto mb-3">
                        <i class="fa-solid fa-database text-slate-300 text-lg"></i>
                    </div>
                    <p class="text-2xl font-black">MinIO</p>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-wider mt-1">Stockage S3</p>
                </div>
                <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-sm text-center">
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 flex items-center justify-center mx-auto mb-3">
                        <i class="fa-solid fa-fingerprint text-blue-600 text-lg"></i>
                    </div>
                    <p class="text-2xl font-black text-slate-900">SHA-256</p>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-wider mt-1">Intégrité fichiers</p>
                </div>
                <div class="col-span-2 bg-white border border-slate-100 rounded-2xl p-5 shadow-sm">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-desktop text-slate-500 text-sm"></i>
                        </div>
                        <div>
                            <p class="text-sm font-black text-slate-900">Sessions actives</p>
                            <p class="text-[10px] text-slate-400">Gérez vos appareils connectés</p>
                        </div>
                    </div>
                    <p class="text-xs text-slate-500">Consultez et révoquez vos sessions ouvertes depuis n'importe quel appareil. Historique de connexions avec IP et user-agent.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ===== SECTION : WORKFLOW VISUEL ===== --}}
<section id="workflow" class="py-24 px-4">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-16">
            <span class="text-[10px] font-black text-orange-600 uppercase tracking-[0.4em]">Cycle documentaire</span>
            <h2 class="text-3xl md:text-5xl font-black text-slate-900 tracking-tight mt-2">De la création à l'archivage</h2>
            <p class="text-slate-500 mt-3 max-w-xl mx-auto text-sm">Chaque document suit un cycle de vie structuré, transparent et auditable.</p>
        </div>

        {{-- Timeline workflow --}}
        <div class="relative">
            {{-- Ligne centrale --}}
            <div class="hidden md:block absolute top-8 left-0 right-0 h-0.5 bg-gradient-to-r from-transparent via-slate-200 to-transparent"></div>

            <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
                @php
                    $steps = [
                        ['icon'=>'fa-plus','color'=>'bg-slate-900 text-white','label'=>'Brouillon','desc'=>'Document créé ou importé. Référence unique générée. QR code intégré.','badge'=>'bg-amber-50 text-amber-600'],
                        ['icon'=>'fa-clock','color'=>'bg-blue-600 text-white','label'=>'En révision','desc'=>'Soumis au circuit d\'approbation. Les approbateurs sont notifiés par email.','badge'=>'bg-blue-50 text-blue-600'],
                        ['icon'=>'fa-list-check','color'=>'bg-orange-600 text-white','label'=>'Approbation','desc'=>'Chaque approbateur valide ou rejette avec commentaire. Séquentiel.','badge'=>'bg-orange-50 text-orange-600'],
                        ['icon'=>'fa-circle-check','color'=>'bg-green-600 text-white','label'=>'Approuvé','desc'=>'Document officiel. Signatures numériques possibles. Partage autorisé.','badge'=>'bg-green-50 text-green-600'],
                        ['icon'=>'fa-box-archive','color'=>'bg-slate-500 text-white','label'=>'Archivé','desc'=>'Conservé selon la politique de rétention. Consultable mais non modifiable.','badge'=>'bg-slate-100 text-slate-500'],
                    ];
                @endphp
                @foreach($steps as $i => $step)
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 rounded-2xl {{ $step['color'] }} flex items-center justify-center shadow-lg mb-4 relative z-10">
                        <i class="fa-solid {{ $step['icon'] }} text-xl"></i>
                    </div>
                    <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-wider {{ $step['badge'] }} mb-3">
                        {{ $step['label'] }}
                    </span>
                    <p class="text-xs text-slate-500 leading-relaxed max-w-[160px]">{{ $step['desc'] }}</p>
                    @if($i < 4)
                    <div class="md:hidden mt-4 w-0.5 h-8 bg-slate-200"></div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        {{-- Features supplémentaires en ligne --}}
        <div class="mt-16 grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-sm text-center feature-card">
                <i class="fa-solid fa-bell text-orange-500 text-2xl mb-3"></i>
                <p class="text-sm font-black text-slate-900">Notifications</p>
                <p class="text-[10px] text-slate-400 mt-1">Email + in-app en temps réel pour chaque événement</p>
            </div>
            <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-sm text-center feature-card">
                <i class="fa-solid fa-lock text-slate-700 text-2xl mb-3"></i>
                <p class="text-sm font-black text-slate-900">Verrouillage</p>
                <p class="text-[10px] text-slate-400 mt-1">Verrou optimiste pour éviter les éditions simultanées</p>
            </div>
            <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-sm text-center feature-card">
                <i class="fa-solid fa-chart-bar text-blue-500 text-2xl mb-3"></i>
                <p class="text-sm font-black text-slate-900">Rapports</p>
                <p class="text-[10px] text-slate-400 mt-1">Statistiques, exports CSV, activité par utilisateur</p>
            </div>
            <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-sm text-center feature-card">
                <i class="fa-solid fa-server text-green-500 text-2xl mb-3"></i>
                <p class="text-sm font-black text-slate-900">WebDAV</p>
                <p class="text-[10px] text-slate-400 mt-1">Accès natif depuis Windows Explorer et macOS Finder</p>
            </div>
        </div>
    </div>
</section>



{{-- ===== SECTION : GESTION UTILISATEURS ===== --}}
<section class="py-24 px-4">
    <div class="max-w-7xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div class="order-2 lg:order-1">
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-orange-600 rounded-2xl p-5 text-white shadow-xl shadow-orange-200">
                        <i class="fa-solid fa-crown text-2xl mb-3 block"></i>
                        <p class="font-black text-sm">Administrateur</p>
                        <p class="text-orange-200 text-[10px] mt-1">Accès total. Gère utilisateurs, paramètres, rapports, corbeille.</p>
                    </div>
                    <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-sm">
                        <i class="fa-solid fa-pen-to-square text-blue-600 text-2xl mb-3 block"></i>
                        <p class="font-black text-sm text-slate-900">Éditeur</p>
                        <p class="text-slate-400 text-[10px] mt-1">Crée et modifie ses documents. Voit les documents partagés.</p>
                    </div>
                    <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-sm">
                        <i class="fa-solid fa-eye text-slate-500 text-2xl mb-3 block"></i>
                        <p class="font-black text-sm text-slate-900">Lecteur</p>
                        <p class="text-slate-400 text-[10px] mt-1">Consulte uniquement les documents partagés avec lui.</p>
                    </div>
                    <div class="bg-slate-900 rounded-2xl p-5 text-white">
                        <i class="fa-solid fa-sliders text-orange-400 text-2xl mb-3 block"></i>
                        <p class="font-black text-sm">Permissions ACL</p>
                        <p class="text-slate-400 text-[10px] mt-1">Droits granulaires par document avec date d'expiration.</p>
                    </div>
                </div>
            </div>
            <div class="order-1 lg:order-2">
                <span class="text-[10px] font-black text-orange-600 uppercase tracking-[0.4em]">Gestion des accès</span>
                <h2 class="text-3xl md:text-4xl font-black text-slate-900 tracking-tight mt-2 mb-6 leading-tight">
                    Chaque utilisateur<br>voit ce qu'il doit voir
                </h2>
                <p class="text-slate-500 text-sm leading-relaxed mb-6">
                    Le système de permissions est à deux niveaux : des rôles globaux définissent les capacités générales, et des permissions par document permettent un contrôle fin sur chaque ressource.
                </p>
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-check text-green-600 text-[8px]"></i>
                        </div>
                        <p class="text-xs text-slate-600 font-medium">Un éditeur ne voit que ses propres documents + ceux partagés avec lui</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-check text-green-600 text-[8px]"></i>
                        </div>
                        <p class="text-xs text-slate-600 font-medium">Les partages peuvent expirer automatiquement à une date définie</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-check text-green-600 text-[8px]"></i>
                        </div>
                        <p class="text-xs text-slate-600 font-medium">L'historique de connexion est visible dans le profil de chaque utilisateur</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-check text-green-600 text-[8px]"></i>
                        </div>
                        <p class="text-xs text-slate-600 font-medium">Réinitialisation de mot de passe par email avec lien sécurisé</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ===== CTA FINAL ===== --}}
<section class="py-24 px-4 bg-slate-50">
    <div class="max-w-4xl mx-auto text-center">
        <div class="bg-slate-900 rounded-3xl p-12 md:p-16 relative overflow-hidden shadow-2xl">
            <div class="absolute top-0 right-0 w-64 h-64 bg-orange-600/15 rounded-full -translate-y-16 translate-x-16 pointer-events-none"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-orange-600/10 rounded-full translate-y-12 -translate-x-12 pointer-events-none"></div>
            <div class="relative z-10">
                <div class="w-16 h-16 bg-orange-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-xl shadow-orange-900/40 float">
                    <i class="fa-solid fa-file-shield text-white text-2xl"></i>
                </div>
                <h2 class="text-3xl md:text-5xl font-black text-white tracking-tight mb-4 leading-tight">
                    Prêt à digitaliser<br>votre gestion documentaire ?
                </h2>
                <p class="text-slate-400 text-sm leading-relaxed mb-8 max-w-xl mx-auto">
                    La GED Bama est opérationnelle et prête à l'emploi. Connectez-vous avec vos identifiants pour accéder à votre espace documentaire sécurisé.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('login') }}"
                       class="group bg-orange-600 text-white px-10 py-4 rounded-2xl font-black text-sm flex items-center justify-center gap-3 hover:bg-orange-500 transition-all shadow-xl shadow-orange-900/30 active:scale-95 uppercase tracking-widest">
                        <i class="fa-solid fa-arrow-right-to-bracket text-[11px]"></i>
                        Se connecter
                        <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform text-[10px]"></i>
                    </a>
                </div>
                <p class="text-slate-600 text-[10px] mt-6 font-medium">
                    Développé par <span class="text-orange-500 font-black">Imperis Sarl</span> · Ingénierie & Transformation Digitale · Bamako {{ date('Y') }}
                </p>
            </div>
        </div>
    </div>
</section>

{{-- ===== FOOTER ===== --}}
<footer class="py-12 px-4 border-t border-slate-100">
    <div class="max-w-7xl mx-auto">
        <div class="flex flex-col md:flex-row items-center justify-between gap-8">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-orange-600 rounded-xl flex items-center justify-center shadow-lg shadow-orange-200">
                    <i class="fa-solid fa-file-shield text-white text-sm"></i>
                </div>
                <div>
                    <p class="text-sm font-black text-slate-900 leading-none">Bama <span class="text-orange-600">GED</span></p>
                    <p class="text-[8px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Plateforme documentaire</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-center gap-6 text-[10px] font-bold text-slate-400">
                <a href="#fonctionnalites" class="hover:text-orange-600 transition-colors uppercase tracking-wider">Fonctionnalités</a>
                <a href="#securite" class="hover:text-orange-600 transition-colors uppercase tracking-wider">Sécurité</a>
                <a href="#workflow" class="hover:text-orange-600 transition-colors uppercase tracking-wider">Workflow</a>
                <a href="#tech" class="hover:text-orange-600 transition-colors uppercase tracking-wider">Technologie</a>
                <a href="{{ route('login') }}" class="hover:text-orange-600 transition-colors uppercase tracking-wider">Connexion</a>
            </div>

            <div class="text-center md:text-right">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">
                    © {{ date('Y') }} Groupe Bama · Tous droits réservés
                </p>
                <p class="text-[9px] text-slate-300 mt-0.5">
                    Conçu par <a href="https://imperis.com" target="_blank" class="text-orange-500 font-black hover:underline">Imperis Sarl</a>
                </p>
            </div>
        </div>
    </div>
</footer>

<script>
    // Navbar scroll effect
    window.addEventListener('scroll', () => {
        const nav = document.getElementById('navbar');
        if (window.scrollY > 40) {
            nav.classList.add('shadow-lg');
        } else {
            nav.classList.remove('shadow-lg');
        }
    });

    // Smooth scroll pour les ancres
    document.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', e => {
            const target = document.querySelector(a.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
</script>
</body>
</html>
