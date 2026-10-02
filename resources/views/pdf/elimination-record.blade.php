<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>{{ $record->number }}</title>
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
    .sub { color: #64748b; margin: 0 0 6mm; }
    .box { border: 1px solid #e2e8f0; border-radius: 2mm; padding: 3mm 4mm; margin-bottom: 5mm; }
    .box table td { padding: 0.8mm 0; vertical-align: top; }
    .box td.k { color: #64748b; width: 42mm; }
    table.docs { width: 100%; border-collapse: collapse; font-size: 7.5pt; table-layout: fixed; }
    table.docs th { background: #f1f5f9; text-align: left; padding: 1.8mm 1.5mm; font-size: 7pt; text-transform: uppercase; color: #475569; }
    table.docs td { border-bottom: 1px solid #f1f5f9; padding: 1.8mm 1.5mm; vertical-align: top; }
    .mono { font-family: 'DejaVu Sans Mono', monospace; font-size: 6.5pt; color: #64748b; word-break: break-all; }
    .sign { margin-top: 10mm; width: 100%; }
    .sign td { width: 50%; vertical-align: top; padding-right: 6mm; }
    .line { border-bottom: 1px solid #94a3b8; height: 14mm; }
    .accent { color: #ea580c; }
</style>
</head>
<body>
<header>
    <img src="{{ $logo }}" alt="">
    <span class="org"><b>{{ $organization->name }}</b><br><span style="color:#64748b">Gestion électronique de documents</span></span>
    <div class="ref">{{ $record->number }}<br>{{ $record->created_at->format('d/m/Y H:i') }}</div>
</header>
<footer>
    Procès-verbal généré par {{ config('saas.platform_name') }} — {{ $record->number }} — les documents listés ont été détruits après validation ;
    leur journal d'audit est conservé. Empreinte de ce fichier enregistrée dans la plateforme.
</footer>

<h1>Procès-verbal d'élimination <span class="accent">{{ $record->number }}</span></h1>
<p class="sub">Destruction de documents arrivés au terme de leur durée de conservation.</p>

<div class="box">
    <table>
        <tr><td class="k">Entreprise</td><td>{{ $organization->name }}</td></tr>
        <tr><td class="k">Date de l'élimination</td><td>{{ $record->created_at->format('d/m/Y à H:i') }}</td></tr>
        <tr><td class="k">Validée par</td><td>{{ $record->approved_by_name }} (administrateur)</td></tr>
        <tr><td class="k">Nombre de documents</td><td>{{ $record->documents_count }}</td></tr>
        <tr><td class="k">Motif</td><td>{{ $record->reason }}</td></tr>
    </table>
</div>

<table class="docs">
    <thead>
        <tr><th style="width:17%">Référence</th><th style="width:41%">Titre / catégorie</th><th style="width:11%">Déposé</th><th style="width:13%">Conservation</th><th style="width:11%">Fin</th><th style="width:7%">Vers.</th></tr>
    </thead>
    <tbody>
    @foreach($record->documents as $doc)
        <tr>
            <td><b>{{ $doc['reference'] }}</b></td>
            <td>
                {{ $doc['title'] }}<br><span style="color:#64748b">{{ $doc['category'] ?? 'Sans catégorie' }}</span>
                @if($doc['checksum'])<div class="mono">SHA-256 {{ $doc['checksum'] }}</div>@endif
            </td>
            <td>{{ $doc['created_at'] ? \Carbon\Carbon::parse($doc['created_at'])->format('d/m/Y') : '—' }}</td>
            <td>{{ $doc['retention_years'] }} an(s)<br><span style="color:#64748b">dès : {{ $doc['trigger'] }}</span></td>
            <td>{{ $doc['retention_until'] ? \Carbon\Carbon::parse($doc['retention_until'])->format('d/m/Y') : '—' }}</td>
            <td>{{ $doc['versions'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<table class="sign">
    <tr>
        <td>Validé électroniquement par<br><b>{{ $record->approved_by_name }}</b><br>le {{ $record->created_at->format('d/m/Y à H:i') }}</td>
        <td>Visa (facultatif)<div class="line"></div></td>
    </tr>
</table>
</body>
</html>
