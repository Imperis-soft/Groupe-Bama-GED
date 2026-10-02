<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentSignature;
use App\Services\NotificationService;
use App\Services\SignatureWorkflow;
use App\Services\DocumentArchivalService;
use Illuminate\Http\Request;

class DocumentSignatureController extends Controller
{
    public function index(Document $document)
    {
        if (!$document->canView()) {
            abort(403, 'Accès refusé à ce document.');
        }
        $signatures = $document->signatures()->with('user')->latest()->get();
        $requests = $document->signatureRequests()->with(['signer', 'requester'])->get();
        $myRequest = $requests->first(fn ($r) => $r->isPending() && $r->user_id === auth()->id());
        $users = $document->canManage() ? \App\Models\User::orderBy('full_name')->get() : collect();
        $currentChecksum = $document->currentChecksum();
        return view('documents.signatures', compact('document', 'signatures', 'requests', 'myRequest', 'users', 'currentChecksum'));
    }

    // Hash de signature : lie la signature au signataire, au document ET à son contenu
    private function computeHash(string $signatureData, int $userId, int $documentId, int $timestamp, ?string $documentChecksum): string
    {
        $payload = $signatureData . $userId . $documentId . $timestamp;
        // Les signatures antérieures (sans empreinte) restent vérifiables
        if ($documentChecksum) {
            $payload .= $documentChecksum;
        }
        return hash('sha256', $payload);
    }

    public function store(Request $request, Document $document)
    {
        if (!$document->canView()) {
            abort(403, 'Accès refusé à ce document.');
        }
        if ($document->isArchived()) {
            return response()->json(['message' => 'Un document archivé est figé : il ne peut plus être signé.'], 422);
        }
        // Seules les personnes sollicitées signent, pendant la série de signatures
        $pending = $document->signatureRequests()->where('user_id', auth()->id())->where('status', 'pending')->with('requester')->get();
        if ($pending->isEmpty() || $document->status !== 'signing') {
            return response()->json(['message' => 'Aucune signature ne vous est demandée sur ce document.'], 403);
        }

        $data = $request->validate([
            'signature_data' => ['required', 'string', 'max:2000000', 'regex:/^data:image\/(png|jpeg);base64,/'], // base64
            'reason'         => 'nullable|string|max:500',
            'page_number'    => 'nullable|integer|min:1',
        ]);

        $signedAt = now();
        $checksum = $document->currentChecksum();
        $hash     = $this->computeHash($data['signature_data'], auth()->id(), $document->id, $signedAt->timestamp, $checksum);

        $signature = DocumentSignature::create([
            'document_id'       => $document->id,
            'user_id'           => auth()->id(),
            'signature_data'    => $data['signature_data'],
            'signature_hash'    => $hash,
            'document_checksum' => $checksum,
            'document_version'  => $document->version,
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
            'page_number'    => $data['page_number'] ?? null,
            'reason'         => $data['reason'] ?? null,
            'status'         => 'signed',
            'signed_at'      => $signedAt,
        ]);

        app(DocumentArchivalService::class)->logAction(
            $document, 'signed',
            'Document signé par ' . auth()->user()->full_name . " (version {$document->version})",
            null, ['version' => $document->version, 'checksum' => $checksum, 'signature_hash' => $hash]
        );

        // Clôturer les demandes de signature en attente pour ce signataire
        foreach ($pending as $signatureRequest) {
            $signatureRequest->update(['status' => 'signed', 'signature_id' => $signature->id, 'decided_at' => $signedAt]);
            if ($signatureRequest->requester && $signatureRequest->requester->id !== auth()->id()) {
                app(NotificationService::class)->notify(
                    $signatureRequest->requester,
                    'signature_completed',
                    'Document signé',
                    auth()->user()->full_name . " a signé « {$document->title} ».",
                    route('documents.signatures', $document),
                    $document
                );
            }
        }

        // Dernière signature attendue : le document passe « Signé »
        app(SignatureWorkflow::class)->settle($document);

        return response()->json([
            'success'   => true,
            'signature' => $signature->load('user'),
            'hash'      => $hash,
        ]);
    }

    public function verify(Document $document, DocumentSignature $signature)
    {
        if ($signature->document_id !== $document->id) {
            abort(404);
        }
        if (!$document->canView()) {
            abort(403, 'Accès refusé à ce document.');
        }

        $isValid = hash_equals(
            $signature->signature_hash,
            $this->computeHash($signature->signature_data, $signature->user_id, $document->id, $signature->signed_at->timestamp, $signature->document_checksum)
        );

        // Le document a-t-il changé depuis la signature ?
        $documentUnchanged = $signature->document_checksum
            ? hash_equals($signature->document_checksum, (string) $document->currentChecksum())
            : null;

        return response()->json([
            'valid'              => $isValid,
            'document_unchanged' => $documentUnchanged,
            'signed_version'     => $signature->document_version,
            'signer'    => $signature->user->full_name,
            'signed_at' => $signature->signed_at->format('d/m/Y H:i'),
        ]);
    }
}
