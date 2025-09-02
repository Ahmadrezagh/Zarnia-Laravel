<?php

namespace App\Http\Resources\Api\V1\QA;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QAResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'question' => $this->question,
            'answer' => $this->answer,
        ];
    }
}
