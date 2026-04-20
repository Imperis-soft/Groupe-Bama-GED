@extends('emails.layout')
@section('content')

<div class="icon-wrap" style="background:#fdf4ff;">
  <svg viewBox="0 0 24 24" fill="none" stroke="#9333ea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
  </svg>
</div>

<div class="title">Nouveau commentaire</div>
<div class="subtitle">
  Bonjour <strong>{{ $recipientName }}</strong>,
  <strong>{{ $commentBy }}</strong> a laissé un commentaire sur votre document.
</div>

<div class="doc-card">
  <div class="doc-card-label">Document concerné</div>
  <div class="doc-card-title">{{ $documentTitle }}</div>
  <div class="doc-card-ref">{{ $documentRef }}</div>
</div>

<div class="info-box" style="background:#fdf4ff; border:1px solid #e9d5ff;">
  <div class="info-box-dot" style="background:#9333ea;"></div>
  <div class="info-box-text">
    <strong>{{ $commentBy }} :</strong><br>
    {{ $commentContent }}
  </div>
</div>

<div class="btn-wrap">
  <a href="{{ $link }}" class="btn" style="background:#9333ea; color:#fff; box-shadow:0 4px 14px rgba(147,51,234,0.30);">
    Voir le commentaire →
  </a>
</div>

@endsection
