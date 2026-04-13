<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentAuditLog extends Model
{

    // Les attributs pouvant être assignés en masse
    protected $fillable = [
        'document_id',
        'user_id',
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
