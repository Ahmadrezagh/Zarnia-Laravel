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
            // Case 1: Product has discounted_price
            $q->where(function ($discountedQuery) use ($minPrice) {
                $discountedQuery->whereNotNull('discounted_price')
                    ->where('discounted_price', '>', 0)
                    ->where('discounted_price', '>=', $minPrice);
            })
            // Case 2: Product doesn't have discounted_price, use regular price
            ->orWhere(function ($regularQuery) use ($minPrice) {
                $regularQuery->where(function ($nullOrZero) {
                    $nullOrZero->whereNull('discounted_price')
                        ->orWhere('discounted_price', '=', 0);
                })
                ->where('price', '>=', $minPrice);
            });
        });
    }
}