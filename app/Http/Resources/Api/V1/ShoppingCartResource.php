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
            'sumPriceFormatted' => number_format($sumPrice),
            'count' => count($this->items),
        ];
    }

    public function sumPrice()
    {
        $price = 0;
        foreach ($this->items as $item) {
            // Since each cart item now represents one etiket, count is always 1
            $price = $price + $item->product->price;
        }
        return $price;
    }
}
