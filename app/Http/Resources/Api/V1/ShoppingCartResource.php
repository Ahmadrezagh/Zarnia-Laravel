<?php

namespace App\Http\Resources\Api\V1;

use App\Models\ShoppingCartItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShoppingCartResource extends JsonResource
{
    protected $items;
    public function __construct($resource, $items)
    {
        $this->items = $items;
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $sumPrice = $this->sumPrice();
        return [

            'items' => ShoppingCartItemResource::collection($this->items),
            'sumPrice' => $sumPrice,
            'sumPrice_formatted' => number_format($sumPrice)
        ];
    }

    public function sumPrice()
    {
        $price = 0;
        foreach ($this->items as $item) {
            $price = $price + ( $item->product->price * $item->count );
        }
        return $price;
    }
}
