<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $subject ?? 'Groupe Bama GED' }}</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { background: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; }
  .wrapper { max-width: 600px; margin: 40px auto; padding: 0 16px 40px; }
  .card { background: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.07); }

  /* Header */
  .header { background: #0f172a; padding: 28px 36px; }
  .header-inner { display: flex; align-items: center; gap: 14px; }
  .logo-box { width: 42px; height: 42px; background: #ea580c; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
  .logo-box svg { width: 22px; height: 22px; fill: white; }
  .brand-name { font-size: 16px; font-weight: 900; color: #ffffff; letter-spacing: -0.3px; }
  .brand-sub { font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 1.5px; margin-top: 1px; }

  /* Accent bar */
  .accent-bar { height: 4px; background: linear-gradient(90deg, #ea580c, #f97316); }

  /* Body */
  .body { padding: 36px; }
  .icon-wrap { width: 56px; height: 56px; border-radius: 16px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; }
  .icon-wrap svg { width: 28px; height: 28px; }
  .title { font-size: 22px; font-weight: 900; color: #0f172a; letter-spacing: -0.5px; line-height: 1.2; margin-bottom: 10px; }
  .subtitle { font-size: 14px; color: #64748b; line-height: 1.6; margin-bottom: 24px; }

  /* Document card */
  .doc-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px 20px; margin-bottom: 24px; }
  .doc-card-label { font-size: 9px; font-weight: 900; color: #94a3b8; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 6px; }
  .doc-card-title { font-size: 15px; font-weight: 800; color: #1e293b; margin-bottom: 4px; }
  .doc-card-ref { font-size: 11px; font-weight: 700; color: #94a3b8; font-family: 'Courier New', monospace; }
  .doc-card-meta { display: flex; gap: 16px; margin-top: 12px; flex-wrap: wrap; }
  .doc-card-meta-item { font-size: 11px; color: #64748b; font-weight: 600; }
  .doc-card-meta-item span { color: #1e293b; font-weight: 700; }

  /* CTA button */
  .btn-wrap { text-align: center; margin-bottom: 28px; }
  .btn { display: inline-block; padding: 14px 32px; border-radius: 12px; font-size: 13px; font-weight: 900; text-decoration: none; letter-spacing: 0.3px; }
  .btn-orange { background: #ea580c; color: #ffffff; box-shadow: 0 4px 14px rgba(234,88,12,0.35); }
  .btn-green  { background: #16a34a; color: #ffffff; box-shadow: 0 4px 14px rgba(22,163,74,0.30); }
  .btn-blue   { background: #2563eb; color: #ffffff; box-shadow: 0 4px 14px rgba(37,99,235,0.30); }

  /* Info box */
  .info-box { border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; display: flex; gap: 12px; align-items: flex-start; }
  .info-box-orange { background: #fff7ed; border: 1px solid #fed7aa; }
  .info-box-green  { background: #f0fdf4; border: 1px solid #bbf7d0; }
  .info-box-red    { background: #fef2f2; border: 1px solid #fecaca; }
  .info-box-blue   { background: #eff6ff; border: 1px solid #bfdbfe; }
  .info-box-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; margin-top: 5px; }
  .info-box-text { font-size: 13px; color: #374151; line-height: 1.5; }
  .info-box-text strong { font-weight: 800; color: #1e293b; }

  /* Divider */
  .divider { height: 1px; background: #f1f5f9; margin: 24px 0; }

  /* Footer */
  .footer { padding: 20px 36px 28px; text-align: center; }
  .footer-text { font-size: 11px; color: #94a3b8; line-height: 1.7; }
  .footer-text a { color: #ea580c; text-decoration: none; font-weight: 700; }
  .footer-brand { font-size: 12px; font-weight: 900; color: #cbd5e1; margin-bottom: 6px; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="card">

    {{-- Header --}}
    <div class="header">
      <div class="header-inner">
        <div class="logo-box">
          <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM9 17H7v-1h2v1zm0-3H7v-1h2v1zm0-3H7v-1h2v1zm8 6h-6v-1h6v1zm0-3h-6v-1h6v1zm0-3h-6v-1h6v1z"/></svg>
        </div>
        <div>
          <div class="brand-name">Groupe Bama</div>
          <div class="brand-sub">GED Platform</div>
        </div>
      </div>
    </div>
    <div class="accent-bar"></div>

    {{-- Content --}}
    <div class="body">
      @yield('content')
    </div>

    <div class="divider" style="margin: 0;"></div>

    {{-- Footer --}}
    <div class="footer">
      <div class="footer-brand">Groupe Bama — GED</div>
      <div class="footer-text">
        Cet email a été envoyé automatiquement, merci de ne pas y répondre.<br>
        <a href="{{ config('app.url') }}">Accéder à la plateforme</a>
      </div>
    </div>

  </div>
</div>
</body>
</html>
