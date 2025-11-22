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
        if ((int) $etiket->is_mojood !== 1) {
            return;
        }

        // Step 1: Look for a product with SAME name AND SAME weight
        $product = Product::query()
            ->where('name', '=', $etiket->name)
            ->where('weight', '=', $etiket->weight)
            ->first();

        if ($product) {
            // Product exists with same name and weight - just update its attributes
            $product->update([
                'ojrat' => $etiket->ojrat,
                'darsad_kharid' => $etiket->darsad_kharid,
                'mazaneh' => $etiket->mazaneh,
                'darsad_vazn_foroosh' => $etiket->darsad_vazn_foroosh,
            ]);
            
            $etiket->updateQuietly([
                'product_id' => $product->id,
            ]);

//            Log::info('Etiket assigned to existing product', [
//                'etiket_id' => $etiket->id,
//                'etiket_name' => $etiket->name,
//                'etiket_weight' => $etiket->weight,
//                'product_id' => $product->id,
//            ]);

        } else {
            // Step 2: No exact match - check if there's a product with same name but different weight
            $sameNameProduct = Product::query()
                ->where('name', '=', $etiket->name)
                ->whereNull('parent_id')
                ->first();

            if ($sameNameProduct) {
                // Create a variant (child product) with different weight
                $product = $sameNameProduct->replicate();
                $product->parent_id = $sameNameProduct->id;
                $product->weight = $etiket->weight;
                $product->ojrat = $etiket->ojrat;
                $product->darsad_kharid = $etiket->darsad_kharid;
                $product->mazaneh = $etiket->mazaneh;
                $product->darsad_vazn_foroosh = $etiket->darsad_vazn_foroosh;
                $product->save();

                $etiket->updateQuietly([
                    'product_id' => $product->id,
                ]);

//                Log::info('Etiket assigned to new variant product', [
//                    'etiket_id' => $etiket->id,
//                    'etiket_name' => $etiket->name,
//                    'etiket_weight' => $etiket->weight,
//                    'product_id' => $product->id,
//                    'parent_id' => $sameNameProduct->id,
//                ]);

            } else {
                // Step 3: No product with this name exists - create a new parent product
                $product = Product::create([
                    'name' => $etiket->name,
                    'weight' => $etiket->weight,
                    'price' => $etiket->price,
                    'ojrat' => $etiket->ojrat,
                    'darsad_kharid' => $etiket->darsad_kharid,
                    'mazaneh' => $etiket->mazaneh,
                    'darsad_vazn_foroosh' => $etiket->darsad_vazn_foroosh,
                ]);

                $etiket->updateQuietly([
                    'product_id' => $product->id,
                ]);

//                Log::info('Etiket assigned to new parent product', [
//                    'etiket_id' => $etiket->id,
//                    'etiket_name' => $etiket->name,
//                    'etiket_weight' => $etiket->weight,
//                    'product_id' => $product->id,
//                ]);
            }
        }

        // Final validation: Disconnect if names don't match
        $this->validateEtiketProductConnection($etiket);
    }

    /**
     * Handle the Etiket "updated" event.
     */

    public function updated(Etiket $etiket): void
    {
        if ((int) $etiket->is_mojood !== 1) {
            return;
        }

        // Keep track of old product before update
        $oldProductId = $etiket->getOriginal('product_id');

        // Step 1: Look for a product with SAME name AND SAME weight
        $product = Product::query()
            ->where('name', '=', $etiket->name)
            ->where('weight', '=', $etiket->weight)
            ->first();

        if ($product) {
            // Product exists with same name and weight - just update its attributes
            $product->update([
                'ojrat' => $etiket->ojrat,
                'darsad_kharid' => $etiket->darsad_kharid,
                'mazaneh' => $etiket->mazaneh,
                'darsad_vazn_foroosh' => $etiket->darsad_vazn_foroosh,
                'price' => $etiket->price
            ]);

            $etiket->updateQuietly([
                'product_id' => $product->id,
            ]);

//            Log::info('Etiket updated - assigned to existing product', [
//                'etiket_id' => $etiket->id,
//                'etiket_name' => $etiket->name,
//                'etiket_weight' => $etiket->weight,
//                'product_id' => $product->id,
//                'old_product_id' => $oldProductId,
//            ]);

        } else {
            // No product with same name AND weight - check if there's a product with same name but different weight
            $sameNameProduct = Product::query()
                ->where('name', '=', $etiket->name)
                ->whereNull('parent_id')
                ->first();

            if ($sameNameProduct) {
                // Create a variant (child product) with different weight
                $product = $sameNameProduct->replicate();
                $product->parent_id = $sameNameProduct->id;
                $product->weight = $etiket->weight;
                $product->ojrat = $etiket->ojrat;
                $product->darsad_kharid = $etiket->darsad_kharid;
                $product->mazaneh = $etiket->mazaneh;
                $product->darsad_vazn_foroosh = $etiket->darsad_vazn_foroosh;
                $product->price = $etiket->price;
                $product->save();

                $etiket->updateQuietly([
                    'product_id' => $product->id,
                ]);

//                Log::info('Etiket updated - assigned to new variant product', [
//                    'etiket_id' => $etiket->id,
//                    'etiket_name' => $etiket->name,
//                    'etiket_weight' => $etiket->weight,
//                    'product_id' => $product->id,
//                    'parent_id' => $sameNameProduct->id,
//                    'old_product_id' => $oldProductId,
//                ]);

            } else {
                // No product with this name exists - create a new parent product
                $newProduct = Product::create([
                    'name' => $etiket->name,
                    'weight' => $etiket->weight,
                    'price' => $etiket->price,
                    'ojrat' => $etiket->ojrat,
                    'darsad_kharid' => $etiket->darsad_kharid,
                    'mazaneh' => $etiket->mazaneh,
                    'darsad_vazn_foroosh' => $etiket->darsad_vazn_foroosh,
                ]);

                $etiket->updateQuietly([
                    'product_id' => $newProduct->id,
                ]);

//                Log::info('Etiket updated - assigned to new parent product', [
//                    'etiket_id' => $etiket->id,
//                    'etiket_name' => $etiket->name,
//                    'etiket_weight' => $etiket->weight,
//                    'product_id' => $newProduct->id,
//                    'old_product_id' => $oldProductId,
//                ]);
            }
        }

        // Step 2: Handle old product (cleanup/repair)
        if ($oldProductId && $oldProductId !== $etiket->product_id) {
            $oldProduct = Product::find($oldProductId);

            if ($oldProduct) {
                // If no etikets left → optional: delete old product
                if ($oldProduct->etikets()->count() === 0) {
                    // $oldProduct->delete(); // uncomment if you want auto-delete
                } else {
                    // Otherwise: re-run parent-name consistency repair
                    $this->fixParentRelation($oldProduct);
                }
            }
        }

        // Final validation: Disconnect if names don't match
        $this->validateEtiketProductConnection($etiket);
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
     * If etiket name doesn't match product name, set product_id to null
     */
    protected function validateEtiketProductConnection(Etiket $etiket): void
    {
        // If etiket has a product assigned
        if ($etiket->product_id) {
            $product = Product::find($etiket->product_id);
            
            if ($product) {
                // Normalize both names for comparison (handle Arabic/Persian characters)
                $etiketName = $this->normalizeName($etiket->name);
                $productName = $this->normalizeName($product->name);
                
                // If names don't match, disconnect
                if ($etiketName !== $productName) {
                    Log::warning('Etiket disconnected from product with different name', [
                        'etiket_id' => $etiket->id,
                        'etiket_name' => $etiket->name,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                    ]);
                    
                    $etiket->updateQuietly([
                        'product_id' => null,
                    ]);
                }
            } else {
                // Product doesn't exist anymore, set to null
                $etiket->updateQuietly([
                    'product_id' => null,
                ]);
            }
        }
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
     * This can be run manually to fix data integrity issues
     * 
     * Usage: app(EtiketObserver::class)->cleanupMismatchedEtikets();
     */
    public function cleanupMismatchedEtikets(): array
    {
        $disconnected = [];
        
        // Get all etikets that have a product_id
        $etikets = Etiket::whereNotNull('product_id')->with('product')->get();
        
        foreach ($etikets as $etiket) {
            if ($etiket->product) {
                $etiketName = $this->normalizeName($etiket->name);
                $productName = $this->normalizeName($etiket->product->name);
                
                if ($etiketName !== $productName) {
                    $disconnected[] = [
                        'etiket_id' => $etiket->id,
                        'etiket_name' => $etiket->name,
                        'product_id' => $etiket->product_id,
                        'product_name' => $etiket->product->name,
                    ];
                    
                    $etiket->updateQuietly(['product_id' => null]);
                }
            }
        }
        
        if (count($disconnected) > 0) {
            Log::warning('Bulk cleanup: Disconnected etikets from mismatched products', [
                'count' => count($disconnected),
                'items' => $disconnected,
            ]);
        }
        
        return $disconnected;
    }
}
