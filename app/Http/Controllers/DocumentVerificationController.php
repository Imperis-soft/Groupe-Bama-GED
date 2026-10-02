<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DocumentVerification;
use Illuminate\Support\Facades\Log;

class DocumentVerificationController extends Controller
{

    // Vérification manuelle : saisie du code imprimé sous le QR code
    public function lookup(Request $request)
    {
        if ($request->filled('code')) {
            $verification = DocumentVerification::findByInput($request->input('code'));
            if ($verification) {
                return redirect()->route('verification.show', $verification->verification_code);
            }
            return $this->secured(response()->view('verification.lookup', ['notFound' => true], 404));
        }

        return $this->secured(response()->view('verification.lookup', ['notFound' => false]));
    }

    // Afficher la page de vérification
    public function show(Request $request, $code)
    {
        $verification = DocumentVerification::with(['document.creator', 'document.category', 'document.organization'])
            ->where('verification_code', $code)->first();

        if (!$verification || !$verification->document) {
            Log::warning('Vérification QR : code inconnu', ['code' => substr((string) $code, 0, 64), 'ip' => $request->ip()]);
            return $this->secured(response()->view('verification.invalid', [], 404));
        }

        // Dernier contrôle (traçabilité des scans)
        $verification->forceFill([
            'verified_at' => now(),
            'ip_address'  => $request->ip(),
            'user_agent'  => substr((string) $request->userAgent(), 0, 255),
        ])->save();

        $document = $verification->document;

        return $this->secured(response()->view('verification.show', [
            'verification' => $verification,
            'document'     => $document,
            'state'        => $this->state($document),
            'shortCode'    => DocumentVerification::displayCode($verification->verification_code),
        ]));
    }

    /**
     * État affiché sur la page : seule une version approuvée et en vigueur est « valide ».
     *
     * @return array{level: string, title: string, message: string}
     */
    private function state($document): array
    {
        return match (true) {
            $document->trashed() => ['level' => 'danger', 'title' => 'DOCUMENT RETIRÉ',
                'message' => 'Ce document a bien été émis par l\'organisation mais il a été retiré. Il ne doit plus être utilisé.'],
            $document->status === 'rejected' => ['level' => 'danger', 'title' => 'DOCUMENT REJETÉ',
                'message' => 'Ce document a été rejeté lors de sa validation. Il n\'a aucune valeur officielle.'],
            $document->expires_at && $document->expires_at->isPast() => ['level' => 'warning', 'title' => 'DOCUMENT EXPIRÉ',
                'message' => 'Ce document est authentique mais sa date d\'échéance est dépassée (' . $document->expires_at->format('d/m/Y') . ').'],
            $document->status === 'archived' => ['level' => 'info', 'title' => 'DOCUMENT AUTHENTIQUE — ARCHIVÉ',
                'message' => 'Ce document est authentique. Il est archivé et n\'est plus la référence en vigueur.'],
            in_array($document->status, ['draft', 'review', 'pending'], true) => ['level' => 'warning', 'title' => 'DOCUMENT NON VALIDÉ',
                'message' => 'Ce document existe bien dans la GED mais il n\'est pas encore approuvé (' . mb_strtolower(statusLabel($document->status)) . '). Il n\'a pas de valeur officielle.'],
            $document->status === 'signing' || ($document->status === 'approved' && $document->workflowRule()['signature']) => ['level' => 'warning', 'title' => 'SIGNATURES EN ATTENTE',
                'message' => 'Ce document est approuvé mais toutes les signatures requises n\'ont pas encore été recueillies. Il n\'a pas encore de valeur officielle.'],
            default => ['level' => 'success', 'title' => 'DOCUMENT AUTHENTIQUE',
                'message' => 'Ce document a été émis et approuvé par l\'organisation. Il est en vigueur.'],
        };
    }

    // Pages de vérification : jamais indexées, jamais mises en cache, code jamais transmis à un site tiers
    private function secured($response)
    {
        return $response->withHeaders([
            'X-Robots-Tag'    => 'noindex, nofollow',
            'Referrer-Policy' => 'no-referrer',
            'Cache-Control'   => 'no-store, private',
        ]);
    }

    // Traiter la vérification du document
    public function verify(Request $request, $code)
    {
        $verification = DocumentVerification::where('verification_code', $code)->first();

        if (!$verification) {
            return response()->json(['error' => 'Code de vérification invalide'], 404);
        }

        // Collecter les informations de l'appareil
        $deviceInfo = [
            'platform' => $request->input('platform'),
            'browser' => $request->input('browser'),
            'version' => $request->input('version'),
            'mobile' => $request->boolean('mobile'),
            'screen_resolution' => $request->input('screen_resolution'),
            'language' => $request->input('language'),
            'timezone' => $request->input('timezone'),
            'cookies_enabled' => $request->boolean('cookies_enabled'),
            'verified_at' => now(),
        ];

        // Mettre à jour la vérification
        $verification->update([
            'verified_at' => now(),
            'device_info' => $deviceInfo,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Log pour audit
        Log::info('Document vérifié', [
            'document_id' => $verification->document_id,
            'code' => $code,
            'ip' => $request->ip(),
            'device' => $deviceInfo,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Document vérifié avec succès',
            'document' => [
                'reference' => $verification->document->reference,
                'title' => $verification->document->title,
                'created_at' => $verification->document->created_at->format('d/m/Y H:i'),
                'creator' => $verification->document->creator->full_name,
            ]
        ]);
    }
}
