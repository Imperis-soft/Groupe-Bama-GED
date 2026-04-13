<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalStep extends Model
{
    protected $fillable = [
        'document_id', 'approver_id', 'step_order', 'status',
        'comment', 'decided_at', 'due_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
        'due_at'     => 'datetime',
    ];

    public function document() { return $this->belongsTo(Document::class); }
    public function approver() { return $this->belongsTo(User::class, 'approver_id'); }

    public function isPending():  bool { return $this->status === 'pending'; }
    public function isApproved(): bool { return $this->status === 'approved'; }
    public function isRejected(): bool { return $this->status === 'rejected'; }
}
