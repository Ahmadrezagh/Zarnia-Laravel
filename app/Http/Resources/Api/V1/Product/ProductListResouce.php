<?php

namespace App\Http\Resources\Api\V1\Product;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductListResouce extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $product = Product::find($this->id);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'weight' => $this->weight,
            'image' => $this->image,
            'cover_image' => $this->CoverImageResponsive,
            'slug' => $this->slug,
            'price' => number_format($this->price),
            'price_without_discount' => number_format($this->price_without_discount),
            'snapp_pay_each_installment' => number_format($this->price/4)
        ];
    }
}
