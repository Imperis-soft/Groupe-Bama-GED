<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    public const PAYMENT_METHODS = [
        'virement'     => 'Virement bancaire',
        'orange_money' => 'Orange Money',
        'wave'         => 'Wave',
        'moov_money'   => 'Moov Money',
        'especes'      => 'Espèces',
        'cheque'       => 'Chèque',
        'gratuit'      => 'Offert / essai',
        'licence'      => 'Reprise de licence',
        'autre'        => 'Autre',
    ];

    protected $fillable = [
        'organization_id', 'plan_id', 'status', 'starts_at', 'ends_at', 'amount', 'currency',
        'payment_method', 'payment_reference', 'paid_at', 'notes', 'created_by', 'cancelled_at',
    ];

    protected $casts = [
        'starts_at'    => 'date',
        'ends_at'      => 'date',
        'paid_at'      => 'date',
        'cancelled_at' => 'datetime',
    ];

    public function organization() { return $this->belongsTo(Organization::class); }
    public function plan()         { return $this->belongsTo(Plan::class); }
    public function creator()      { return $this->belongsTo(User::class, 'created_by'); }

    public function isCurrent(): bool
    {
        return $this->status !== 'cancelled'
            && $this->starts_at->lte(today())
            && $this->ends_at->gte(today());
    }

    // Statut affiché : actif, essai, à venir, expiré, annulé
    public function displayStatus(): string
    {
        return match (true) {
            $this->status === 'cancelled'  => 'cancelled',
            $this->ends_at->lt(today())    => 'expired',
            $this->starts_at->gt(today())  => 'upcoming',
            default                        => $this->status, // active ou trial
        };
    }

    public function displayStatusLabel(): string
    {
        return [
            'active'    => 'Actif',
            'trial'     => 'Essai',
            'upcoming'  => 'À venir',
            'expired'   => 'Expiré',
            'cancelled' => 'Annulé',
        ][$this->displayStatus()];
    }

    public function daysRemaining(): int
    {
        return (int) today()->diffInDays($this->ends_at, false);
    }

    public function paymentMethodLabel(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? ($this->payment_method ?: '—');
    }

    public function formattedAmount(): string
    {
        return number_format($this->amount, 0, ',', ' ') . ' ' . $this->currency;
    }
}
