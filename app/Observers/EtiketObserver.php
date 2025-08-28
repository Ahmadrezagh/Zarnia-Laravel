<?php

namespace App\Observers;

use App\Models\Etiket;
use App\Models\Product;

class EtiketObserver
{
    /**
     * Handle the Etiket "created" event.
     */
    public function created(Etiket $etiket): void
    {
//        echo "Creating etiket $etiket->id";
        $product = Product::query()
            ->where('name','=',$etiket->name)
            ->where('weight','=',$etiket->weight)
            ->first();
        if ($product) {
            $product->update([
                'ojrat' => $etiket->ojrat,
                'darsad_kharid' => $etiket->darsad_kharid,
            ]);
            $etiket->update([
                'product_id' => $product->id,
            ]);
        }else{
            $sameNameProduct = Product::query()
                ->where('name', '=', $etiket->name)
                ->whereNull('parent_id')
                ->first();

            if ($sameNameProduct) {
                $product = $sameNameProduct->replicate();
                $product->parent_id = $sameNameProduct->id;
                $product->weight = $etiket->weight;
                $product->ojrat = $etiket->ojrat;
                $product->save();
                $etiket->update([
                    'product_id' => $product->id,
                    'darsad_kharid' => $etiket->darsad_kharid,
                ]);
            }else{
                $product = Product::create([
                    'name' => $etiket->name,
                    'weight' => $etiket->weight,
                    'price' => $etiket->price,
                    'ojrat' => $etiket->ojrat,
                    'darsad_kharid' => $etiket->darsad_kharid,
                ]);
                $etiket->update([
                    'product_id' => $product->id,
                ]);
            }
        }

    }

    /**
     * Handle the Etiket "updated" event.
     */

    public function updated(Etiket $etiket): void
    {
        // Keep track of old product before update
        $oldProductId = $etiket->getOriginal('product_id');

        // Look for another product with the same name
        $sameNameProduct = Product::query()
            ->where('name', $etiket->name)
            ->first();

        if ($sameNameProduct) {
            $sameNameProduct->update([
                'weight' => $etiket->weight,
                'price' => $etiket->price,
                'ojrat' => $etiket->ojrat,
                'darsad_kharid' => $etiket->darsad_kharid,
            ]);

            $etiket->updateQuietly([
                'product_id' => $sameNameProduct->id,
            ]);

        } else {
            $newProduct = Product::create([
                'name' => $etiket->name,
                'weight' => $etiket->weight,
                'price' => $etiket->price,
                'ojrat' => $etiket->ojrat,
                'darsad_kharid' => $etiket->darsad_kharid,
            ]);

            $etiket->updateQuietly([
                'product_id' => $newProduct->id,
            ]);
        }

        // ✅ Step 2: Handle old product (cleanup/repair)
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
}
