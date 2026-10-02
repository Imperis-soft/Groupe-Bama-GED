<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'price', 'currency', 'billing_months',
        'max_users', 'max_storage_mb', 'features', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'features'  => 'array',
        'is_active' => 'boolean',
    ];

    public function subscriptions() { return $this->hasMany(Subscription::class); }

    public function formattedPrice(): string
    {
        return number_format($this->price, 0, ',', ' ') . ' ' . $this->currency;
    }

    public function periodLabel(): string
    {
        return match ((int) $this->billing_months) {
            1       => 'mois',
            3       => 'trimestre',
            6       => 'semestre',
            12      => 'an',
            default => $this->billing_months . ' mois',
        };
    }
}
