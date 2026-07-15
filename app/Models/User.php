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
        'shop_id',
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
            'shop_id'           => 'integer',
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

    public function isSubshop(): bool
    {
        return $this->hasRole('subshop');
    }

    /**
     * The shop this user operates. NULL = main shop (admin/salesman).
     */
    public function shopId(): ?int
    {
        return $this->isSubshop() ? $this->shop_id : null;
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Route-name prefix of this user's panel: admin / shop / salesman.
     */
    public function panelPrefix(): string
    {
        if ($this->hasRole('admin')) {
            return 'admin';
        }

        return $this->isSubshop() ? 'shop' : 'salesman';
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
