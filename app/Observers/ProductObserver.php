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
        $this->updateDiscountedPrice($product);
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
        if ($product->price != 0 && $product->discount_percentage != 0) {
            // Calculate discounted price
            $discountedPrice = $product->price * (1 - $product->discount_percentage / 100);

            // Round to nearest 1000 (last three digits to 000)
            $discountedPrice = round($discountedPrice, -3);

            $product->discounted_price = $product->price - $discountedPrice;
        } else {
            $product->discounted_price = null;
        }

        $product->saveQuietly();
    }
}
