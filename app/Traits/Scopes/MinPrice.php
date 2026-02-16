<?php
namespace App\Traits\Scopes;
use Illuminate\Database\Eloquent\Builder;

trait MinPrice
{
    /**
     * Filter products that have at least one available etiket with effective price >= minPrice.
     * minPrice is in display units; effective price = etikets.price * 10 * (1 - products.discount_percentage/100).
     */
    public function scopeMinPrice(Builder $query, $minPrice = null)
    {
        if (is_null($minPrice)) {
            return $query;
        }
        $effectivePrice = 'etikets.price * 10 * (1 - COALESCE(products.discount_percentage, 0) / 100)';
        return $query->whereHas('etikets', function ($etiketQuery) use ($minPrice, $effectivePrice) {
            $etiketQuery->where('is_mojood', 1)
                ->whereRaw("({$effectivePrice}) >= ?", [$minPrice]);
        });
    }
}