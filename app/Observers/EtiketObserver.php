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
//        echo "Updating etiket $etiket->id";
        // Look for another product with the same name
        $sameNameProduct = Product::query()
            ->where('name', $etiket->name)
            ->first();

        if ($sameNameProduct) {
            // ✅ If product exists with same name → update it
            $sameNameProduct->update([
                'weight' => $etiket->weight,
                'price' => $etiket->price,
                'ojrat' => $etiket->ojrat,
                'darsad_kharid' => $etiket->darsad_kharid,
            ]);

            // Link Etiket to that product
            $etiket->updateQuietly([
                'product_id' => $sameNameProduct->id,
            ]);

        } else {
            // ❌ No product with same name → create new one
            $newProduct = Product::create([
                'name' => $etiket->name,
                'weight' => $etiket->weight,
                'price' => $etiket->price,
                'ojrat' => $etiket->ojrat,
                'darsad_kharid' => $etiket->darsad_kharid,
            ]);

            // Link Etiket to new product
            $etiket->updateQuietly([
                'product_id' => $newProduct->id,
            ]);
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
