<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;

class DocumentFavoriteController extends Controller
{
    public function toggle(Document $document)
    {
        $user = auth()->user();
        $user->favorites()->toggle($document->id);

        $isFav = $user->favorites()->where('document_id', $document->id)->exists();

        if (request()->expectsJson()) {
            return response()->json(['favorited' => $isFav]);
        }

        return back()->with('success', $isFav ? 'Ajouté aux favoris.' : 'Retiré des favoris.');
    }

    public function index()
    {
        $documents = auth()->user()->favorites()
            ->with('category', 'creator')
            ->latest('document_favorites.created_at')
            ->paginate(15);

        return view('documents.favorites', compact('documents'));
    }
}
