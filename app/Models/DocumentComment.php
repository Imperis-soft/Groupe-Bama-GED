<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentComment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'document_id', 'user_id', 'parent_id', 'content',
        'type', 'is_internal', 'edited_at',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
        'edited_at'   => 'datetime',
    ];

    public function document() { return $this->belongsTo(Document::class); }
    public function user()     { return $this->belongsTo(User::class); }
    public function parent()   { return $this->belongsTo(DocumentComment::class, 'parent_id'); }
    public function replies()  { return $this->hasMany(DocumentComment::class, 'parent_id')->with('user'); }
}
