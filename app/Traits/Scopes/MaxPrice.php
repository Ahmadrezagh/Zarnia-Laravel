<?php
namespace App\Traits\Scopes;
use Illuminate\Database\Eloquent\Builder;

trait MaxPrice
{
    /**
     * Filter products that have at least one available etiket with effective price <= maxPrice.
     * maxPrice is in display units; effective price = etikets.price * 10 * (1 - products.discount_percentage/100).
     */
    public function scopeMaxPrice(Builder $query, $maxPrice = null)
    {
        if (is_null($maxPrice)) {
            return $query;
        }
        $effectivePrice = 'etikets.price * 10 * (1 - COALESCE(products.discount_percentage, 0) / 100)';
        return $query->whereHas('etikets', function ($etiketQuery) use ($maxPrice, $effectivePrice) {
            $etiketQuery->where('is_mojood', 1)
                ->whereRaw("({$effectivePrice}) <= ?", [$maxPrice]);
        });
    }
}