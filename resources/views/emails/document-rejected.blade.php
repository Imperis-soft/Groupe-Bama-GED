@extends('emails.layout')
@section('content')

<div class="icon-wrap" style="background:#fef2f2;">
  <svg viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
  </svg>
</div>

<div class="title">Document refusé</div>
<div class="subtitle">
  Bonjour <strong>{{ $recipientName }}</strong>, votre document a été <strong style="color:#dc2626;">refusé</strong> lors du processus de validation.
</div>

<div class="doc-card">
  <div class="doc-card-label">Document refusé</div>
  <div class="doc-card-title">{{ $documentTitle }}</div>
  <div class="doc-card-ref">{{ $documentRef }}</div>
  <div class="doc-card-meta">
    <div class="doc-card-meta-item">Refusé par : <span>{{ $rejectedBy }}</span></div>
    <div class="doc-card-meta-item">Date : <span>{{ now()->format('d/m/Y') }}</span></div>
  </div>
</div>

<div class="info-box info-box-red">
  <div class="info-box-dot" style="background:#dc2626;"></div>
  <div class="info-box-text">
    <strong>Raison du refus :</strong><br>
    {{ $reason }}
  </div>
</div>

<div class="info-box info-box-blue" style="margin-top:0;">
  <div class="info-box-dot" style="background:#2563eb;"></div>
  <div class="info-box-text">
    Vous pouvez modifier le document et relancer le processus de validation depuis la plateforme.
  </div>
</div>

<div class="btn-wrap">
  <a href="{{ $link }}" class="btn btn-blue">Modifier le document →</a>
</div>

@endsection
