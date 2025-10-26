<?php

namespace App\Http\Resources\Api\V1\Gifts;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;

class Giftresource extends JsonResource
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
            'code' => $this->code,
            'percentage' => $this->percentage ,
            'amount' => $this->amount,
            'expires_at' => $this->expires_at,
            'expire_at_jalali' => Jalalian::forge($this->expires_at)->format('Y-m-d'),

        ];
    }
}
