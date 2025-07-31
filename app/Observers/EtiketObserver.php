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
        $product = $etiket->product;
        $product->update([
            'name' => $etiket->name,
            'ojrat' => $etiket->ojrat,
            'weight' => $etiket->weight,
            'price' => $etiket->price,
            'darsad_kharid' => $etiket->darsad_kharid,
        ]);
        $sameNameProduct = Product::query()
            ->where('name', '=', $etiket->name)
            ->whereNull('parent_id')
            ->where('id','!=',$product->id)
            ->first();
        if ($sameNameProduct) {
            $product->update([
                'parent_id' => $sameNameProduct->id,
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
