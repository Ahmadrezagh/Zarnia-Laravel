<?php

namespace App\Http\Resources\Admin\Table;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminEtiketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $product = $this->product;
        $categories = $product ? $product->categories->pluck('title')->join('، ') : '-';

        // Show product price if etiket is related to a product, otherwise show '-'
        $price = '-';
        if ($product) {
            // Get product's raw price (stored multiplied by 10) and divide by 10
            $rawPrice = $product->getRawOriginal('price');
            if ($rawPrice > 0) {
                $price = number_format($rawPrice / 10) . ' تومان';
            }
        }

        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'weight' => $this->weight,
            'price' => $price,
            'product_name' => $product ? $product->name : '-',
            'product_id' => $this->product_id,
            'categories' => $categories,
            'is_mojood' => $this->is_mojood ? 'موجود' : 'ناموجود',
            'ojrat' => $this->ojrat ?? '-',
            'darsad_kharid' => $this->darsad_kharid ?? '-',
            'darsad_vazn_foroosh' => $this->darsad_vazn_foroosh ?? '-',
            'created_at' => $this->created_at ? $this->created_at->format('Y/m/d H:i') : '-',
        ];
    }
}

