<?php

namespace App\Http\Resources\Api\V1\PorductSlider;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductSliderResource extends JsonResource
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
            'query' => $this->query,
            'show_more' => boolval($this->show_more),
            'buttons' => ProductSliderButtonResource::collection($this->buttons)
        ];
    }
}
