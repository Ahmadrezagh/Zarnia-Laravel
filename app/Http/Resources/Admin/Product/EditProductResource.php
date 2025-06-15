<?php

namespace App\Http\Resources\Admin\Product;

use App\Http\Resources\Api\V1\Categories\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EditProductResource extends JsonResource
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
            'slug' => $this->slug,
            'image' => $this->image,
            'weight' => $this->weight,
            'ojrat_percentage' => $this->ojrat_percentage,
            'categories_title' => $this->categories_title,
            'category_ids' => $this->categories()->pluck('category_id')->toArray(),
            'discount_percentage' => $this->discount_percentage,
            'count' => $this->count,
            'description' => $this->description,
            'price' => $this->price,
            'discounted_price' => $this->discounted_price,
            'categories' => CategoryResource::collection(Category::all()),
            'gallery' => $this->getMedia('gallery')->map(function ($media, $index) {
                $url = $media->getUrl();
                return [
                    'id' => 'gallery-image-' . $media->id, // Use media ID for uniqueness
                    'source' => $url
                ];
            })->toArray(),
        ];
    }
}
