<?php

namespace App\Models;

use App\Models\Role;
use App\Models\LoginHistory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // <--- Importe ça

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable; // <--- Utilise HasApiTokens
    // Les attributs pouvant être assignés en masse
    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'address',
        'password',
    ];

    // Les attributs à cacher pour les tableaux
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Les attributs à caster
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relation avec les rôles (many to many)
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    // Favoris
    public function favorites()
    {
        return $this->belongsToMany(Document::class, 'document_favorites');
    }

    // Historique de connexions
    public function loginHistories()
    {
        return $this->hasMany(LoginHistory::class)->latest();
    }

    // Vérifie si l'utilisateur a un rôle spécifique
    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }
}