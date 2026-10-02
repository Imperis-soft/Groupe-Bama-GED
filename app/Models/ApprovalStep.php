<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalStep extends Model
{
    protected $fillable = [
        'document_id', 'approver_id', 'delegated_from_id', 'step_order', 'status',
        'comment', 'decided_at', 'due_at', 'due_days', 'activated_at', 'reminders_sent', 'last_reminded_at',
        'document_version', 'document_checksum', 'forced_by_id', 'force_reason',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
        'due_at'     => 'datetime',
        'activated_at'     => 'datetime',
        'last_reminded_at' => 'datetime',
    ];

    public function document() { return $this->belongsTo(Document::class); }
    public function approver() { return $this->belongsTo(User::class, 'approver_id'); }
    // Approbateur initial quand l'étape a été confiée à son suppléant
    public function delegatedFrom() { return $this->belongsTo(User::class, 'delegated_from_id'); }
    // Administrateur qui a décidé à la place de l'approbateur
    public function forcedBy() { return $this->belongsTo(User::class, 'forced_by_id'); }

    public function isOverdue(): bool { return $this->isPending() && $this->due_at?->isPast(); }

    public function isPending():  bool { return $this->status === 'pending'; }
    public function isApproved(): bool { return $this->status === 'approved'; }
    public function isRejected(): bool { return $this->status === 'rejected'; }
}
