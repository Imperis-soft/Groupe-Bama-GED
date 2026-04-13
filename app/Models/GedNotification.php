<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GedNotification extends Model
{
    protected $table = 'ged_notifications';

    protected $fillable = [
        'user_id', 'type', 'title', 'message', 'link',
        'notifiable_type', 'notifiable_id',
        'is_read', 'email_sent', 'read_at',
    ];

    protected $casts = [
        'is_read'    => 'boolean',
        'email_sent' => 'boolean',
        'read_at'    => 'datetime',
    ];

    public function user()       { return $this->belongsTo(User::class); }
    public function notifiable() { return $this->morphTo(); }

    public function markAsRead(): void
    {
        $this->update(['is_read' => true, 'read_at' => now()]);
    }
}
