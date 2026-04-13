<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DocumentLock extends Model
{
    protected $fillable = [
        'document_id', 'locked_by', 'lock_token', 'locked_at', 'expires_at',
    ];

    protected $casts = [
        'locked_at'  => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function document()  { return $this->belongsTo(Document::class); }
    public function lockedBy()  { return $this->belongsTo(User::class, 'locked_by'); }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public static function generateToken(): string
    {
        return Str::uuid()->toString();
    }
}
