<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

// Export complet d'une entreprise (ZIP), disponible quelques jours puis supprimé
class OrganizationExport extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'requested_by', 'status', 'path', 'size', 'checksum', 'documents_count', 'error', 'expires_at'];

    protected $casts = ['expires_at' => 'datetime'];

    public function requester() { return $this->belongsTo(User::class, 'requested_by'); }

    public function isReady(): bool
    {
        return $this->status === 'done' && $this->path && (!$this->expires_at || $this->expires_at->isFuture());
    }
}
