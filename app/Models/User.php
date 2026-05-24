<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'password_plain',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isSalesman(): bool
    {
        return $this->hasRole('salesman');
    }

    public function loginLogs()
    {
        return $this->hasMany(LoginLog::class);
    }

    public function latestLogin()
    {
        return $this->hasOne(LoginLog::class)->latestOfMany('logged_in_at');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'served_by');
    }

    public function posSessions()
    {
        return $this->hasMany(PosSession::class);
    }

    public function sections()
    {
        return $this->belongsToMany(Section::class, 'section_user');
    }

    /**
     * Returns category IDs this user is allowed to sell.
     * Returns null if the user can see everything (admin or no sections assigned).
     *
     * @return int[]|null
     */
    public function allowedCategoryIds(): ?array
    {
        if ($this->isAdmin()) {
            return null; // no restriction
        }

        $this->loadMissing('sections');
        if ($this->sections->isEmpty()) {
            return null; // no sections assigned — unrestricted
        }

        return Category::whereIn('section_id', $this->sections->pluck('id'))->pluck('id')->toArray();
    }
}
