<?php

namespace App\Http\Resources\Api\V1\Product;

use App\Http\Resources\Api\V1\Categories\CategoryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductItemResouce extends JsonResource
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
            'name' => $this->name,
            'weight' => $this->weight,
            'description' =>fake()->text(200),
            'image' => $this->image,
            'gallery' => $this->gallery,
            'slug' => $this->slug,
            'price' => number_format($this->price),
            'price_without_discount' => number_format($this->price_without_discount),
            'snapp_pay_each_installment' => number_format($this->price/4),
            'children' => ProductListResouce::collection($this->children),
            'related_products' => [],
            'complementary_products' => [],
            'categories' => CategoryResource::collection($this->categories),
        ];
    }
}
