<?php

namespace App\Http\Resources\Api\V1\Orders;

use App\Http\Resources\Api\V1\Address\AddressResource;
use App\Http\Resources\Api\V1\GatewayResource;
use App\Http\Resources\Api\V1\ShippingResource;
use App\Http\Resources\Api\V1\ShippingTimeResource;
use App\Http\Resources\Api\V1\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{


    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $etiketItem = $this->etiketItem;
        return [
            'id' => $this->id,
            'etiket' => $etiketItem ? array_merge($etiketItem->toArray(), [
                'is_reserved' => $etiketItem->isReserved(),
            ]) : null,
            'name' => $this->name,
            'count' => $this->count,
            'price' => $this->price,
        ];
    }
}
