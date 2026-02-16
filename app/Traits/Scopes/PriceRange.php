<?php
namespace App\Traits\Scopes;
use Illuminate\Database\Eloquent\Builder;

trait PriceRange
{
    /**
     * Effective price expression in display×10 scale (to match minPrice*10 / maxPrice*10).
     * etikets.price is stored as display/10; effective display×10 = price*100*(1-d/100).
     * products = outer query table (correlated).
     */
    protected function etiketEffectivePriceSql(): string
    {
        return 'etikets.price * 100 * (1 - COALESCE(products.discount_percentage, 0) / 100)';
    }

    /**
     * Scope to filter parent products by their own price or their children's prices.
     * Price is determined by available etikets and product discount_percentage (products table no longer has price/discounted_price).
     * Only includes products that are available (single_count >= 1).
     *
     * @param Builder $query
     * @param float|null $fromPrice Minimum price (can be null)
     * @param float|null $toPrice Maximum price (can be null)
     * @return Builder
     */
    public function scopePriceRange(Builder $query, $fromPrice = null, $toPrice = null)
    {
        if (is_null($fromPrice) && is_null($toPrice)) {
            return $query;
        }

        // Multiply by 10 so filter uses same scale as effective price (display×10)
        $fromPrice = $fromPrice !== null ? $fromPrice * 10 : null;
        $toPrice = $toPrice !== null ? $toPrice * 10 : null;

        $query->where(function ($q) use ($fromPrice, $toPrice) {
            $q->where(function ($ownPriceQuery) use ($fromPrice, $toPrice) {
                $this->applyPriceFilter($ownPriceQuery, $fromPrice, $toPrice);
            })
            ->orWhereHas('children', function ($childrenQuery) use ($fromPrice, $toPrice) {
                $this->applyPriceFilter($childrenQuery, $fromPrice, $toPrice);
            });
        });

        return $query;
    }

    /**
     * Apply price filter via etikets: at least one available etiket's effective price must be in range.
     * Effective price = etikets.price * (1 - products.discount_percentage/100). products = outer query.
     *
     * @param Builder $query
     * @param float|null $fromPrice minimum price (will be multiplied by 10 for comparison)
     * @param float|null $toPrice maximum price (will be multiplied by 10 for comparison)
     * @return void
     */
    protected function applyPriceFilter($query, $fromPrice, $toPrice)
    {
        $effectivePrice = $this->etiketEffectivePriceSql();
        $query->whereHas('etikets', function ($etiketQuery) use ($fromPrice, $toPrice, $effectivePrice) {
            $etiketQuery->where('is_mojood', 1);
            $etiketQuery->where(function ($priceCondition) use ($fromPrice, $toPrice, $effectivePrice) {
                if ($fromPrice !== null) {
                    $priceCondition->whereRaw("({$effectivePrice}) >= ?", [$fromPrice]);
                }
                if ($toPrice !== null) {
                    $priceCondition->whereRaw("({$effectivePrice}) <= ?", [$toPrice]);
                }
            });
        });
    }

    /**
     * Scope to filter only parent products by price range.
     *
     * @param Builder $query
     * @param float|null $fromPrice Minimum price (can be null)
     * @param float|null $toPrice Maximum price (can be null)
     * @return Builder
     */
    public function scopeParentPriceRange(Builder $query, $fromPrice = null, $toPrice = null)
    {
        if (is_null($fromPrice) && is_null($toPrice)) {
            return $query;
        }

        $fromPrice = $fromPrice !== null ? $fromPrice * 10 : null;
        $toPrice = $toPrice !== null ? $toPrice * 10 : null;

        return $query->whereNull('parent_id')->where(function ($q) use ($fromPrice, $toPrice) {
            $this->applyPriceFilter($q, $fromPrice, $toPrice);
        });
    }
}

