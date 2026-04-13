<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Role extends Model
{
    // Les attributs pouvant être assignés en masse
    protected $fillable = ['name', 'display_name', 'description'];

    // Relation avec les utilisateurs
    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
