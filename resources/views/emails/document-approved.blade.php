@extends('emails.layout')
@section('content')

<div class="icon-wrap" style="background:#f0fdf4;">
  <svg viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
  </svg>
</div>

<div class="title">Document approuvé ✓</div>
<div class="subtitle">
  Bonjour <strong>{{ $recipientName }}</strong>, votre document a été <strong style="color:#16a34a;">approuvé</strong> par tous les validateurs.
</div>

<div class="doc-card">
  <div class="doc-card-label">Document approuvé</div>
  <div class="doc-card-title">{{ $documentTitle }}</div>
  <div class="doc-card-ref">{{ $documentRef }}</div>
  <div class="doc-card-meta">
    <div class="doc-card-meta-item">Statut : <span style="color:#16a34a;">✓ Approuvé</span></div>
    <div class="doc-card-meta-item">Date : <span>{{ now()->format('d/m/Y') }}</span></div>
  </div>
</div>

<div class="info-box info-box-green">
  <div class="info-box-dot" style="background:#16a34a;"></div>
  <div class="info-box-text">
    Toutes les étapes de validation ont été complétées avec succès. Le document est maintenant <strong>officiel et actif</strong>.
  </div>
</div>

<div class="btn-wrap">
  <a href="{{ $link }}" class="btn btn-green">Voir le document →</a>
</div>

@endsection
