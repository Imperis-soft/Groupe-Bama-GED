<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\EliminationRecord;
use App\Models\IntegrityCheck;
use App\Models\OrganizationExport;
use App\Services\IntegrityService;
use App\Services\RetentionService;
use App\Support\Tenant;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Fin de conservation (administrateur) : documents arrivés à terme, décisions et procès-verbaux d'élimination.
 */
class RetentionController extends Controller
{
    public function index(Request $request)
    {
        $tab = in_array($request->query('tab'), ['due', 'upcoming', 'records', 'integrity'], true) ? $request->query('tab') : 'due';

        $counts = [
            'due'      => Document::retentionDue()->count(),
            'upcoming' => Document::whereNotNull('retention_until')->where('retention_permanent', false)
                ->whereDate('retention_until', '>', today())->whereDate('retention_until', '<=', today()->addDays(90))->count(),
            'records'  => EliminationRecord::count(),
        ];

        $documents = null;
        $records   = null;
        $checks    = null;
        $exports   = null;
        if ($tab === 'integrity') {
            $checks  = IntegrityCheck::latest()->take(10)->get();
            $exports = OrganizationExport::with('requester')->latest()->take(10)->get();
        } elseif ($tab === 'records') {
            $records = EliminationRecord::latest()->paginate(20);
        } else {
            $query = $tab === 'due'
                ? Document::retentionDue()
                : Document::whereNotNull('retention_until')->where('retention_permanent', false)
                    ->whereDate('retention_until', '>', today())->whereDate('retention_until', '<=', today()->addDays(90));
            $documents = $query->with('category')->orderBy('retention_until')->paginate(50)->withQueryString();
        }

        return view('retention.index', compact('tab', 'counts', 'documents', 'records', 'checks', 'exports'));
    }

    // Contrôle d'intégrité immédiat de l'entreprise
    public function verify(IntegrityService $integrity)
    {
        $check = $integrity->check(Tenant::organization());

        return redirect()->route('retention.index', ['tab' => 'integrity'])->with($check->passed() ? 'success' : 'error', $check->passed()
            ? "Intégrité vérifiée : {$check->files_checked} fichier(s) intact(s), journal d'audit intact."
            : "Anomalies détectées : {$check->files_failed} fichier(s) en défaut" . ($check->audit_chain_ok ? '.' : ', journal d\'audit altéré.'));
    }

    // Export complet de l'entreprise (préparé en arrière-plan)
    public function export()
    {
        if (OrganizationExport::whereIn('status', ['pending', 'running'])->exists()) {
            return back()->with('error', 'Un export est déjà en préparation.');
        }

        $export = OrganizationExport::create(['requested_by' => auth()->id()]);
        \App\Jobs\ExportOrganization::dispatch($export->id);

        return redirect()->route('retention.index', ['tab' => 'integrity'])
            ->with('success', 'Export lancé : vous serez prévenu dès qu\'il sera prêt à télécharger.');
    }

    public function downloadExport(OrganizationExport $export)
    {
        $disk = Tenant::organization()->disk();
        abort_unless($export->isReady() && $disk->exists($export->path), 404, 'Export introuvable ou expiré.');

        return $disk->download($export->path, basename($export->path));
    }

    public function eliminate(Request $request, RetentionService $service)
    {
        $data = $request->validate([
            'documents'   => 'required|array|min:1|max:200',
            'documents.*' => Tenant::exists('documents'),
            'reason'      => 'required|string|min:10|max:1000',
            'confirm'     => 'accepted',
        ], [
            'reason.required'  => 'Indiquez le motif de l\'élimination (il figure sur le procès-verbal).',
            'reason.min'       => 'Indiquez le motif de l\'élimination (il figure sur le procès-verbal).',
            'confirm.accepted' => 'Confirmez que la destruction est définitive.',
        ]);

        try {
            $record = $service->eliminate(Document::whereIn('id', $data['documents'])->get(), $data['reason'], auth()->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $skipped = count($data['documents']) - $record->documents_count;

        return redirect()->route('retention.index', ['tab' => 'records'])->with('success',
            "{$record->documents_count} document(s) éliminé(s). Procès-verbal {$record->number} enregistré."
            . ($skipped ? " {$skipped} document(s) non éliminable(s) ont été laissés de côté (gel juridique, conservation définitive ou délai non échu)." : ''));
    }

    public function extend(Request $request, RetentionService $service)
    {
        $data = $request->validate([
            'documents'   => 'required|array|min:1|max:200',
            'documents.*' => Tenant::exists('documents'),
            'years'       => 'required|integer|min:1|max:50',
            'reason'      => 'required|string|min:5|max:1000',
        ], ['reason.required' => 'Indiquez le motif de la prolongation.', 'reason.min' => 'Indiquez le motif de la prolongation.']);

        $count = $service->extend(Document::whereIn('id', $data['documents'])->get(), (int) $data['years'], $data['reason']);

        return back()->with('success', "Conservation prolongée de {$data['years']} an(s) pour {$count} document(s).");
    }

    public function keep(Request $request, RetentionService $service)
    {
        $data = $request->validate([
            'documents'   => 'required|array|min:1|max:200',
            'documents.*' => Tenant::exists('documents'),
            'reason'      => 'required|string|min:5|max:1000',
        ], ['reason.required' => 'Indiquez le motif de la conservation définitive.', 'reason.min' => 'Indiquez le motif de la conservation définitive.']);

        $count = $service->keepPermanently(Document::whereIn('id', $data['documents'])->get(), $data['reason']);

        return back()->with('success', "{$count} document(s) conservé(s) définitivement.");
    }

    public function pdf(EliminationRecord $record)
    {
        $disk = Tenant::organization()->disk();
        abort_unless($record->pdf_path && $disk->exists($record->pdf_path), 404, 'Procès-verbal introuvable.');

        return $disk->download($record->pdf_path, $record->number . '.pdf');
    }
}
