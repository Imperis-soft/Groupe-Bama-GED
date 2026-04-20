@extends('emails.layout')
@section('content')

<div class="icon-wrap" style="background:#eff6ff;">
  <svg viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/>
    <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
  </svg>
</div>

<div class="title">Un document a été partagé avec vous</div>
<div class="subtitle">
  Bonjour <strong>{{ $recipientName }}</strong>,
  <strong>{{ $sharedBy }}</strong> vous a partagé un document sur la plateforme GED.
</div>

<div class="doc-card">
  <div class="doc-card-label">Document partagé</div>
  <div class="doc-card-title">{{ $documentTitle }}</div>
  <div class="doc-card-ref">{{ $documentRef }}</div>
  <div class="doc-card-meta">
    <div class="doc-card-meta-item">Accès : <span>
      @if($accessLevel === 'edit') Édition complète
      @elseif($accessLevel === 'comment') Lecture + Commentaires
      @else Lecture seule
      @endif
    </span></div>
    @if(!empty($expiresAt))
    <div class="doc-card-meta-item">Expire le : <span style="color:#ea580c;">{{ $expiresAt }}</span></div>
    @endif
  </div>
</div>

@if(!empty($message))
<div class="info-box info-box-blue">
  <div class="info-box-dot" style="background:#2563eb;"></div>
  <div class="info-box-text">
    <strong>Message de {{ $sharedBy }} :</strong><br>
    {{ $message }}
  </div>
</div>
@endif

<div class="btn-wrap">
  <a href="{{ $link }}" class="btn btn-blue">Accéder au document →</a>
</div>

@endsection
