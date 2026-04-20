@extends('emails.layout')
@section('content')

<div class="icon-wrap" style="background:#f1f5f9;">
  <svg viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
  </svg>
</div>

<div class="title">{{ $subject }}</div>
<div class="subtitle">
  Bonjour <strong>{{ $recipientName }}</strong>,
</div>

<div class="info-box" style="background:#f8fafc; border:1px solid #e2e8f0;">
  <div class="info-box-dot" style="background:#64748b;"></div>
  <div class="info-box-text">{{ $bodyMessage }}</div>
</div>

@if(!empty($link))
<div class="btn-wrap">
  <a href="{{ $link }}" class="btn btn-orange">Voir sur la plateforme →</a>
</div>
@endif

@endsection
