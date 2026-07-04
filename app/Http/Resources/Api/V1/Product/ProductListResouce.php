<?php

namespace App\Http\Resources\Api\V1\Product;

use App\Models\Etiket;
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
        
        
        // Count available etikets with orderable_after_out_of_stock = 1
        $availableCountOrderableAfterOutOfStock = $product->etikets()
            ->where('is_mojood', 1)
            ->where('orderable_after_out_of_stock', 1)
            ->count();

        // fast_delivery: true if at least one available etiket has a numeric code,
        // but false if ALL available etiket codes start with "s-"
        $availableEtiketCodes = $product->etikets()
            ->where('is_mojood', 1)
            ->pluck('code');

        $hasNumericCode = $availableEtiketCodes->contains(fn($code) => is_numeric($code));
        $allStartWithS = $availableEtiketCodes->isNotEmpty() && $availableEtiketCodes->every(fn($code) => str_starts_with((string) $code, 's-'));

        // true if at least one numeric code exists; false if all codes start with "s-" (overrides)
        $fast_delivery = $allStartWithS ? false : true;

        // Availability: at least one effectively available etiket (comprehensive etikets check related stock).
        if ((int) ($product->is_comprehensive ?? 0) === 1) {
            $availability = $product->hasAnyAvailableComprehensiveEtiket();
        } else {
            $hasOwnAvailableEtiket = $product->etikets()->where('is_mojood', 1)->exists();
            if ($hasOwnAvailableEtiket) {
                $availability = true;
            } else {
                $hasChildWithAvailableEtiket = $product->relationLoaded('children') && $product->children->isNotEmpty()
                    ? Etiket::whereIn('product_id', $product->children->pluck('id'))->where('is_mojood', 1)->exists()
                    : $product->children()->whereHas('etikets', fn ($q) => $q->where('is_mojood', 1))->exists();
                $availability = $hasChildWithAvailableEtiket;
            }
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            // Only comprehensive products should use minimum available weight.
            'weight' => (int) ($this->is_comprehensive ?? 0) === 1
                ? ($this->minimum_available_weight ?? $this->weight)
                : $this->weight,
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
            'availability' => $availability,
            'available_count' => $this->count,
            'available_count_orderable_after_out_of_stock' => $availableCountOrderableAfterOutOfStock,
            'fast_delivery' => $fast_delivery,
        ];
    }
}
