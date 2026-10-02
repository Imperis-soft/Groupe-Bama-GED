<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Certificat de validation — {{ $document->reference }}</title>
<style>
    @page { margin: 28mm 16mm 22mm; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; color: #0f172a; }
    header { position: fixed; top: -20mm; left: 0; right: 0; height: 16mm; border-bottom: 1px solid #e2e8f0; }
    header img { height: 11mm; vertical-align: middle; }
    header .org { display: inline-block; vertical-align: middle; margin-left: 3mm; }
    header .org b { font-size: 11pt; }
    header .ref { position: absolute; right: 0; top: 3mm; text-align: right; color: #64748b; font-size: 8pt; }
    footer { position: fixed; bottom: -15mm; left: 0; right: 0; font-size: 7pt; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 2mm; }
    h1 { font-size: 15pt; margin: 0 0 1mm; }
    h2 { font-size: 10pt; margin: 6mm 0 2mm; text-transform: uppercase; color: #475569; }
    .sub { color: #64748b; margin: 0 0 6mm; }
    .box { border: 1px solid #e2e8f0; border-radius: 2mm; padding: 3mm 4mm; }
    .box table td { padding: 0.8mm 0; vertical-align: top; }
    .box td.k { color: #64748b; width: 38mm; }
    .qr { width: 42mm; text-align: center; vertical-align: top; padding-left: 5mm; }
    .qr img { width: 38mm; height: 38mm; }
    .code { font-family: 'DejaVu Sans Mono', monospace; font-size: 9pt; font-weight: bold; letter-spacing: 0.5mm; }
    table.list { width: 100%; border-collapse: collapse; font-size: 8pt; }
    table.list th { background: #f1f5f9; text-align: left; padding: 1.8mm 1.5mm; font-size: 7pt; text-transform: uppercase; color: #475569; }
    table.list td { border-bottom: 1px solid #f1f5f9; padding: 1.8mm 1.5mm; vertical-align: top; }
    .mono { font-family: 'DejaVu Sans Mono', monospace; font-size: 6.5pt; color: #64748b; word-break: break-all; }
    .forced { color: #b91c1c; font-size: 7pt; }
    .muted { color: #94a3b8; }
    .sig { height: 12mm; }
    .status { display: inline-block; padding: 1mm 3mm; border-radius: 1.5mm; background: #dcfce7; color: #166534; font-weight: bold; }
</style>
</head>
<body>
<header>
    <img src="{{ $logo }}" alt="">
    <span class="org"><b>{{ $organization?->name }}</b><br><span style="color:#64748b">Certificat de validation</span></span>
    <div class="ref">{{ $document->reference }} — version {{ $document->version }}<br>Émis le {{ now()->format('d/m/Y H:i') }}</div>
</header>
<footer>
    Ce certificat atteste les validations enregistrées dans la GED pour le fichier dont l'empreinte figure ci-dessus.
    Toute modification du fichier change son empreinte : vérifiez l'authenticité en scannant le QR code ou en saisissant le code sur la page de vérification.
</footer>

<h1>Certificat de validation</h1>
<p class="sub">{{ $document->is_confidential ? 'Document confidentiel' : $document->title }}</p>

<table style="width:100%"><tr>
    <td class="box" style="vertical-align: top">
        <table>
            <tr><td class="k">Référence</td><td><b>{{ $document->reference }}</b></td></tr>
            <tr><td class="k">Version validée</td><td>{{ $document->version }}</td></tr>
            <tr><td class="k">Statut</td><td><span class="status">{{ statusLabel($document->status) }}</span></td></tr>
            <tr><td class="k">Auteur</td><td>{{ $document->creator?->full_name ?? '—' }}</td></tr>
            <tr><td class="k">Déposé le</td><td>{{ $document->created_at?->format('d/m/Y') }}</td></tr>
            <tr><td class="k">Empreinte SHA-256</td><td class="mono">{{ $checksum }}</td></tr>
            <tr><td class="k">Code de vérification</td><td class="code">{{ $code }}</td></tr>
        </table>
    </td>
    <td class="qr">
        <img src="{{ $qr }}" alt="QR code de vérification">
        <div class="muted" style="font-size:7pt">Scanner pour vérifier</div>
    </td>
</tr></table>

<h2>Approbations</h2>
@if($steps->isEmpty())
<p class="muted">Aucun circuit d'approbation pour cette version.</p>
@else
<table class="list">
    <tr><th style="width:8mm">#</th><th>Approbateur</th><th style="width:32mm">Date</th><th>Commentaire</th></tr>
    @foreach($steps as $step)
    <tr>
        <td>{{ $loop->iteration }}</td>
        <td>
            {{ $step->approver?->full_name }}
            @if($step->delegatedFrom)<br><span class="muted">en remplacement de {{ $step->delegatedFrom->full_name }}</span>@endif
            @if($step->forcedBy)<br><span class="forced">Décidé par {{ $step->forcedBy->full_name }} (administrateur) — motif : {{ $step->force_reason }}</span>@endif
        </td>
        <td>{{ $step->decided_at?->format('d/m/Y H:i') }}</td>
        <td>{{ $step->comment ?: '—' }}</td>
    </tr>
    @endforeach
</table>
@endif

<h2>Signatures</h2>
@if($signatures->isEmpty())
<p class="muted">Aucune signature pour cette version.</p>
@else
<table class="list">
    <tr><th>Signataire</th><th style="width:32mm">Date</th><th style="width:34mm">Signature</th><th>Empreinte de la signature</th></tr>
    @foreach($signatures as $signature)
    <tr>
        <td>{{ $signature->user?->full_name }}@if($signature->reason)<br><span class="muted">{{ $signature->reason }}</span>@endif</td>
        <td>{{ $signature->signed_at?->format('d/m/Y H:i') }}</td>
        <td>@if($signature->signature_data)<img class="sig" src="{{ $signature->signature_data }}" alt="">@endif</td>
        <td class="mono">{{ $signature->signature_hash }}</td>
    </tr>
    @endforeach
</table>
@endif
</body>
</html>
