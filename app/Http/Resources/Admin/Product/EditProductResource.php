<?php

namespace App\Http\Resources\Admin\Product;

use App\Http\Resources\Api\V1\Categories\CategoryResource;
use App\Models\AttributeGroup;
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
        $attribute_group_id = $this->attribute_group_id;
        $attribute_group_str = "";
        if($attribute_group_id){
            $attribute_group = AttributeGroup::find($attribute_group_id);
            if($attribute_group){
                $attribute_group_str = $attribute_group->name;
            }
        }
        return [
            'id' => $this->id,
            'urlOfProduct' => url($this->slug) ,
            'frontend_url' => $this->frontend_url,
            'name' => $this->name,
            'attribute_group_id' => $attribute_group_id,
            'attribute_group_str' => $attribute_group_str ,
            'slug' => $this->slug,
            'image' => $this->image,
            'weight' => $this->weight,
            'ojrat' => $this->ojrat,
            'darsad_kharid' => $this->darsad_kharid,
            'categories_title' => $this->categories_title,
            'category_ids' => $this->categories()->pluck('category_id')->toArray(),
            'discount_percentage' => $this->discount_percentage,
            'count' => $this->count,
            'description' => $this->description,
            'price' => $this->originalPrice ,
            'discounted_price' => $this->discounted_price,
            'categories' => CategoryResource::collection(Category::all()),
            'gallery' => $this->getMedia('gallery')->map(function ($media, $index) {
                $url = $media->getUrl();
                return [
                    'id' => 'gallery-image-' . $media->id, // Use media ID for uniqueness
                    'src' => $url
                ];
            })->toArray(),
            'is_comprehensive' => $this->is_comprehensive,
            'orderable_after_out_of_stock' => $this->orderable_after_out_of_stock ?? false,
            'comprehensive_products' => $this->is_comprehensive ? 
                $this->products()->get()->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'price' => $product->price,
                        'weight' => $product->weight,
                        'count' => $product->count,
                        'image' => $product->image,
                    ];
                })->toArray() : [],
        ];
    }
}
