<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class ApprovalTemplate extends Model
{
    use BelongsToOrganization;

    public const STEP_TYPES = [
        'user'               => 'Une personne précise',
        'department_manager' => 'Le responsable d\'un service',
        'creator_manager'    => 'Le responsable du service de l\'auteur',
    ];

    public const MAX_STEPS = 10;

    protected $fillable = ['organization_id', 'name', 'description', 'category_id', 'steps', 'created_by'];

    protected $casts = ['steps' => 'array'];

    public function category() { return $this->belongsTo(Category::class); }
    public function creator()  { return $this->belongsTo(User::class, 'created_by'); }
}
