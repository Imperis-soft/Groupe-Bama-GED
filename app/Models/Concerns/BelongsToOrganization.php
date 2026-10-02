<?php

namespace App\Models\Concerns;

use App\Models\Organization;
use App\Support\Tenant;
use Illuminate\Database\Eloquent\Builder;

/**
 * Cloisonne un modèle par entreprise :
 * - filtre automatiquement les requêtes sur l'entreprise courante ;
 * - renseigne organization_id à la création.
 */
trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope('organization', function (Builder $query) {
            if (Tenant::check()) {
                $query->where($query->getModel()->getTable() . '.organization_id', Tenant::id());
            }
        });

        static::creating(function ($model) {
            if (empty($model->organization_id) && Tenant::check()) {
                $model->organization_id = Tenant::id();
            }
        });
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
