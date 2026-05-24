<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSocialLink extends Model
{
    protected $fillable = ['product_id', 'platform', 'url'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getIconAttribute(): string
    {
        return match($this->platform) {
            'facebook'  => 'fab fa-facebook',
            'tiktok'    => 'fab fa-tiktok',
            'instagram' => 'fab fa-instagram',
            'youtube'   => 'fab fa-youtube',
            default     => 'fas fa-link',
        };
    }
}
