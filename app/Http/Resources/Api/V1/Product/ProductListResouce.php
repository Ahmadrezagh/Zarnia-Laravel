<?php

namespace App\Http\Resources\Api\V1\Product;

use App\Models\Favorite;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductListResouce extends JsonResource
{
    protected $user;
    public function __construct($resource,$user = null)
    {
        parent::__construct($resource);
        if($user){
            $this->user = $user;
        }
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $product = Product::find($this->id);
        $is_favorite = false;
        if($this->user){
            $is_favorite = Favorite::query()->where([
                'user_id' => $this->user->id,
                'product_id' => $this->id
            ])->exists();
        }
        $galleryImages = $product->getMedia('gallery');
        $galleryUrls = $galleryImages->map(function ($media) {
            return $media->getUrl();
        })->toArray();
        
        // Count available etikets
        $availableCount = $product->etikets()->where('is_mojood', 1)->count();
        
        // Count available etikets with orderable_after_out_of_stock = 1
        $availableCountOrderableAfterOutOfStock = $product->etikets()
            ->where('is_mojood', 1)
            ->where('orderable_after_out_of_stock', 1)
            ->count();
        
        return [
            'id' => $this->id,
            'name' => $this->name,
            'weight' => $this->weight,
            'image' => $this->image,
            'images' => $galleryUrls,
            'cover_image' => $this->CoverImageResponsive,
            'slug' => $this->slug,
            'price' => number_format($this->price),
            'price_without_discount' => number_format($this->price_without_discount_minimum_available_product ?? 0),
            'price_range_title' => $this->price_range_title,
            'minimum_available_price' => $this->minimum_available_price,
            'minimum_available_weight' => $this->minimum_available_weight,
            'discount_percentage' => $this->discount_percentage,
            'snapp_pay_each_installment' => number_format($this->price/4),
            'is_favorite' => $is_favorite,
            'available_count' => $availableCount,
            'available_count_orderable_after_out_of_stock' => $availableCountOrderableAfterOutOfStock,
        ];
    }
}
