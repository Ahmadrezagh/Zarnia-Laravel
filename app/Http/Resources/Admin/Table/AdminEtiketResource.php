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
        
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'weight' => $this->weight,
            'price' => number_format($this->price / 10) . ' تومان',
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

