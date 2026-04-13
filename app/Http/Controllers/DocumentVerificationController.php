<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DocumentVerification;
use Illuminate\Support\Facades\Log;

class DocumentVerificationController extends Controller
{
    // Afficher la page de vérification
    public function show($code)
    {
        $verification = DocumentVerification::where('verification_code', $code)->first();

        if (!$verification) {
            return view('verification.invalid');
        }

        return view('verification.show', compact('verification'));
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
