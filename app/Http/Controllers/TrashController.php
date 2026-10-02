<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\DocumentArchivalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TrashController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $query = Document::onlyTrashed()->with('category', 'creator');

        // Non-admin : seulement ses propres documents supprimés
        if (!$user->hasRole('admin')) {
            $query->where('creator_id', $user->id);
        }

        $documents = $query->latest('deleted_at')->paginate(20);

        return view('documents.trash', compact('documents'));
    }

    public function restore(int $id)
    {
        $document = Document::onlyTrashed()->findOrFail($id);

        if (!auth()->user()->hasRole('admin') && $document->creator_id !== auth()->id()) {
            abort(403, 'Vous ne pouvez restaurer que vos propres documents.');
        }

        $document->restore();
        app(DocumentArchivalService::class)->logAction($document, 'restored', 'Restauré depuis la corbeille');

        return back()->with('success', 'Document restauré : ' . $document->title);
    }

    public function forceDelete(int $id)
    {
        if (!auth()->user()->hasRole('admin')) abort(403);

        $document = Document::onlyTrashed()->findOrFail($id);

        if ($document->isUnderLegalHold()) {
            return back()->with('error', 'Ce document est sous gel juridique (Legal Hold) et ne peut pas être supprimé.');
        }

        app(DocumentArchivalService::class)->deleteStoredFiles($document);
        $document->forceDelete();

        return back()->with('success', 'Document supprimé définitivement.');
    }

    public function emptyTrash()
    {
        if (!auth()->user()->hasRole('admin')) abort(403);

        $trashed = Document::onlyTrashed()->where('legal_hold', false)->get();
        $service = app(DocumentArchivalService::class);
        foreach ($trashed as $doc) {
            $service->deleteStoredFiles($doc);
            $doc->forceDelete();
        }

        return back()->with('success', count($trashed) . ' document(s) supprimé(s) définitivement.');
    }
}
