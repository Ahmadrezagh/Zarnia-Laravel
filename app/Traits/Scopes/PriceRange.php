<?php
namespace App\Traits\Scopes;
use Illuminate\Database\Eloquent\Builder;

trait PriceRange
{
    /**
     * Scope to filter parent products by their own price or their children's prices.
     * This works well when combined with ->main() scope.
     * 
     * @param Builder $query
     * @param float|null $fromPrice Minimum price (can be null)
     * @param float|null $toPrice Maximum price (can be null)
     * @return Builder
     */
    public function scopePriceRange(Builder $query, $fromPrice = null, $toPrice = null)
    {
        // If both prices are null, return the query as is
        if (is_null($fromPrice) && is_null($toPrice)) {
            return $query;
        }

        // Multiply by 10 to match the database format
        $fromPrice = !is_null($fromPrice) ? $fromPrice * 10 : null;
        $toPrice = !is_null($toPrice) ? $toPrice * 10 : null;

        // Filter products based on their own price OR any of their children's prices
        return $query->where(function ($q) use ($fromPrice, $toPrice) {
            // Check if the product itself matches the price range
            $q->where(function ($ownPriceQuery) use ($fromPrice, $toPrice) {
                $this->applyPriceFilter($ownPriceQuery, $fromPrice, $toPrice);
            })
            // OR check if any of its children match the price range
            ->orWhereHas('children', function ($childrenQuery) use ($fromPrice, $toPrice) {
                $this->applyPriceFilter($childrenQuery, $fromPrice, $toPrice);
            });
        });
    }

    /**
     * Apply price filter logic considering discounted_price and regular price.
     * 
     * @param Builder $query
     * @param float|null $fromPrice
     * @param float|null $toPrice
     * @return void
     */
    protected function applyPriceFilter($query, $fromPrice, $toPrice)
    {
        $query->where(function ($priceQuery) use ($fromPrice, $toPrice) {
            // Case 1: Product has discounted_price
            $priceQuery->where(function ($discountedQuery) use ($fromPrice, $toPrice) {
                $discountedQuery->whereNotNull('discounted_price')
                    ->where('discounted_price', '>', 0);
                
                if (!is_null($fromPrice)) {
                    $discountedQuery->where('discounted_price', '>=', $fromPrice);
                }
                if (!is_null($toPrice)) {
                    $discountedQuery->where('discounted_price', '<=', $toPrice);
                }
            })
            // Case 2: Product doesn't have discounted_price, use regular price
            ->orWhere(function ($regularQuery) use ($fromPrice, $toPrice) {
                $regularQuery->where(function ($nullOrZero) {
                    $nullOrZero->whereNull('discounted_price')
                        ->orWhere('discounted_price', '=', 0);
                });
                
                if (!is_null($fromPrice)) {
                    $regularQuery->where('price', '>=', $fromPrice);
                }
                if (!is_null($toPrice)) {
                    $regularQuery->where('price', '<=', $toPrice);
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
        // If both prices are null, return the query as is
        if (is_null($fromPrice) && is_null($toPrice)) {
            return $query;
        }

        // Multiply by 10 to match the database format
        $fromPrice = !is_null($fromPrice) ? $fromPrice * 10 : null;
        $toPrice = !is_null($toPrice) ? $toPrice * 10 : null;

        // Filter only parent products
        return $query->whereNull('parent_id')->where(function ($q) use ($fromPrice, $toPrice) {
            $this->applyPriceFilter($q, $fromPrice, $toPrice);
        });
    }
}

