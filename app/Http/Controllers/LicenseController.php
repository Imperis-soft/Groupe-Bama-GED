<?php

namespace App\Http\Controllers;

use App\Models\License;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    /**
     * Page affichée quand la licence est expirée.
     * Contient les 3 onglets : contact Imperis, demande de renouvellement, activation clé.
     */
    public function expired()
    {
        $license = License::current();

        return view('license.expired', [
            'license' => $license,
        ]);
    }

    /**
     * Formulaire d'activation/renouvellement de licence (redirige vers la page expirée avec onglet activate).
     */
    public function showActivate()
    {
        // Tout est intégré dans la page expired — on redirige vers elle
        return redirect()->route('license.expired');
    }

    /**
     * Activer ou renouveler la licence.
     */
    public function activate(Request $request)
    {
        $request->validate([
            'license_key' => ['required', 'string', 'min:16'],
        ]);

        $key = $request->input('license_key');

        // Vérifier la clé de licence (format: IMPERIS-XXXX-XXXX-XXXX-EXPYYYYMMDD)
        if (! $this->validateLicenseKey($key)) {
            return back()->withErrors(['license_key' => 'Clé de licence invalide. Contactez Imperis SARL.']);
        }

        // Extraire la date d'expiration de la clé
        $expiresAt = $this->extractExpirationDate($key);

        if (! $expiresAt) {
            return back()->withErrors(['license_key' => 'Format de clé invalide. Contactez Imperis SARL.']);
        }

        // Désactiver les anciennes licences
        License::where('is_active', true)->update(['is_active' => false]);

        // Créer la nouvelle licence
        License::create([
            'license_key' => $key,
            'licensed_to' => 'Groupe Bama',
            'issued_by'   => 'Imperis SARL',
            'issued_at'   => now(),
            'expires_at'  => $expiresAt,
            'is_active'   => true,
            'notes'       => 'Licence activée le ' . now()->format('d/m/Y à H:i'),
        ]);

        return redirect()->route('login')->with('success', 'Licence activée avec succès. Valide jusqu\'au ' . $expiresAt->format('d/m/Y') . '.');
    }

    /**
     * Valide le format de la clé de licence.
     * Format attendu : IMPERIS-XXXX-XXXX-XXXX-EXPYYYYMMDD
     */
    private function validateLicenseKey(string $key): bool
    {
        return preg_match('/^IMPERIS-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-EXP\d{8}$/', $key) === 1;
    }

    /**
     * Extrait la date d'expiration depuis la clé de licence.
     */
    private function extractExpirationDate(string $key): ?\Carbon\Carbon
    {
        if (preg_match('/EXP(\d{8})$/', $key, $matches)) {
            try {
                return \Carbon\Carbon::createFromFormat('Ymd', $matches[1]);
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }
}
