<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'logged_in_at',
        'logged_out_at',
    ];

    protected function casts(): array
    {
        return [
            'logged_in_at'  => 'datetime',
            'logged_out_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getDurationAttribute(): ?string
    {
        if (!$this->logged_out_at) {
            return null;
        }
        $mins = $this->logged_in_at->diffInMinutes($this->logged_out_at);
        $h    = intdiv($mins, 60);
        $m    = $mins % 60;
        return $h > 0 ? "{$h}h {$m}m" : "{$m}m";
    }
}
