<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SignatureRequest extends Model
{
    public const STATUSES = [
        'pending'   => 'En attente',
        'signed'    => 'Signé',
        'declined'  => 'Refusé',
        'cancelled' => 'Annulé',
    ];

    protected $fillable = [
        'document_id', 'requested_by', 'user_id', 'message', 'due_at', 'reminders_sent', 'last_reminded_at',
        'status', 'signature_id', 'decline_reason', 'decided_at',
    ];

    protected $casts = [
        'due_at'     => 'datetime',
        'decided_at' => 'datetime',
        'last_reminded_at' => 'datetime',
    ];

    public function document()    { return $this->belongsTo(Document::class); }
    public function signer()      { return $this->belongsTo(User::class, 'user_id'); }
    public function requester()   { return $this->belongsTo(User::class, 'requested_by'); }
    public function signature()   { return $this->belongsTo(DocumentSignature::class); }

    public function isPending(): bool { return $this->status === 'pending'; }
    public function isOverdue(): bool { return $this->isPending() && $this->due_at?->isPast(); }
}
