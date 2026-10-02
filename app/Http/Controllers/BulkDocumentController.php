<?php

namespace App\Http\Controllers;

use App\Support\Tenant;
use App\Models\Document;
use App\Services\DocumentArchivalService;
use Illuminate\Http\Request;

class BulkDocumentController extends Controller
{
    public function action(Request $request)
    {
        $data = $request->validate([
            'action'       => 'required|in:archive,delete,move_category',
            'document_ids' => 'required|array|min:1|max:50',
            'document_ids.*' => Tenant::exists('documents'),
            'category_id'  => ['nullable', Tenant::exists('categories')],
        ]);

        $documents = Document::visibleTo()->whereIn('id', $data['document_ids'])->get();
        $service   = app(DocumentArchivalService::class);
        $count     = 0;

        foreach ($documents as $doc) {
            $done = match($data['action']) {
                'archive' => $this->archiveDoc($doc, $service),
                'delete'  => $this->deleteDoc($doc, $service),
                'move_category' => $this->moveDoc($doc, $data['category_id'] ?? null, $service),
            };
            if ($done) $count++;
        }

        $skipped = count($data['document_ids']) - $count;
        $message = "{$count} document(s) traité(s) avec succès.";
        if ($skipped > 0) {
            $message .= " {$skipped} ignoré(s) (droits insuffisants, gel juridique, verrou ou circuit en cours).";
        }

        return back()->with('success', $message);
    }

    private function archiveDoc(Document $doc, DocumentArchivalService $service): bool
    {
        if (!$doc->canManage()) return false;
        return $service->archiveDocument($doc, 'Archivage en masse');
    }

    private function deleteDoc(Document $doc, DocumentArchivalService $service): bool
    {
        if (!auth()->user()->hasRole('admin') || $doc->isUnderLegalHold() || $doc->isArchived()) return false;
        // Soft delete : le fichier reste sur MinIO tant que le document est en corbeille
        $service->logAction($doc, 'deleted', 'Suppression en masse');
        $doc->delete();
        return true;
    }

    private function moveDoc(Document $doc, $categoryId, DocumentArchivalService $service): bool
    {
        if (!$categoryId || !$doc->canEdit() || !auth()->user()->canFileInCategory((int) $categoryId) || $doc->isUnderLegalHold() || $doc->isLockedByOther() || $doc->isInWorkflow()) return false;
        $old = $doc->category_id;
        $doc->update(['category_id' => $categoryId]);
        $service->logAction($doc, 'updated', 'Catégorie modifiée (action en masse)', ['category_id' => $old], ['category_id' => $categoryId]);
        return true;
    }
}
