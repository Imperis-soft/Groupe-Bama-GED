<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\SiteConfigController;
use Illuminate\Http\Request;

/**
 * Paramètres de la plateforme (organization_id NULL) :
 * SMTP des emails système et valeurs par défaut des entreprises.
 */
class SettingsController extends SiteConfigController
{
    public function index()
    {
        return view('super.settings', ['settings' => $this->loadSettings(null), 'organization' => null]);
    }

    public function update(Request $request)
    {
        $this->saveSettings($request, null);

        return redirect()->route('super.settings.index')->with('success', 'Paramètres enregistrés.');
    }
}
