<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SavedFilter extends Model
{
    use BelongsToOrganization;

    public const MAX_PER_USER = 20;

    protected $fillable = ['organization_id', 'user_id', 'name', 'params', 'is_shared'];

    protected $casts = [
        'params'    => 'array',
        'is_shared' => 'boolean',
    ];

    public function user() { return $this->belongsTo(User::class); }

    // Vues de l'utilisateur et vues partagées de son entreprise
    public function scopeAvailableTo(Builder $query, User $user): Builder
    {
        return $query->where(fn ($q) => $q->where('user_id', $user->id)->orWhere('is_shared', true));
    }

    public function canManage(User $user): bool
    {
        return $this->user_id === $user->id || ($this->is_shared && $user->hasRole('admin'));
    }

    public function url(): string
    {
        return route('documents.index', array_merge($this->params, ['view' => $this->id]));
    }
}
