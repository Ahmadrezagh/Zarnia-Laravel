<?php

namespace App\Http\Resources\Api\V1\Categories;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
            'title' => $this->title,
            'slug' => $this->slug,
            'image' => $this->image,
            'images' => [
                'large' => $this->image,
                'medium' => $this->image,
                'small' => $this->image,
            ],
            'show_in_nav' => $this->show_in_nav ?? false,
        ];
    }
}
