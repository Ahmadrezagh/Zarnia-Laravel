<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShoppingCartItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Get price from etiket if available, otherwise fallback to product's lowest etiket price
        $etiketRow = $this->etiketItem;
        $itemPrice = $etiketRow ? ($etiketRow->price / 10) : 0;

        // Get weight from etiket if available, otherwise fallback to product's lowest etiket weight
        $itemWeight = $etiketRow ? $etiketRow->weight : 0;
        
        return [
            'id' => $this->id,
            'product' => $this->product->name,
            'product_slug' => $this->product->slug,
            'product_weight' => $itemWeight,
            'count' => $this->count,
            'image' => $this->product->image,
            'item_price' => $itemPrice,
            'total_price' => ($itemPrice * $this->count),
            'item_price_formatted' => number_format($itemPrice),
            'total_price_formatted' => number_format($itemPrice * $this->count),
            'etiket' => $etiketRow ? [
                'id' => $etiketRow->id,
                'code' => $etiketRow->code,
                'weight' => $etiketRow->weight,
                'price' => $etiketRow->price / 10,
            ] : null,
            'etiket_code' => $etiketRow ? $etiketRow->code : null,
        ];
    }
}
