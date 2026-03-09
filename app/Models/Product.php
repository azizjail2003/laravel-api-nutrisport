<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = ['name', 'stock'];

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function priceForSite(int $siteId): ?ProductPrice
    {
        return $this->prices()->where('site_id', $siteId)->first();
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function isInStock(): bool
    {
        return $this->stock > 0;
    }
}
