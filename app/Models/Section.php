<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $fillable = ['name', 'slug', 'sort_order'];

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'section_user');
    }
}
