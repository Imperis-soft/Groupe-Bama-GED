<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

/**
 * Procès-verbal d'élimination : trace permanente d'une destruction de documents validée par un administrateur.
 * Il n'est jamais modifié ni supprimé (sauf suppression de l'entreprise).
 */
class EliminationRecord extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'number', 'approved_by', 'approved_by_name', 'reason',
        'documents', 'documents_count', 'pdf_path', 'pdf_checksum',
    ];

    protected $casts = ['documents' => 'array'];

    protected static function booted(): void
    {
        // Seul l'enregistrement du PDF généré juste après la création est admis
        static::updating(function (EliminationRecord $record) {
            if (array_diff(array_keys($record->getDirty()), ['pdf_path', 'pdf_checksum', 'updated_at'])) {
                throw new \LogicException('Un procès-verbal d\'élimination ne peut pas être modifié.');
            }
        });
        static::deleting(function () {
            throw new \LogicException('Un procès-verbal d\'élimination ne peut pas être supprimé.');
        });
    }

    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
}
