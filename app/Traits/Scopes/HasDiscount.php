<?php
namespace App\Traits\Scopes;
use Illuminate\Database\Eloquent\Builder;

trait HasDiscount
{
    /**
     * Filter products that have a discount (discount_percentage > 0).
     * products.discounted_price was removed; discount is now via discount_percentage only.
     */
    public function scopeHasDiscount(Builder $query, $hasDiscount = null)
    {
        if ($hasDiscount) {
            return $query
                ->whereNotNull('discount_percentage')
                ->where('discount_percentage', '>', 0);
        }
        return $query;
    }
}