<?php

namespace App\Http\Resources\Api\V1\IndexButton;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IndexButtonResource extends JsonResource
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
        ];
    }
}
