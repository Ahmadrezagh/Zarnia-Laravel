<?php

namespace App\Http\Resources\Api\V1\Address;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
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
            'receiver_name' => $this->receiver_name,
            'receiver_phone' => $this->receiver_phone,
            'address' => $this->address,
            'province' => $this->province->name ,
            'city' => $this->city->name,
            'postal_code' => $this->postal_code
        ];
    }
}
