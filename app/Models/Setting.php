<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        // Cache only what is stored — never the caller's default, or the first
        // default read for a missing key would stick for every later caller.
        // Wrapped in an array so a missing key (cached as []) still skips the
        // database. The v2 key keeps old-format cache entries from being read.
        $cached = Cache::rememberForever(self::cacheKey($key), function () use ($key) {
            $row = static::where('key', $key)->first(['value']);
            return $row ? ['value' => $row->value] : [];
        });

        return $cached['value'] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget(self::cacheKey($key));
    }

    public static function setMany(array $data): void
    {
        foreach ($data as $key => $value) {
            static::set($key, $value);
        }
    }

    private static function cacheKey(string $key): string
    {
        return "setting_v2_{$key}";
    }
}
