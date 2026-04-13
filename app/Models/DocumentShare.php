<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DocumentShare extends Model
{
    protected $fillable = [
        'document_id', 'shared_by', 'shared_with', 'share_token',
        'access_level', 'message', 'expires_at', 'accessed_at', 'is_active',
    ];

    protected $casts = [
        'expires_at'  => 'datetime',
        'accessed_at' => 'datetime',
        'is_active'   => 'boolean',
    ];

    public function document()   { return $this->belongsTo(Document::class); }
    public function sharedBy()   { return $this->belongsTo(User::class, 'shared_by'); }
    public function sharedWith() { return $this->belongsTo(User::class, 'shared_with'); }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    public static function generateToken(): string
    {
        return Str::random(48);
    }
}
