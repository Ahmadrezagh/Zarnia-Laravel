<?php

namespace App\Observers;

use App\Models\Etiket;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class EtiketObserver
{
    /**
     * Handle the Etiket "created" event.
     */
    public function created(Etiket $etiket): void
    {
        // NOTE: This method has been disabled because etiket->name field has been removed.
        // Etikets should now be created with a product_id already assigned.
        // If you need to implement auto-assignment logic, use product_id directly instead of name matching.
        
        // if ((int) $etiket->is_mojood !== 1) {
        //     return;
        // }

        // // If etiket already has a product_id assigned, update product attributes
        // if ($etiket->product_id) {
        //     $product = Product::find($etiket->product_id);
        //     if ($product) {
        //         $product->update([
        //             'ojrat' => $etiket->ojrat,
        //             'mazaneh' => $etiket->mazaneh,
        //         ]);
        //     }
        // }
    }

    /**
     * Handle the Etiket "updated" event.
     */

    public function updated(Etiket $etiket): void
    {
        // NOTE: This method has been disabled because etiket->name field has been removed.
        // Etikets should now be created with a product_id already assigned.
        // If you need to implement auto-assignment logic, use product_id directly instead of name matching.
        
        // if ((int) $etiket->is_mojood !== 1) {
        //     return;
        // }

        // // If etiket has a product_id assigned, update product attributes
        // if ($etiket->product_id) {
        //     $product = Product::find($etiket->product_id);
        //     if ($product) {
        //         $product->update([
        //             'ojrat' => $etiket->ojrat,
        //             'mazaneh' => $etiket->mazaneh,
        //         ]);
        //     }
        // }
    }

// ✅ Helper to enforce parent-child consistency (same as ProductObserver)
    protected function fixParentRelation(Product $product): void
    {
        $parent = Product::query()
            ->where('name', $product->name)
            ->whereNull('parent_id')
            ->first();

        if ($parent) {
            if ($product->id !== $parent->id) {
                $product->updateQuietly(['parent_id' => $parent->id]);
            }
        } else {
            $product->updateQuietly(['parent_id' => null]);
        }
    }

    /**
     * Disconnect etikets from products with mismatched names
     * NOTE: This method has been disabled because etiket->name field has been removed.
     */
    protected function validateEtiketProductConnection(Etiket $etiket): void
    {
        // This method is no longer relevant since etikets don't have a name field
        // Etikets are now directly linked to products via product_id
    }

    /**
     * Normalize name for comparison (handle Arabic/Persian character differences)
     */
    protected function normalizeName($name): string
    {
        // Arabic Ye → Persian Ye
        $name = str_replace(['ي', 'ی'], 'ی', $name);
        
        // Arabic Kaf → Persian Kaf
        $name = str_replace('ك', 'ک', $name);
        
        // Trim whitespace
        $name = trim($name);
        
        return $name;
    }

    /**
     * Handle the Etiket "deleted" event.
     */
    public function deleted(Etiket $etiket): void
    {
        //
    }

    /**
     * Handle the Etiket "restored" event.
     */
    public function restored(Etiket $etiket): void
    {
        //
    }

    /**
     * Handle the Etiket "force deleted" event.
     */
    public function forceDeleted(Etiket $etiket): void
    {
        //
    }

    /**
     * Clean up all etikets with mismatched product names
     * NOTE: This method has been disabled because etiket->name field has been removed.
     */
    public function cleanupMismatchedEtikets(): array
    {
        // This method is no longer relevant since etikets don't have a name field
        return [];
    }
}
