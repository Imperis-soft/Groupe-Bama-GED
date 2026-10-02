<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\SiteConfigController;
use App\Models\Organization;
use App\Models\PlatformActivityLog;
use Illuminate\Http\Request;

/**
 * Configuration propre à une entreprise : SMTP dédié (sinon celui de la plateforme)
 * et réglages qui remplacent les valeurs par défaut de la plateforme.
 */
class OrganizationSettingsController extends SiteConfigController
{
    public function edit(Organization $organization)
    {
        return view('super.settings', [
            'settings'         => $this->loadSettings($organization->id),
            'organization'     => $organization,
            'platformSettings' => $this->loadSettings(null),
        ]);
    }

    public function update(Request $request, Organization $organization)
    {
        $this->saveSettings($request, $organization->id);

        PlatformActivityLog::record('organization_settings_updated', "Configuration de « {$organization->name} » modifiée", $organization);

        return redirect()->route('super.organizations.settings', $organization)->with('success', 'Configuration enregistrée.');
    }
}
