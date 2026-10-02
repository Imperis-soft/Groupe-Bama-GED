<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Lecture / écriture des paramètres de la table `settings`.
 * organization_id NULL = plateforme, sinon paramètres propres à une entreprise.
 * Uniquement utilisé par l'espace super admin.
 */
abstract class SiteConfigController extends Controller
{
    // Clés texte autorisées
    protected const TEXT_KEYS = [
        'mail_host', 'mail_port', 'mail_encryption', 'mail_username', 'mail_from_address', 'mail_from_name',
        'lock_timeout_min',
    ];

    // Cases à cocher (absentes du formulaire = décochées)
    protected const CHECKBOX_KEYS = ['mail_enabled'];

    protected function loadSettings(?int $organizationId): array
    {
        $settings = DB::table('settings')->where('organization_id', $organizationId)->pluck('value', 'key')->toArray();

        // Ne jamais renvoyer le mot de passe SMTP dans le HTML
        $settings['has_mail_password'] = !empty($settings['mail_password']);
        unset($settings['mail_password']);

        return $settings;
    }

    protected function saveSettings(Request $request, ?int $organizationId): void
    {
        $request->validate([
            'mail_port'         => 'nullable|integer|min:1|max:65535',
            'mail_encryption'   => 'nullable|in:tls,ssl',
            'mail_from_address' => 'nullable|email',
            'lock_timeout_min'  => 'nullable|integer|min:1|max:1440',
        ]);

        foreach (static::TEXT_KEYS as $key) {
            if ($request->has($key)) {
                $this->put($organizationId, $key, $request->input($key));
            }
        }

        foreach (static::CHECKBOX_KEYS as $key) {
            $this->put($organizationId, $key, $request->boolean($key) ? '1' : '0');
        }

        // Mot de passe SMTP : champ vide = conserver l'actuel ; stocké chiffré
        if ($request->filled('mail_password')) {
            $this->put($organizationId, 'mail_password', Crypt::encryptString($request->input('mail_password')));
        }
    }

    protected function put(?int $organizationId, string $key, ?string $value): void
    {
        DB::table('settings')->updateOrInsert(
            ['organization_id' => $organizationId, 'key' => $key],
            ['value' => $value, 'updated_at' => now()]
        );
    }
}
