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
            // Get price from etiket if available, otherwise use product's lowest etiket price
            $itemPrice = $item->etiketItem ? ($item->etiketItem->price / 10) : $item->product->price;
            $price = $price + ($itemPrice * $item->count);
        }
        return $price;
    }
}
