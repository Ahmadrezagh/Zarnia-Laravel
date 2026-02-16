<?php
namespace App\Traits\Scopes;
use Illuminate\Database\Eloquent\Builder;

trait MinPrice
{
    /**
     * Filter products that have at least one available etiket with effective price >= minPrice.
     * Effective price = etikets.price * 100 * (1 - products.discount_percentage/100) (same scale as price column).
     */
    public function scopeMinPrice(Builder $query, $minPrice = null)
    {
        if (is_null($minPrice)) {
            return $query;
        }
        $minPrice = $minPrice * 10;
        $effectivePrice = 'etikets.price * 100 * (1 - COALESCE(products.discount_percentage, 0) / 100)';
        return $query->whereHas('etikets', function ($etiketQuery) use ($minPrice, $effectivePrice) {
            $etiketQuery->where('is_mojood', 1)
                ->whereRaw("({$effectivePrice}) >= ?", [$minPrice]);
        });
    }
}