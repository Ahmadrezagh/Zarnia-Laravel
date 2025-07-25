<?php

namespace App\Http\Resources\Api\V1\Product;

use App\Http\Resources\Api\V1\Categories\CategoryResource;
use App\Models\Favorite;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductItemResouce extends JsonResource
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
        $is_favorite = false;
        $sampleProducts = new ProductListCollection(Product::query()->inRandomOrder()->take(15)->get());
        if($this->user){
            $is_favorite = Favorite::query()->where([
                'user_id' => $this->user->id,
                'product_id' => $this->id
            ])->exists();
        }
        $product = Product::find($this->id);
        $coverImage = $product->getFirstMedia('cover_image');
        $galleryImages = $product->getMedia('gallery');
        $galleryUrls = $galleryImages->map(function ($media) {
            return [
                'xlarge' => $media->getUrl('xlarge'),
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
            'cover_image' => $this->CoverImageResponsive,
            'gallery_images' => $galleryUrls,
            'gallery' => $this->getMedia('gallery')->map(function ($media, $index) {
                $url = $media->getUrl();
                return $url
                ;
            })->toArray(),
            'slug' => $this->slug,
            'price' => number_format($this->price),
            'price_without_discount' => number_format($this->price_without_discount),

            'discount_percentage' => $this->discount_percentage,
            'snapp_pay_each_installment' => number_format($this->price/4),
            'children' => ProductListCollection::collection($this->children),
            'related_products' => $sampleProducts,
            'complementary_products' => $sampleProducts,
            'categories' => CategoryResource::collection($this->categories),
            'is_favorite' => $is_favorite,
            'purity' => '18',
            'gold_price' => '6640400',
            'options' => [
                'title' => 'سایز',
                'value' => '8'
            ],

        ];
    }
}
