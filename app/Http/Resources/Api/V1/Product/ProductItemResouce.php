<?php

namespace App\Http\Resources\Api\V1\Product;

use App\Http\Resources\Api\V1\Categories\CategoryResource;
use App\Models\Product;
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
        $product = Product::find($this->id);
        $coverImage = $product->getFirstMedia('cover_image');
        $galleryImages = $product->getMedia('gallery');
        $galleryUrls = $galleryImages->map(function ($media) {
            return [
                'large' => $media->getUrl('large'),
                'medium' => $media->getUrl('medium'),
                'small' => $media->getUrl('small'),
            ];
        })->toArray();
        return [
            'id' => $this->id,
            'name' => $this->name,
            'weight' => $this->weight,
            'description' => $this->description,
            'image' => $this->image,
            'cover_image' => [
                'large' => $coverImage->getUrl('large') ?? null,
                'medium' => $coverImage->getUrl('medium') ?? null,
                'small' => $coverImage->getUrl('small') ?? null,
            ],
            'gallery_images' => $galleryUrls,
            'gallery' => $this->getMedia('gallery')->map(function ($media, $index) {
                $url = $media->getUrl();
                return $url
                ;
            })->toArray(),
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
