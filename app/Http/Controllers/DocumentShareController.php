<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentShare;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class DocumentShareController extends Controller
{
    public function index(Document $document)
    {
        $shares = $document->shares()->with('sharedWith', 'sharedBy')->latest()->get();
        $users  = User::where('id', '!=', auth()->id())->orderBy('full_name')->get();
        return view('documents.shares', compact('document', 'shares', 'users'));
    }

    public function store(Request $request, Document $document)
    {
        $data = $request->validate([
            'shared_with'  => 'nullable|exists:users,id',
            'access_level' => 'required|in:view,edit,comment',
            'message'      => 'nullable|string|max:500',
            'expires_at'   => 'nullable|date|after:today',
            'generate_link'=> 'nullable|boolean',
        ]);

        $share = DocumentShare::create([
            'document_id'  => $document->id,
            'shared_by'    => auth()->id(),
            'shared_with'  => $data['shared_with'] ?? null,
            'share_token'  => $request->boolean('generate_link') ? DocumentShare::generateToken() : null,
            'access_level' => $data['access_level'],
            'message'      => $data['message'] ?? null,
            'expires_at'   => $data['expires_at'] ?? null,
            'is_active'    => true,
        ]);

        // Notification
        if ($share->shared_with) {
            app(NotificationService::class)->notifyShare($share->load('sharedBy', 'sharedWith', 'document'));
        }

        // Log audit
        app(\App\Services\DocumentArchivalService::class)->logAction(
            $document, 'shared',
            "Partagé avec " . ($share->sharedWith?->full_name ?? 'lien public')
        );

        return back()->with('success', 'Document partagé avec succès.');
    }

    public function revoke(Document $document, DocumentShare $share)
    {
        $share->update(['is_active' => false]);
        return back()->with('success', 'Partage révoqué.');
    }

    // Accès via lien public
    public function accessByToken(string $token)
    {
        $share = DocumentShare::where('share_token', $token)->with('document')->firstOrFail();

        if (!$share->isValid()) {
            abort(403, 'Ce lien de partage est expiré ou révoqué.');
        }

        $share->update(['accessed_at' => now()]);

        return view('documents.shared-access', compact('share'));
    }
}
