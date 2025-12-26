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
        return [
            'id' => $this->id,
            'product' => $this->product->name,
            'product_slug' => $this->product->slug,
            'product_weight' => $this->product->weight ?? 0,
            'count' => $this->count,
            'image' => $this->product->image,
            'item_price' => $this->product->price,
            'total_price' => ($this->product->price * $this->count),
            'item_price_formatted' => number_format($this->product->price),
            'total_price_formatted' => number_format($this->product->price * $this->count),
            'etiket' => $this->etiket ? [
                'id' => $this->etiket->id,
                'code' => $this->etiket->code,
                'name' => $this->etiket->name,
                'weight' => $this->etiket->weight,
            ] : null,
        ];
    }
}
