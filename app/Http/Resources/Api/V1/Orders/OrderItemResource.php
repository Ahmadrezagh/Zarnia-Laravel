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
    protected $payment_urll;
    public function __construct($resource,$payment_url = null)
    {
        $this->payment_urll = $payment_url;
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'etiket' => $this->etiket,
            'name' => $this->name,
            'count' => $this->count,
            'price' => $this->price,
        ];
    }
}
