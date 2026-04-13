<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentPermission extends Model
{
    protected $fillable = [
        'document_id', 'user_id', 'can_view', 'can_edit', 'can_delete',
        'can_approve', 'can_archive', 'can_share', 'can_comment',
        'granted_by', 'expires_at',
    ];

    protected $casts = [
        'can_view' => 'boolean', 'can_edit' => 'boolean', 'can_delete' => 'boolean',
        'can_approve' => 'boolean', 'can_archive' => 'boolean',
        'can_share' => 'boolean', 'can_comment' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function document() { return $this->belongsTo(Document::class); }
    public function user()     { return $this->belongsTo(User::class); }
    public function grantedBy(){ return $this->belongsTo(User::class, 'granted_by'); }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
