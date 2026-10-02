<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\SignatureRequest;
use App\Services\SignatureWorkflow;
use App\Support\Tenant;
use Illuminate\Http\Request;

class SignatureRequestController extends Controller
{
    // Demander à un ou plusieurs utilisateurs de signer le document
    public function store(Request $request, Document $document)
    {
        if (!$document->canManage()) {
            abort(403, 'Seul le créateur ou un administrateur peut demander des signatures.');
        }
        $data = $request->validate([
            'signers'   => 'required|array|min:1|max:20',
            'signers.*' => ['distinct', Tenant::exists('users')],
            'message'   => 'nullable|string|max:1000',
            'due_days'  => 'nullable|integer|min:1|max:365',
        ]);

        try {
            $signers = app(SignatureWorkflow::class)->request(
                $document, auth()->user(), $data['signers'], $data['message'] ?? null, isset($data['due_days']) ? (int) $data['due_days'] : null
            );
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        $skipped = count($data['signers']) - $signers->count();
        $message = $signers->count() . ' demande(s) de signature envoyée(s).';
        if ($skipped > 0) {
            $message .= " {$skipped} ignorée(s) (demande déjà en attente).";
        }

        return back()->with('success', $message);
    }

    // Annuler une demande (demandeur, créateur du document ou administrateur)
    public function cancel(Document $document, SignatureRequest $signatureRequest)
    {
        if ($signatureRequest->document_id !== $document->id) {
            abort(404);
        }
        if (!$document->canManage() && $signatureRequest->requested_by !== auth()->id()) {
            abort(403);
        }
        if (!$signatureRequest->isPending()) {
            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        app(SignatureWorkflow::class)->cancel($signatureRequest, auth()->user());

        return back()->with('success', 'Demande de signature annulée.');
    }

    // Le signataire refuse de signer
    public function decline(Request $request, Document $document, SignatureRequest $signatureRequest)
    {
        if ($signatureRequest->document_id !== $document->id) {
            abort(404);
        }
        if ($signatureRequest->user_id !== auth()->id()) {
            abort(403);
        }
        if (!$signatureRequest->isPending()) {
            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        $data = $request->validate(['reason' => 'required|string|max:1000']);

        app(SignatureWorkflow::class)->decline($signatureRequest, auth()->user(), $data['reason']);

        return back()->with('success', 'Refus enregistré. Le demandeur a été prévenu.');
    }
}
