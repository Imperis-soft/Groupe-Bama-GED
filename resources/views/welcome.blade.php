<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Groupe Bama | GED </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@200;400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; bg-white; }
        .glass { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(12px); }
        .grid-pattern {
            background-image: radial-gradient(circle at 1px 1px, #e5e7eb 1px, transparent 0);
            background-size: 32px 32px;
        }
    </style>
</head>
<body class="antialiased text-slate-900 grid-pattern">

    <header class="fixed top-0 w-full z-50 px-6 py-4">
        <nav class="max-w-7xl mx-auto flex items-center justify-between glass border border-white/40 px-6 py-3 rounded-3xl shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-orange-600 rounded-2xl flex items-center justify-center shadow-lg shadow-orange-200">
                    <i class="fa-solid fa-box-archive text-white text-lg"></i>
                </div>
                <div>
                    <span class="block text-sm font-black tracking-tight leading-none uppercase">Bama <span class="text-orange-600 font-black">GED</span></span>
                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Enterprise Solution</span>
                </div>
            </div>
            
            <div class="flex items-center gap-6">
                <!-- <a href="https://imperis.com" target="_blank" class="hidden md:block text-[10px] font-black text-slate-400 hover:text-orange-600 transition-colors uppercase tracking-[0.2em]">Partner: Imperis Sarl</a> -->
                @auth
                    <a href="{{ route('dashboard') }}" class="bg-slate-900 text-white px-6 py-2.5 rounded-xl text-[11px] font-black uppercase tracking-wider hover:bg-orange-600 transition-all shadow-xl shadow-slate-200">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="bg-orange-600 text-white px-6 py-2.5 rounded-xl text-[11px] font-black uppercase tracking-wider hover:bg-orange-700 transition-all shadow-xl shadow-orange-200">Connexion</a>
                @endauth
            </div>
        </nav>
    </header>

    <section class="relative pt-40 pb-20 px-6 overflow-hidden">
        <div class="max-w-7xl mx-auto flex flex-col items-center">
            <div class="inline-flex items-center gap-2 bg-white border border-slate-100 px-4 py-1.5 rounded-full shadow-sm mb-12 animate-fade-in">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none">Conçu avec excellence par</span>
                <span class="text-[11px] font-black text-orange-600 uppercase tracking-tighter italic">Imperis Sarl</span>
            </div>

            <h1 class="text-5xl md:text-8xl font-black text-slate-900 tracking-tighter text-center leading-[0.9] max-w-5xl mb-10">
                Gestion <span class="text-slate-300">Electronique</span> des Documents <span class="text-orange-600 uppercase">Groupe Bama</span>
            </h1>

            <p class="text-slate-500 text-center max-w-2xl text-lg font-medium leading-relaxed mb-12 px-4 italic">
                Une infrastructure GED robuste développée par <span class="text-slate-900 font-bold underline decoration-orange-300">Imperis Sarl</span> pour transformer le flux documentaire du Groupe Bama en un actif stratégique.
            </p>

            <div class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto">
                <a href="{{ route('login') }}" class="group bg-slate-900 text-white px-10 py-5 rounded-2xl font-black text-sm flex items-center justify-center gap-3 hover:bg-orange-600 transition-all shadow-2xl shadow-slate-300 active:scale-95 uppercase tracking-widest">
                    Accéder à l'archive
                    <i class="fa-solid fa-arrow-right-long group-hover:translate-x-2 transition-transform"></i>
                </a>
            </div>
        </div>
    </section>

    <section id="features" class="py-20 px-6 max-w-7xl mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <div class="md:col-span-2 bg-white p-10 rounded-[3rem] border border-slate-100 shadow-sm relative overflow-hidden group">
                <div class="relative z-10 max-w-sm">
                    <div class="w-12 h-12 bg-green-50 text-green-600 rounded-2xl flex items-center justify-center mb-6">
                        <i class="fa-solid fa-shield-virus text-xl"></i>
                    </div>
                    <h3 class="text-2xl font-black text-slate-900 mb-4 tracking-tight uppercase">Souveraineté des données</h3>
                    <p class="text-slate-500 font-medium text-sm leading-relaxed">
                        Grâce à l'architecture d'Imperis, Bama bénéficie d'un stockage chiffré de bout en bout. Chaque document est tracé, audité et protégé contre toute intrusion externe.
                    </p>
                </div>
                <i class="fa-solid fa-fingerprint absolute -right-8 -bottom-8 text-[12rem] text-slate-50 opacity-40 group-hover:text-orange-50 transition-colors"></i>
            </div>

            <div class="bg-orange-600 p-10 rounded-[3rem] shadow-2xl shadow-orange-200 flex flex-col justify-between text-white group hover:scale-[1.02] transition-transform">
                <div>
                    <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center mb-6">
                        <i class="fa-solid fa-file-word text-xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-black mb-4 tracking-tight uppercase leading-none">Édition Native</h3>
                    <p class="text-orange-100 font-bold text-xs uppercase tracking-widest mb-6">Zéro téléchargement.</p>
                    <p class="text-orange-50 font-medium text-sm">
                        Modifiez vos contrats directement via Microsoft Office. Le système synchronise automatiquement vos changements.
                    </p>
                </div>
            </div>

            <div class="bg-white p-10 rounded-[3rem] border border-slate-100 shadow-sm flex flex-col justify-between group">
                <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mb-6">
                    <i class="fa-solid fa-magnifying-glass-chart text-xl"></i>
                </div>
                <div>
                    <h3 class="text-2xl font-black text-slate-900 mb-4 tracking-tight uppercase">Indexation Smart</h3>
                    <p class="text-slate-500 font-medium text-sm leading-relaxed">
                        Recherche multicritère instantanée. Retrouvez un document parmi des milliers grâce à la catégorisation intelligente par référence.
                    </p>
                </div>
            </div>

            <div class="md:col-span-2 bg-slate-900 p-10 rounded-[3rem] relative overflow-hidden group">
                <div class="relative z-10 flex flex-col md:flex-row items-center gap-10">
                    <div class="max-w-md">
                        <span class="text-orange-500 text-[10px] font-black uppercase tracking-[0.4em] mb-4 block">L'expertise Imperis</span>
                        <h3 class="text-3xl font-black text-white mb-6 leading-none">Pourquoi Imperis ?</h3>
                        <p class="text-slate-400 font-medium text-sm leading-relaxed mb-6 italic border-l-2 border-orange-600 pl-4">
                            "Nous n'avons pas seulement créé un logiciel, nous avons bâti l'écosystème numérique du Groupe Bama pour garantir la pérennité de leur mémoire institutionnelle."
                        </p>
                        <div class="flex items-center gap-4">
                            <div class="h-[1px] w-12 bg-slate-700"></div>
                            <span class="text-xs font-black text-slate-500 uppercase tracking-widest italic">L'équipe technique Imperis Sarl</span>
                        </div>
                    </div>
                    <div class="flex-grow flex justify-center">
                         <div class="w-32 h-32 bg-slate-800 rounded-full flex items-center justify-center border border-slate-700 shadow-inner group-hover:border-orange-600 transition-colors">
                            <i class="fa-solid fa-microchip text-slate-600 text-5xl group-hover:text-orange-600 transition-all"></i>
                         </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="py-20 px-6 border-t border-slate-100">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-12">
            <div class="text-center md:text-left">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mb-4">Propriété exclusive</p>
                <h4 class="text-xl font-black text-slate-900 uppercase italic leading-none">Groupe <span class="text-orange-600">Bama</span></h4>
            </div>

            <div class="flex flex-col items-center">
                <div class="flex items-center gap-6 mb-4">
                    <img src="https://via.placeholder.com/40" alt="" class="opacity-20 grayscale">
                    <i class="fa-solid fa-link text-slate-200"></i>
                    <a href="https://imperis.com" class="opacity-100 grayscale-0 hover:scale-110 transition-transform">
                        <div class="bg-slate-900 px-4 py-2 rounded-lg text-white font-black text-sm tracking-tighter uppercase italic">
                            Imperis<span class="text-orange-500 underline decoration-2">Sarl</span>
                        </div>
                    </a>
                </div>
                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest tracking-[0.2em] text-center">
                    Ingénierie & Transformation Digitale • Bamako {{ date ('Y') }}
                </p>
            </div>
        </div>
    </footer>

    <script>
        // Micro-interaction simple pour le scroll
        window.addEventListener('scroll', function() {
            const nav = document.querySelector('nav');
            if (window.scrollY > 50) {
                nav.classList.add('shadow-xl', 'border-slate-200');
            } else {
                nav.classList.remove('shadow-xl', 'border-slate-200');
            }
        });
    </script>
</body>
</html>