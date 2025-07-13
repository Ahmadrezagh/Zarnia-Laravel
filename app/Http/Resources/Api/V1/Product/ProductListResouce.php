<?php

namespace App\Http\Resources\Api\V1\Product;

use App\Models\Favorite;
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
        $is_favorite = false;
        $user = auth()->user();
        if($user){
            $is_favorite = Favorite::query()->where([
                'user_id' => $user->id,
                'product_id' => $this->id
            ])->exists();
        }
        return [
            'id' => $this->id,
            'name' => $this->name,
            'weight' => $this->weight,
            'image' => $this->image,
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
