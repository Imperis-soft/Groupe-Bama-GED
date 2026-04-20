<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Document;
use App\Models\Category;
use App\Models\User;
use App\Models\ApprovalStep;

class DashboardController extends Controller
{

    // Afficher le tableau de bord avec les métriques clés
    public function index(Request $request)
    {
        $user    = auth()->user();
        $isAdmin = $user->hasRole('admin');

        // Basic counts — admin voit tout, les autres voient leurs docs + partagés
        $baseQuery = fn() => $isAdmin ? Document::query() : Document::visibleTo();

        $documentsCount    = $baseQuery()->count();
        $categoriesCount   = Category::count();
        $usersCount        = $isAdmin ? User::count() : null;

        // Archival metrics
        $archivedCount     = $baseQuery()->where('status', 'archived')->count();
        $expiredCount      = $baseQuery()->expired()->count();
        $confidentialCount = $isAdmin ? Document::confidential()->count() : null;
        $draftCount        = $baseQuery()->where('status', 'draft')->count();
        $reviewCount       = $baseQuery()->where('status', 'review')->count();

        // Storage usage
        $storageUsed = 0;
        try {
            if (DB::getDriverName() === 'pgsql') {
                $storageUsed = $baseQuery()->sum(DB::raw("COALESCE((metadata->>'size')::bigint, 0)"));
            } else {
                $storageUsed = $baseQuery()->sum(DB::raw("COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.size')) AS UNSIGNED), 0)"));
            }
        } catch (\Exception $e) {
            $storageUsed = 0;
        }

        // Recent documents (visibles uniquement)
        $recentDocuments = $baseQuery()->latest()->limit(6)->get();

        // Recent audit activities (admin voit tout, autres voient leurs docs)
        $recentActivities = \App\Models\DocumentAuditLog::with(['document', 'user'])
            ->when(!$isAdmin, function ($q) use ($user) {
                $q->whereHas('document', function ($d) use ($user) {
                    $d->visibleTo($user->id);
                });
            })
            ->latest()
            ->limit(10)
            ->get();

        // DB health (simple)
        try {
            DB::connection()->getPdo();
            $dbStatus = 'connected';
        } catch (\Exception $e) {
            $dbStatus = 'down: ' . $e->getMessage();
        }

        // Storage (S3 / MinIO) health: try list recent objects in documents/ (non-blocking)
        $storageStatus = 'unknown';
        try {
            if (Storage::disk('s3')->exists('documents')) {
                $storageStatus = 'ok';
            } else {
                $storageStatus = 'ok (no documents prefix)';
            }
        } catch (\Exception $e) {
            $storageStatus = 'down: ' . $e->getMessage();
        }

        // quick disk free space (server): only for local environment
        $diskFree = null;
        try {
            $diskFree = disk_free_space(base_path());
        } catch (\Exception $e) {
            $diskFree = null;
        }

        // Métriques spécifiques aux non-admins
        $sharedWithMeCount   = 0;
        $pendingApprovalsCount = 0;
        if (!$isAdmin) {
            $sharedWithMeCount = \App\Models\DocumentShare::where('shared_with', $user->id)
                ->where('is_active', true)
                ->where(function ($q) { $q->whereNull('expires_at')->orWhere('expires_at', '>', now()); })
                ->count();

            $pendingApprovalsCount = \App\Models\ApprovalStep::where('approver_id', $user->id)
                ->where('status', 'pending')
                ->count();
        }

        return view('dashboard', compact(
            'documentsCount', 'categoriesCount', 'usersCount', 'recentDocuments', 'dbStatus', 'storageStatus', 'diskFree',
            'archivedCount', 'expiredCount', 'confidentialCount', 'draftCount', 'reviewCount', 'storageUsed', 'recentActivities',
            'isAdmin', 'sharedWithMeCount', 'pendingApprovalsCount'
        ));
    }
}
