<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemoRequest extends Model
{
    public const FORMULAS = [
        'saas'          => 'SaaS (cloud)',
        'single_entity' => 'SingleEntity (installation dédiée)',
        'unsure'        => 'À conseiller',
    ];

    public const STATUSES = [
        'new'       => 'Nouvelle',
        'contacted' => 'Contactée',
        'won'       => 'Gagnée',
        'lost'      => 'Perdue',
    ];

    public const SIZES = ['1-10', '11-50', '51-200', '201-500', '500+'];

    protected $fillable = [
        'formula', 'company', 'contact_name', 'email', 'phone',
        'company_size', 'sector', 'message', 'status', 'notes', 'ip_address',
    ];

    public function formulaLabel(): string
    {
        return self::FORMULAS[$this->formula] ?? $this->formula;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
