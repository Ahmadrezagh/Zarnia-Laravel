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
            // Case 1: Product has discounted_price
            $q->where(function ($discountedQuery) use ($maxPrice) {
                $discountedQuery->whereNotNull('discounted_price')
                    ->where('discounted_price', '>', 0)
                    ->where('discounted_price', '<=', $maxPrice);
            })
            // Case 2: Product doesn't have discounted_price, use regular price
            ->orWhere(function ($regularQuery) use ($maxPrice) {
                $regularQuery->where(function ($nullOrZero) {
                    $nullOrZero->whereNull('discounted_price')
                        ->orWhere('discounted_price', '=', 0);
                })
                ->where('price', '<=', $maxPrice);
            });
        });
    }
}