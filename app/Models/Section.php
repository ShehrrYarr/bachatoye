<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $fillable = ['name', 'slug', 'sort_order', 'exchange_enabled'];

    protected function casts(): array
    {
        return ['exchange_enabled' => 'boolean'];
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'section_user');
    }
}
