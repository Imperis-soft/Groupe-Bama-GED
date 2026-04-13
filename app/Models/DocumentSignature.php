<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentSignature extends Model
{
    protected $fillable = [
        'document_id', 'user_id', 'signature_data', 'signature_hash',
        'ip_address', 'user_agent', 'page_number', 'position',
        'status', 'reason', 'signed_at',
    ];

    protected $casts = [
        'position'  => 'array',
        'signed_at' => 'datetime',
    ];

    public function document() { return $this->belongsTo(Document::class); }
    public function user()     { return $this->belongsTo(User::class); }
}
