<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\DocumentArchivalService;
use Illuminate\Http\Request;

class DocumentArchiveController extends Controller
{
    // ===========================
    // LEGAL HOLD (Gel d'archive)
    // ===========================

    /**
     * Activer le gel juridique sur un document.
     */
    public function enableLegalHold(Request $request, Document $document)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Seul un administrateur peut activer le gel juridique.');
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $service = app(DocumentArchivalService::class);
        $service->enableLegalHold($document, $request->input('reason'));

        return back()->with('success', "Gel juridique activé sur le document {$document->reference}.");
    }

    /**
     * Désactiver le gel juridique sur un document.
     */
    public function disableLegalHold(Document $document)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Seul un administrateur peut lever le gel juridique.');
        }

        $service = app(DocumentArchivalService::class);
        $service->disableLegalHold($document);

        return back()->with('success', "Gel juridique levé sur le document {$document->reference}.");
    }

    // ===========================
    // COMPARAISON DE VERSIONS
    // ===========================

    /**
     * Afficher la page de comparaison de deux versions.
     */
    public function compareVersions(Request $request, Document $document)
    {
        if (!$document->canView()) {
            abort(403, 'Accès refusé à ce document.');
        }

        $request->validate([
            'version_a' => 'required|integer|min:1',
            'version_b' => 'required|integer|min:1|different:version_a',
        ]);

        $service = app(DocumentArchivalService::class);
        $comparison = $service->compareVersions(
            $document,
            (int) $request->input('version_a'),
            (int) $request->input('version_b')
        );

        if (!$comparison) {
            return back()->with('error', 'Une ou les deux versions spécifiées sont introuvables.');
        }

        return view('documents.compare-versions', compact('document', 'comparison'));
    }

    // ===========================
    // EXPORT ARCHIVE (ZIP)
    // ===========================

    /**
     * Exporter un document complet (toutes versions + audit + métadonnées) en ZIP.
     */
    public function exportArchive(Document $document)
    {
        if (!$document->canView()) {
            abort(403, 'Accès refusé à ce document.');
        }

        $service = app(DocumentArchivalService::class);
        $zipPath = $service->exportArchive($document);

        if (!$zipPath || !file_exists($zipPath)) {
            return back()->with('error', 'Erreur lors de la génération de l\'archive ZIP.');
        }

        $filename = "archive_{$document->reference}_" . now()->format('Ymd_His') . '.zip';

        return response()->download($zipPath, $filename, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }
}
