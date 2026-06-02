<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class License extends Model
{
    protected $fillable = [
        'license_key',
        'licensed_to',
        'issued_by',
        'issued_at',
        'expires_at',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'issued_at'  => 'date',
        'expires_at' => 'date',
        'is_active'  => 'boolean',
    ];

    /**
     * Vérifie si la licence est actuellement valide.
     */
    public function isValid(): bool
    {
        return $this->is_active && $this->expires_at->isFuture();
    }

    /**
     * Vérifie si la licence est expirée.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Nombre de jours restants avant expiration.
     */
    public function daysRemaining(): int
    {
        return (int) now()->diffInDays($this->expires_at, false);
    }

    /**
     * Récupère la licence active actuelle.
     */
    public static function current(): ?self
    {
        return static::where('is_active', true)
            ->orderByDesc('expires_at')
            ->first();
    }

    /**
     * Vérifie globalement si le système a une licence valide.
     */
    public static function isSystemLicensed(): bool
    {
        $license = static::current();

        return $license !== null && $license->isValid();
    }
}
