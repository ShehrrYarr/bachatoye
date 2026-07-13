<?php

namespace App\Models\Concerns;

use App\Models\Shop;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToShop
{
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Scope to one shop's rows. NULL = main shop.
     */
    public function scopeForShop(Builder $query, ?int $shopId): Builder
    {
        return $shopId
            ? $query->where($this->qualifyColumn('shop_id'), $shopId)
            : $query->whereNull($this->qualifyColumn('shop_id'));
    }

    /**
     * Scope by an admin-side filter value: '' / null = all shops,
     * 'main' = main shop only, numeric = that sub shop.
     */
    public function scopeForShopFilter(Builder $query, $filter): Builder
    {
        if ($filter === null || $filter === '') {
            return $query;
        }

        return $filter === 'main'
            ? $query->whereNull($this->qualifyColumn('shop_id'))
            : $query->where($this->qualifyColumn('shop_id'), (int) $filter);
    }
}
