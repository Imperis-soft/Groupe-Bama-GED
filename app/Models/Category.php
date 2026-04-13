<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    // Les attributs pouvant être assignés en masse
    protected $fillable = [
        'name',
        'slug',
        'description',
    ];


    // Relation avec les documents
    public function documents()
    {
        return $this->hasMany(Document::class);
    }
}
