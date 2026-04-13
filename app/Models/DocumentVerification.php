<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentVerification extends Model
{

    // Les attributs pouvant être assignés en masse
    protected $fillable = [
        'document_id',
        'verification_code',
        'verified_at',
        'device_info',
        'ip_address',
        'user_agent',
    ];

    // Les attributs à caster
    protected $casts = [
        'verified_at' => 'datetime',
        'device_info' => 'array',
    ];

    // Relation avec le document
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
