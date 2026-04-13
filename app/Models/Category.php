<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    // Les attributs pouvant être assignés en masse
    protected $fillable = ['name', 'slug', 'description', 'parent_id'];

    public function documents()  { return $this->hasMany(Document::class); }
    public function parent()     { return $this->belongsTo(Category::class, 'parent_id'); }
    public function children()   { return $this->hasMany(Category::class, 'parent_id'); }
    public function allChildren(): \Illuminate\Support\Collection
    {
        return $this->children->flatMap(fn($c) => collect([$c])->merge($c->allChildren()));
    }
}
