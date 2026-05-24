<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use Sluggable;

    protected $fillable = ['name', 'slug', 'logo', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function sluggable(): array
    {
        return ['slug' => ['source' => 'name']];
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function getLogoUrlAttribute(): string
    {
        return $this->logo
            ? asset('storage/' . $this->logo)
            : asset('images/brand-placeholder.png');
    }
}
