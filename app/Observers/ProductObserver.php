<?php

namespace App\Observers;

use App\Models\Product;

class ProductObserver
{
    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        $this->updateDiscountedPrice($product);
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        // Check if price or discount_percentage changed
        $this->updateDiscountedPrice($product);

        // ✅ Only check if product has a parent
        if ($product->parent_id) {
            $parent = Product::find($product->parent_id);

            if ($parent && $product->name !== $parent->name) {
                // Look for another parent with the same name
                $newParent = Product::query()
                    ->whereNull('parent_id')
                    ->where('name', $product->name)
                    ->first();

                if ($newParent) {
                    // ✅ Connect product to the correct parent
                    $product->updateQuietly([
                        'parent_id' => $newParent->id,
                    ]);
                } else {
                    // ❌ No matching parent → detach from current parent
                    $product->updateQuietly([
                        'parent_id' => null,
                    ]);
                }
            }
        }
        
        // If this is a parent product and discount_percentage changed, update all children
        if (!$product->parent_id && $product->wasChanged('discount_percentage')) {
            $this->updateChildrenDiscountedPrices($product);
        }
    }

    /**
     * Update discounted prices for all children when parent's discount_percentage changes
     */
    private function updateChildrenDiscountedPrices(Product $parent): void
    {
        $children = Product::where('parent_id', $parent->id)->get();
        
        foreach ($children as $child) {
            $this->updateDiscountedPrice($child);
        }
    }


    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "restored" event.
     */
    public function restored(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "force deleted" event.
     */
    public function forceDeleted(Product $product): void
    {
        //
    }

    /**
     * Calculate and update discounted price.
     */
    private function updateDiscountedPrice(Product $product)
    {
        // Get raw price value (stored multiplied by 10) and discount percentage
        $rawPrice = $product->getRawOriginal('price');
        
        // If product has parent, use parent's discount_percentage
        $discountPercentage = 0;
        if ($product->parent_id) {
            $parent = Product::find($product->parent_id);
            if ($parent) {
                $discountPercentage = $parent->getRawOriginal('discount_percentage') ?? 0;
            }
        } else {
            // Use own discount_percentage
            $discountPercentage = $product->getRawOriginal('discount_percentage') ?? 0;
        }

        if ($rawPrice != 0 && $discountPercentage != 0) {
            // Calculate discounted price
            // Raw price is stored multiplied by 10, so divide by 10 to get actual price
            // discounted_price is stored as-is (not multiplied by 10)
            $discountedPrice = ($rawPrice / 10) * (1 - $discountPercentage / 100);

            // Round to nearest 1000 (last three digits to 000)
            $discountedPrice = round($discountedPrice, -3);

            $product->discounted_price = $discountedPrice;
        } else {
            $product->discounted_price = null;
        }

        $product->saveQuietly();
    }
}
