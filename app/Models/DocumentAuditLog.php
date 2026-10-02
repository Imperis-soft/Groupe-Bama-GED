<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToOrganization;

class DocumentAuditLog extends Model
{
    use BelongsToOrganization;

    // Les attributs pouvant être assignés en masse
    protected $fillable = [
        'organization_id',
        'document_id',
        'document_reference',
        'document_title',
        'user_id',
        'user_name',
        'action',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    // Les attributs à caster
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];


    
    protected static function booted(): void
    {
        // Référence et titre recopiés : la ligne reste lisible après la purge du document
        static::creating(function (DocumentAuditLog $log) {
            // Auteur recopié : la preuve ne dépend pas d'un compte qui peut être supprimé
            if ($log->user_id && !$log->user_name) {
                $log->user_name = User::withoutGlobalScopes()->whereKey($log->user_id)->value('full_name');
            }
            if ($log->document_id && (!$log->document_reference || !$log->document_title)) {
                $document = Document::withoutGlobalScopes()->withTrashed()->find($log->document_id);
                $log->document_reference ??= $document?->reference;
                $log->document_title ??= $document?->title;
            }
        });

        // Scellement dans la chaîne de l'entreprise (empreinte liée à la ligne précédente)
        static::created(fn (DocumentAuditLog $log) => \App\Support\AuditChain::seal($log->organization_id));

        // Journal en ajout seul : une ligne d'audit ne se modifie pas
        static::updating(function (DocumentAuditLog $log) {
            // Seul le détachement du document (purge, géré par la base) est admis
            if (array_diff(array_keys($log->getDirty()), ['document_id'])) {
                throw new \LogicException('Le journal d\'audit ne peut pas être modifié.');
            }
        });
    }

    // Relation avec le document
    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    // Relation avec l'utilisateur
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
