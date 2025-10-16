<?php

namespace App\Http\Resources\Admin\Table;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminProductResource extends JsonResource
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
            'nameUrl' => $this->nameUrl,
            'slug' => $this->slug,
            'image' => $this->image,
            'weight' => $this->weight,
            'ojrat' => $this->ojrat,
            'darsad_kharid' => $this->darsad_kharid,
            'price' => number_format($this->price)." تومان",
            'categories_title' => $this->categories_title,
            'discount_percentage' => $this->discount_percentage,
            'count' => $this->count,
            'etiketsCodeAsArray' => [],
            'parent_id' => $this->parent_id,
            'view_count' => $this->ViewCount,
            'frontend_url' => $this->frontend_url
        ];
    }
}
