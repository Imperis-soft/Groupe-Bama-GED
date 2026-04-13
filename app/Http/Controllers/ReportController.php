<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use App\Models\DocumentAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        $stats = [
            'total'        => Document::count(),
            'draft'        => Document::where('status', 'draft')->count(),
            'review'       => Document::where('status', 'review')->count(),
            'approved'     => Document::where('status', 'approved')->count(),
            'archived'     => Document::where('status', 'archived')->count(),
            'expired'      => Document::expired()->count(),
            'confidential' => Document::confidential()->count(),
            'expiring_soon'=> Document::where('expires_at', '<=', now()->addDays(30))
                                ->where('expires_at', '>', now())
                                ->count(),
        ];

        // Activité par utilisateur (top 10)
        $userActivity = DocumentAuditLog::select('user_id', DB::raw('count(*) as total'))
            ->with('user')
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // Documents par catégorie
        $byCategory = Document::select('category_id', DB::raw('count(*) as total'))
            ->with('category')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->get();

        // Activité des 30 derniers jours
        $dailyActivity = DocumentAuditLog::select(
                DB::raw("DATE(created_at) as date"),
                DB::raw('count(*) as total')
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Documents expirés
        $expiredDocs = Document::expired()
            ->with('category', 'creator')
            ->latest('expires_at')
            ->limit(20)
            ->get();

        // Documents expirant bientôt
        $expiringSoon = Document::where('expires_at', '<=', now()->addDays(30))
            ->where('expires_at', '>', now())
            ->with('category', 'creator')
            ->orderBy('expires_at')
            ->limit(20)
            ->get();

        return view('reports.index', compact(
            'stats', 'userActivity', 'byCategory',
            'dailyActivity', 'expiredDocs', 'expiringSoon'
        ));
    }

    public function exportCsv(Request $request)
    {
        $query = Document::with(['category', 'creator']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($category = $request->input('category')) {
            $query->where('category_id', $category);
        }
        if ($request->input('expired')) {
            $query->expired();
        }

        $documents = $query->orderBy('created_at', 'desc')->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="documents_' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($documents) {
            $file = fopen('php://output', 'w');
            // BOM UTF-8 pour Excel
            fputs($file, "\xEF\xBB\xBF");

            fputcsv($file, [
                'Référence', 'Titre', 'Catégorie', 'Statut', 'Version',
                'Créateur', 'Confidentiel', 'Date création', 'Date expiration', 'Tags'
            ], ';');

            foreach ($documents as $doc) {
                fputcsv($file, [
                    $doc->reference,
                    $doc->title,
                    $doc->category?->name ?? 'Général',
                    $doc->status,
                    $doc->version,
                    $doc->creator?->full_name ?? '—',
                    $doc->is_confidential ? 'Oui' : 'Non',
                    $doc->created_at->format('d/m/Y H:i'),
                    $doc->expires_at?->format('d/m/Y') ?? '—',
                    is_array($doc->tags) ? implode(', ', $doc->tags) : ($doc->tags ?? ''),
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportAuditCsv(Request $request)
    {
        $logs = DocumentAuditLog::with(['document', 'user'])
            ->when($request->input('date_from'), fn($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->input('date_to'),   fn($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->orderByDesc('created_at')
            ->limit(5000)
            ->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="audit_' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, ['Date', 'Utilisateur', 'Action', 'Document', 'Description', 'IP'], ';');
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->created_at->format('d/m/Y H:i'),
                    $log->user?->full_name ?? 'Système',
                    $log->action,
                    $log->document?->title ?? '—',
                    $log->description ?? '—',
                    $log->ip_address ?? '—',
                ], ';');
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
