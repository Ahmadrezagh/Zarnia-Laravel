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
        $coverImage = $product->getFirstMedia('cover_image');
        return [
            'id' => $this->id,
            'name' => $this->name,
            'weight' => $this->weight,
            'image' => $this->image,
            'cover_image' => [
                'large' => $coverImage->getUrl('large') ?? null,
                'medium' => $coverImage->getUrl('medium') ?? null,
                'small' => $coverImage->getUrl('small') ?? null,
            ],
            'slug' => $this->slug,
            'price' => number_format($this->price),
            'price_without_discount' => number_format($this->price_without_discount),
            'snapp_pay_each_installment' => number_format($this->price/4)
        ];
    }
}
