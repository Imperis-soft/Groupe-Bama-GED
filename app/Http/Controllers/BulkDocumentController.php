<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\DocumentArchivalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BulkDocumentController extends Controller
{
    public function action(Request $request)
    {
        $data = $request->validate([
            'action'       => 'required|in:archive,delete,move_category,approve',
            'document_ids' => 'required|array|min:1|max:50',
            'document_ids.*' => 'exists:documents,id',
            'category_id'  => 'nullable|exists:categories,id',
        ]);

        $documents = Document::whereIn('id', $data['document_ids'])->get();
        $service   = app(DocumentArchivalService::class);
        $count     = 0;

        foreach ($documents as $doc) {
            match($data['action']) {
                'archive' => $this->archiveDoc($doc, $service),
                'delete'  => $this->deleteDoc($doc, $service),
                'move_category' => $doc->update(['category_id' => $data['category_id']]),
                'approve' => $this->approveDoc($doc, $service),
            };
            $count++;
        }

        return back()->with('success', "{$count} document(s) traité(s) avec succès.");
    }

    private function archiveDoc(Document $doc, DocumentArchivalService $service): void
    {
        $service->archiveDocument($doc, 'Archivage en masse');
    }

    private function deleteDoc(Document $doc, DocumentArchivalService $service): void
    {
        if (!auth()->user()->hasRole('admin')) return;
        Storage::disk('s3')->delete($doc->file_path);
        $service->logAction($doc, 'deleted', 'Suppression en masse');
        $doc->delete();
    }

    private function approveDoc(Document $doc, DocumentArchivalService $service): void
    {
        if (!auth()->user()->hasRole('admin')) return;
        $doc->update(['status' => 'approved']);
        $service->logAction($doc, 'approved', 'Approbation en masse');
    }
}
