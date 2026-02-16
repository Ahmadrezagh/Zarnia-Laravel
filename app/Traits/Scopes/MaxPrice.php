<?php
namespace App\Traits\Scopes;
use Illuminate\Database\Eloquent\Builder;

trait MaxPrice
{
    /**
     * Filter products that have at least one available etiket with effective price <= maxPrice.
     * Effective price = etikets.price * 100 * (1 - products.discount_percentage/100) (same scale as price column).
     */
    public function scopeMaxPrice(Builder $query, $maxPrice = null)
    {
        if (is_null($maxPrice)) {
            return $query;
        }
        $maxPrice = $maxPrice * 10;
        $effectivePrice = 'etikets.price * 100 * (1 - COALESCE(products.discount_percentage, 0) / 100)';
        return $query->whereHas('etikets', function ($etiketQuery) use ($maxPrice, $effectivePrice) {
            $etiketQuery->where('is_mojood', 1)
                ->whereRaw("({$effectivePrice}) <= ?", [$maxPrice]);
        });
    }
}