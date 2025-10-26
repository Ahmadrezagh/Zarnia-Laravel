<?php

namespace App\Http\Resources\Api\V1\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SimpleProductResource extends JsonResource
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
            'single_count' => $this->SingleCount ,
            'price' => number_format($this->price),
            'slug' => $this->slug,
            'weight' => $this->weight,
            'description' => $this->description,
            'image' => $this->image,
            'cover_image' => $this->CoverImageResponsive,
        ];
    }
}
