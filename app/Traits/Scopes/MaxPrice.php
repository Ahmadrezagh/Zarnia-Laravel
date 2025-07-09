<?php
namespace App\Traits\Scopes;
use Illuminate\Database\Eloquent\Builder;

trait MaxPrice{
    public function scopeMaxPrice(Builder $query, $maxPrice = null)
    {
        if (is_null($maxPrice)) {
            return $query;
        }
        $maxPrice = $maxPrice * 10;
        return $query->where(function ($q) use ($maxPrice) {
            $q->whereNotNull('discounted_price')
                ->where('discounted_price', '<=', $maxPrice)
                ->orWhereNull('discounted_price')
                ->where('price', '<=', $maxPrice);
        });
    }
}