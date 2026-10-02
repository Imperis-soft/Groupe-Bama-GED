<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformActivityLog extends Model
{
    protected $fillable = ['user_id', 'organization_id', 'action', 'description', 'ip_address'];

    public function user()         { return $this->belongsTo(User::class); }
    public function organization() { return $this->belongsTo(Organization::class); }

    public static function record(string $action, ?string $description = null, ?Organization $organization = null): self
    {
        return static::create([
            'user_id'         => auth()->id(),
            'organization_id' => $organization?->id,
            'action'          => $action,
            'description'     => $description,
            'ip_address'      => app()->runningInConsole() ? null : request()->ip(),
        ]);
    }
}
