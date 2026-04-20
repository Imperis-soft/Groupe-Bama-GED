@extends('emails.layout')
@section('content')

<div class="icon-wrap" style="background:#fef3c7;">
  <svg viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
  </svg>
</div>

<div class="title">Réinitialisation du mot de passe</div>
<div class="subtitle">
  Bonjour <strong>{{ $recipientName }}</strong>,<br>
  Vous avez demandé à réinitialiser votre mot de passe. Cliquez sur le bouton ci-dessous pour en choisir un nouveau.
</div>

<div class="btn-wrap" style="margin-bottom: 20px;">
  <a href="{{ $resetUrl }}" class="btn" style="background:#d97706; color:#fff; box-shadow:0 4px 14px rgba(217,119,6,0.35);">
    Réinitialiser mon mot de passe →
  </a>
</div>

<div class="info-box info-box-orange">
  <div class="info-box-dot" style="background:#d97706;"></div>
  <div class="info-box-text">
    Ce lien est valable <strong>60 minutes</strong> et ne peut être utilisé qu'une seule fois.
    Après expiration, vous devrez faire une nouvelle demande.
  </div>
</div>

<div class="info-box info-box-red" style="margin-top: 12px;">
  <div class="info-box-dot" style="background:#dc2626;"></div>
  <div class="info-box-text">
    Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.
    Votre mot de passe actuel reste inchangé.
  </div>
</div>

<div class="divider"></div>

<p style="font-size:11px; color:#94a3b8; text-align:center; line-height:1.6;">
  Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :<br>
  <span style="color:#d97706; font-weight:700; word-break:break-all;">{{ $resetUrl }}</span>
</p>

@endsection
