<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TrashController extends Controller
{
    public function index()
    {
        $documents = Document::onlyTrashed()
            ->with('category', 'creator')
            ->latest('deleted_at')
            ->paginate(20);

        return view('documents.trash', compact('documents'));
    }

    public function restore(int $id)
    {
        $document = Document::onlyTrashed()->findOrFail($id);
        $document->restore();

        return back()->with('success', 'Document restauré : ' . $document->title);
    }

    public function forceDelete(int $id)
    {
        if (!auth()->user()->hasRole('admin')) abort(403);

        $document = Document::onlyTrashed()->findOrFail($id);

        // Supprimer le fichier physique
        Storage::disk('s3')->delete($document->file_path);

        $document->forceDelete();

        return back()->with('success', 'Document supprimé définitivement.');
    }

    public function emptyTrash()
    {
        if (!auth()->user()->hasRole('admin')) abort(403);

        $trashed = Document::onlyTrashed()->get();
        foreach ($trashed as $doc) {
            Storage::disk('s3')->delete($doc->file_path);
            $doc->forceDelete();
        }

        return back()->with('success', count($trashed) . ' document(s) supprimé(s) définitivement.');
    }
}
