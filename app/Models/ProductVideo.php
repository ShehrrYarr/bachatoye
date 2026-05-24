<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVideo extends Model
{
    protected $fillable = ['product_id', 'type', 'url', 'path', 'thumbnail', 'title', 'sort_order'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getEmbedUrlAttribute(): ?string
    {
        if ($this->type === 'upload') {
            return $this->path ? asset('storage/' . $this->path) : null;
        }

        $url = $this->url ?? '';

        // YouTube: convert watch URL to embed URL
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\s]+)/', $url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }

        // Already an embed URL (youtube.com/embed/...)
        if (str_contains($url, 'youtube.com/embed/')) {
            return $url;
        }

        // TikTok
        if (str_contains($url, 'tiktok.com')) {
            return $url;
        }

        return $url ?: null;
    }
}
