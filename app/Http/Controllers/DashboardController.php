<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Document;
use App\Models\Category;
use App\Models\User;

class DashboardController extends Controller
{

    // Afficher le tableau de bord avec les métriques clés
    public function index(Request $request)
    {
        // Basic counts
        $documentsCount = Document::count();
        $categoriesCount = Category::count();
        $usersCount = User::count();

        // Archival metrics
        $archivedCount = Document::where('status', 'archived')->count();
        $expiredCount = Document::expired()->count();
        $confidentialCount = Document::confidential()->count();
        $draftCount = Document::where('status', 'draft')->count();
        $reviewCount = Document::where('status', 'review')->count();

        // Storage usage - compatible MySQL et PostgreSQL
        $storageUsed = 0;
        try {
            if (DB::getDriverName() === 'pgsql') {
                $storageUsed = Document::sum(DB::raw("COALESCE((metadata->>'size')::bigint, 0)"));
            } else {
                $storageUsed = Document::sum(DB::raw("COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.size')) AS UNSIGNED), 0)"));
            }
        } catch (\Exception $e) {
            $storageUsed = 0;
        }

        // Recent documents
        $recentDocuments = Document::latest()->limit(6)->get();

        // Recent audit activities
        $recentActivities = \App\Models\DocumentAuditLog::with(['document', 'user'])
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

        return view('dashboard', compact(
            'documentsCount', 'categoriesCount', 'usersCount', 'recentDocuments', 'dbStatus', 'storageStatus', 'diskFree',
            'archivedCount', 'expiredCount', 'confidentialCount', 'draftCount', 'reviewCount', 'storageUsed', 'recentActivities'
        ));
    }
}
