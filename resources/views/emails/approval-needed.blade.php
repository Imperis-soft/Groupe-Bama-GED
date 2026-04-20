@extends('emails.layout')
@section('content')

<div class="icon-wrap" style="background:#fff7ed;">
  <svg viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
  </svg>
</div>

<div class="title">Votre validation est requise</div>
<div class="subtitle">
  Bonjour <strong>{{ $recipientName }}</strong>, un document attend votre approbation.
</div>

<div class="doc-card">
  <div class="doc-card-label">Document à valider</div>
  <div class="doc-card-title">{{ $documentTitle }}</div>
  <div class="doc-card-ref">{{ $documentRef }}</div>
  <div class="doc-card-meta">
    @if(!empty($category))
    <div class="doc-card-meta-item">Catégorie : <span>{{ $category }}</span></div>
    @endif
    @if(!empty($dueDate))
    <div class="doc-card-meta-item">À valider avant : <span style="color:#ea580c;">{{ $dueDate }}</span></div>
    @endif
    <div class="doc-card-meta-item">Étape : <span>{{ $stepOrder }}</span></div>
  </div>
</div>

<div class="info-box info-box-orange">
  <div class="info-box-dot" style="background:#ea580c;"></div>
  <div class="info-box-text">
    Connectez-vous à la plateforme pour <strong>valider ou refuser</strong> ce document. Votre décision sera enregistrée et le créateur sera notifié.
  </div>
</div>

<div class="btn-wrap">
  <a href="{{ $link }}" class="btn btn-orange">Voir et valider le document →</a>
</div>

@endsection
