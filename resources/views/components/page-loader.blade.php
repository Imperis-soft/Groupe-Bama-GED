{{--
    Splash de transition entre pages (utilisateur connecté).
    - Barre de progression en haut + écran de chargement affiché immédiatement
    - Continuité : la page suivante s'ouvre sous le splash, qui reste visible au moins MIN_DURATION ms au total puis s'efface en fondu
    - Ignoré pour les téléchargements/exports, liens externes, nouvel onglet, ancres, et tout élément [data-no-loader]
    CSS en ligne volontairement : il doit s'afficher avant que Tailwind (CDN) ne soit compilé.
--}}
<script>
    try { if (sessionStorage.getItem('ged:nav')) document.documentElement.classList.add('ged-arriving'); } catch (e) {}
</script>
<style>
    #ged-splash { position: fixed; inset: 0; z-index: 2147483000; display: flex; align-items: center; justify-content: center;
        background: radial-gradient(1200px 600px at 50% 40%, rgba(255,247,237,.96), rgba(248,250,252,.97));
        -webkit-backdrop-filter: blur(6px); backdrop-filter: blur(6px);
        opacity: 0; visibility: hidden; transition: opacity .45s ease, visibility 0s linear .45s; }
    .ged-arriving #ged-splash, #ged-splash.is-visible { opacity: 1; visibility: visible; transition: opacity .22s ease; }
    #ged-splash .gs-card { display: flex; flex-direction: column; align-items: center; gap: 18px;
        transform: translateY(6px) scale(.98); transition: transform .35s cubic-bezier(.2,.8,.2,1); }
    .ged-arriving #ged-splash .gs-card, #ged-splash.is-visible .gs-card { transform: none; }
    #ged-splash .gs-logo { position: relative; width: 84px; height: 84px; display: grid; place-items: center; }
    #ged-splash .gs-ring { position: absolute; inset: 0; border-radius: 26px; padding: 3px;
        background: conic-gradient(from 0deg, rgba(234,88,12,0) 0deg, #ea580c 120deg, #fb923c 200deg, rgba(234,88,12,0) 360deg);
        -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0); -webkit-mask-composite: xor; mask-composite: exclude;
        animation: gs-spin 1.1s linear infinite; }
    #ged-splash .gs-badge { width: 66px; height: 66px; border-radius: 20px; display: grid; place-items: center;
        background: #fff; box-shadow: 0 18px 40px -14px rgba(234,88,12,.45);
        animation: gs-breathe 1.6s ease-in-out infinite; }
    #ged-splash .gs-badge img { width: 54px; height: 54px; object-fit: contain; }
    #ged-splash .gs-name { font: 900 15px/1 -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #0f172a; letter-spacing: -.01em; text-align: center; }
    #ged-splash .gs-sub { margin-top: 6px; font: 700 9px/1 -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #94a3b8; letter-spacing: .2em; text-transform: uppercase; text-align: center; }
    #ged-splash .gs-track { width: 160px; height: 3px; border-radius: 99px; background: #fde4d0; overflow: hidden; }
    #ged-splash .gs-track span { display: block; width: 40%; height: 100%; border-radius: 99px;
        background: linear-gradient(90deg, #fb923c, #ea580c); animation: gs-slide 1.2s cubic-bezier(.4,0,.2,1) infinite; }
    #ged-topbar { position: fixed; top: 0; left: 0; height: 3px; width: 0; z-index: 2147483001; pointer-events: none;
        background: linear-gradient(90deg, #fb923c, #ea580c); box-shadow: 0 0 10px rgba(234,88,12,.6); opacity: 0; }
    #ged-topbar.is-running { opacity: 1; width: 85%; transition: width 8s cubic-bezier(.1,.7,.2,1), opacity .1s; }
    @keyframes gs-spin { to { transform: rotate(360deg); } }
    @keyframes gs-breathe { 50% { transform: scale(.94); } }
    @keyframes gs-slide { 0% { transform: translateX(-110%); } 100% { transform: translateX(260%); } }
    @media (prefers-reduced-motion: reduce) {
        #ged-splash .gs-ring, #ged-splash .gs-badge, #ged-splash .gs-track span { animation-duration: 3s; }
        #ged-splash .gs-badge { animation: none; }
    }
</style>

<div id="ged-topbar" aria-hidden="true"></div>
<div id="ged-splash" role="status" aria-live="polite" aria-label="Chargement de la page">
    <div class="gs-card">
        <div class="gs-logo">
            <div class="gs-ring"></div>
            <div class="gs-badge"><img src="{{ asset('images/logo-ged-160.webp') }}" alt=""></div>
        </div>
        <div>
            <div class="gs-name">{{ brandName() }}</div>
            <div class="gs-sub">{{ config('saas.platform_name') }}</div>
        </div>
        <div class="gs-track"><span></span></div>
    </div>
</div>

<script>
(function () {
    const splash = document.getElementById('ged-splash');
    const bar    = document.getElementById('ged-topbar');
    const root   = document.documentElement;
    // Réponses de type fichier : la page ne change pas, le splash resterait affiché
    const FILE_URL = /\/(download|stream|export[\w-]*|preview\/pdf|webdav)(\/|\?|$)|\.(pdf|zip|docx?|xlsx?|csv)(\?|$)/i;
    // Durée minimale d'affichage (départ → arrivée) pour que le splash soit bien perçu
    const MIN_DURATION = 900;
    let safetyTimer = null;

    function start() {
        clearTimeout(safetyTimer);
        bar.classList.remove('is-running'); void bar.offsetWidth; bar.classList.add('is-running');
        splash.classList.add('is-visible');
        safetyTimer = setTimeout(stop, 15000);
        try { sessionStorage.setItem('ged:nav', String(Date.now())); } catch (e) {}
    }

    function stop() {
        clearTimeout(safetyTimer);
        splash.classList.remove('is-visible');
        root.classList.remove('ged-arriving');
        bar.classList.remove('is-running');
        try { sessionStorage.removeItem('ged:nav'); } catch (e) {}
    }

    function shouldHandle(url, target) {
        if (target && target !== '_self') return false;
        if (url.origin !== location.origin) return false;
        if (FILE_URL.test(url.pathname + url.search)) return false;
        // Simple ancre sur la même page
        if (url.pathname === location.pathname && url.search === location.search && url.hash) return false;
        return true;
    }

    document.addEventListener('click', (e) => {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        const a = e.target.closest('a[href]');
        if (!a || a.hasAttribute('download') || a.closest('[data-no-loader]')) return;
        const href = a.getAttribute('href');
        if (!href || href.startsWith('#') || /^(javascript|mailto|tel):/i.test(href)) return;
        if (shouldHandle(new URL(a.href, location.href), a.target)) start();
    });

    // Phase de bouillonnement sur window : les @submit.prevent / confirm() ont déjà eu lieu
    window.addEventListener('submit', (e) => {
        if (e.defaultPrevented) return;
        const form = e.target;
        if (form.closest('[data-no-loader]')) return;
        const submitter = e.submitter;
        const action = (submitter && submitter.getAttribute('formaction')) || form.getAttribute('action') || location.href;
        const target = (submitter && submitter.getAttribute('formtarget')) || form.target;
        if (shouldHandle(new URL(action, location.href), target)) start();
    });

    // Arrivée sur la nouvelle page : fondu de sortie une fois le rendu prêt
    const reveal = () => {
        let startedAt = 0;
        try { startedAt = Number(sessionStorage.getItem('ged:nav')) || 0; } catch (e) {}
        const remaining = startedAt ? Math.min(MIN_DURATION, Math.max(0, MIN_DURATION - (Date.now() - startedAt))) : 0;
        setTimeout(() => requestAnimationFrame(() => requestAnimationFrame(stop)), remaining);
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', reveal); else reveal();
    // Retour arrière (bfcache) : ne jamais revenir sur un splash figé
    window.addEventListener('pageshow', (e) => { if (e.persisted) stop(); });
})();
</script>
