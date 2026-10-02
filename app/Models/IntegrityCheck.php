<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

// Résultat d'un contrôle d'intégrité d'une entreprise (fichiers, procès-verbaux, journal d'audit)
class IntegrityCheck extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'files_checked', 'files_failed', 'baselines', 'audit_chain_ok', 'problems'];

    protected $casts = ['problems' => 'array', 'audit_chain_ok' => 'boolean'];

    public function passed(): bool
    {
        return $this->files_failed === 0 && $this->audit_chain_ok;
    }
}
