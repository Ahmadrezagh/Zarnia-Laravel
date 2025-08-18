<?php

namespace App\Http\Resources\Admin\Order;

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
        return [
            'orderColumn' => $this->orderColumn,
            'persianStatus' => $this->persianStatus,
            'firstImageOfOrderItem' => $this->firstImageOfOrderItem,
            'productNameCol' => $this->productNameCol,
            'WeightCol' => $this->WeightCol,
            'AddressCol' => $this->AddressCol,
            'SumCountAndAmountCol' => $this->SumCountAndAmountCol,
        ];
    }
}
