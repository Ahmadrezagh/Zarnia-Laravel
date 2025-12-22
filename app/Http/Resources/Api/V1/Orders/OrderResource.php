<?php

namespace App\Http\Resources\Api\V1\Orders;

use App\Http\Resources\Api\V1\Address\AddressResource;
use App\Http\Resources\Api\V1\GatewayResource;
use App\Http\Resources\Api\V1\ShippingResource;
use App\Http\Resources\Api\V1\ShippingTimeResource;
use App\Http\Resources\Api\V1\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;

class OrderResource extends JsonResource
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
            'user' => UserResource::make($this->user),
            'address' => AddressResource::make($this->address),
            'shipping' => ShippingResource::make($this->shipping),
            'shipping_time' => ShippingTimeResource::make($this->shippingTime),
            'gateway' => GatewayResource::make($this->gateway),
            'status' => $this->persianStatus,
            'status_en' => $this->status,
            'discount_code' => $this->discount_code,
            'discount_percentage' => $this->discount_percentage,
            'discount_price' => $this->discount_price,
            'total_amount' => $this->total_amount,
            'final_amount' => $this->final_price,
            'paid_at' => $this->paid_at,
            'note' => $this->notem,
            'payment_url' => $this->payment_urll,
            'items' => OrderItemResource::collection($this->orderItems),
            'transaction_id' => $this->transaction_id,
            'reference' => $this->reference,
            'print_url' => route('order.print',$this->id),
            'created_at' => Jalalian::forge($this->created_at)->format('Y-m-d'),
        ];
    }
}
