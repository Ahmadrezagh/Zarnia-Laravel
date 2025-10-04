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

        $this->user = $user;
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
        $galleryUrls = $galleryImages ? $galleryImages->map(function ($media) {
            return $media->getUrl();
        })->toArray() : [] ;
        return [
            'id' => $this->id,
            'name' => $this->name,
            'weight' => $this->weight,
            'image' => $this->image,
            'images' => $galleryUrls,
            'cover_image' => $this->CoverImageResponsive,
            'slug' => $this->slug,
            'price' => number_format($this->price),
            'price_without_discount' => number_format($this->price_without_discount),
            'discount_percentage' => $this->discount_percentage,
            'snapp_pay_each_installment' => number_format($this->price/4),
            'is_favorite' => $is_favorite,
        ];
    }
}
