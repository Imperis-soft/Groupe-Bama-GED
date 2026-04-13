<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentVersion extends Model
{
    // Les attributs pouvant être assignés en masse
    protected $fillable = [
        'document_id',
        'version_number',
        'file_path',
        'checksum',
        'change_description',
        'created_by',
        'metadata',
    ];

    // Les attributs à caster
    protected $casts = [
        'metadata' => 'array',
    ];

    // Relations avec le document
    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    // Relation avec le créateur de la version
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
