<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentComment;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class DocumentCommentController extends Controller
{
    public function store(Request $request, Document $document)
    {
        $data = $request->validate([
            'content'     => 'required|string|max:2000',
            'parent_id'   => 'nullable|exists:document_comments,id',
            'type'        => 'nullable|in:comment,annotation',
            'is_internal' => 'nullable|boolean',
        ]);

        $comment = DocumentComment::create([
            'document_id' => $document->id,
            'user_id'     => auth()->id(),
            'parent_id'   => $data['parent_id'] ?? null,
            'content'     => $data['content'],
            'type'        => $data['type'] ?? 'comment',
            'is_internal' => $request->boolean('is_internal') && auth()->user()->hasRole('admin'),
        ]);

        app(NotificationService::class)->notifyComment($comment->load('user', 'document.creator'));

        app(\App\Services\DocumentArchivalService::class)->logAction(
            $document, 'commented', "Commentaire ajouté"
        );

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'comment' => $comment->load('user')]);
        }

        return back()->with('success', 'Commentaire ajouté.');
    }

    public function update(Request $request, Document $document, DocumentComment $comment)
    {
        if ($comment->user_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            abort(403);
        }

        $comment->update([
            'content'   => $request->validate(['content' => 'required|string|max:2000'])['content'],
            'edited_at' => now(),
        ]);

        return back()->with('success', 'Commentaire modifié.');
    }

    public function destroy(Document $document, DocumentComment $comment)
    {
        if ($comment->user_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            abort(403);
        }

        $comment->delete();
        return back()->with('success', 'Commentaire supprimé.');
    }
}
