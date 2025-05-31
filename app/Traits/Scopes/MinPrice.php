<?php
namespace App\Traits\Scopes;
use Illuminate\Database\Eloquent\Builder;

trait MinPrice{
    public function scopeMinPrice(Builder $query, $minPrice = null)
    {
        if (is_null($minPrice)) {
            return $query;
        }
        $minPrice = $minPrice * 10;
        return $query->where(function ($q) use ($minPrice) {
            $q->whereNotNull('discounted_price')
                ->where('discounted_price', '>=', $minPrice)
                ->orWhereNull('discounted_price')
                ->where('price', '>=', $minPrice);
        });
    }
}