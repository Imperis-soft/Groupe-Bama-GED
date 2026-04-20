<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentSignature;
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
        return view('documents.signatures', compact('document', 'signatures'));
    }

    public function store(Request $request, Document $document)
    {
        $data = $request->validate([
            'signature_data' => 'required|string', // base64
            'reason'         => 'nullable|string|max:500',
            'page_number'    => 'nullable|integer|min:1',
        ]);

        $hash = hash('sha256', $data['signature_data'] . auth()->id() . $document->id . now()->timestamp);

        $signature = DocumentSignature::create([
            'document_id'    => $document->id,
            'user_id'        => auth()->id(),
            'signature_data' => $data['signature_data'],
            'signature_hash' => $hash,
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
            'page_number'    => $data['page_number'] ?? null,
            'reason'         => $data['reason'] ?? null,
            'status'         => 'signed',
            'signed_at'      => now(),
        ]);

        app(DocumentArchivalService::class)->logAction(
            $document, 'signed',
            "Document signé par " . auth()->user()->full_name
        );

        return response()->json([
            'success'   => true,
            'signature' => $signature->load('user'),
            'hash'      => $hash,
        ]);
    }

    public function verify(Document $document, DocumentSignature $signature)
    {
        $isValid = hash_equals(
            $signature->signature_hash,
            hash('sha256', $signature->signature_data . $signature->user_id . $document->id . $signature->signed_at->timestamp)
        );

        return response()->json([
            'valid'     => $isValid,
            'signer'    => $signature->user->full_name,
            'signed_at' => $signature->signed_at->format('d/m/Y H:i'),
        ]);
    }
}
