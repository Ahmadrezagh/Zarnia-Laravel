<?php

namespace App\Http\Resources\Api\V1\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductFeedResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $baseUrl = rtrim(setting('url') ?? config('app.url'), '/');
        
        // Get attribute values for this product
        $productAttributeValues = $this->attributeValues ?? collect();
        
        // Get brand, GTIN, and color from attributes
        $brand = null;
        $gtin = null;
        $color = null;
        
        if ($productAttributeValues->isNotEmpty()) {
            // Find by attribute name
            foreach ($productAttributeValues as $attrValue) {
                $attrName = $attrValue->attribute->name ?? '';
                if (in_array(strtolower($attrName), ['brand', 'برند']) && !$brand) {
                    $brand = $attrValue->value;
                } elseif (in_array(strtolower($attrName), ['gtin', 'gtin']) && !$gtin) {
                    $gtin = $attrValue->value;
                } elseif (in_array(strtolower($attrName), ['color', 'رنگ']) && !$color) {
                    $color = $attrValue->value;
                }
            }
        }
        
        // Get image URL
        $imageUrl = $this->image;
        if ($imageUrl === asset('img/no_image.jpg')) {
            $imageUrl = null;
        }
        
        // Get prices
        $regularPrice = $this->getRawOriginal('price') / 10; // Convert from stored format
        $salePrice = $this->discounted_price ? $this->discounted_price : null;
        
        // If sale_price is null, use regular_price
        if ($salePrice === null) {
            $salePrice = $regularPrice;
        }
        
        // Get availability
        $availability = $this->single_count > 0 ? 'in_stock' : 'out_of_stock';
        
        // Get category
        $category = null;
        if ($this->categories && $this->categories->isNotEmpty()) {
            $category = $this->categories->pluck('title')->implode(' > ');
        }
        
        // Get shipping info (from request if passed, otherwise from first shipping)
        $shipping = $request->get('shipping');
        $costShipping = $shipping['cost'] ?? null;
        $timeDelivery = $shipping['time'] ?? null;
        
        // Build description object
        $description = [];
        if ($this->description) {
            $description['text'] = strip_tags($this->description);
        }
        if ($this->meta_description) {
            $description['meta'] = $this->meta_description;
        }
        
        // Build result array
        $result = [
            'id' => (string) $this->id,
            'title' => $this->name,
            'subtitle' => $this->meta_description ?? strip_tags($this->description ?? ''),
            'link' => $baseUrl . '/products/' . $this->slug,
            'image_link' => $imageUrl,
            'availability' => $availability,
            'regular_price' => (int) $regularPrice,
            'sale_price' => (int) $salePrice,
            'category' => $category,
            'description' => !empty($description) ? $description : null,
            'brand' => $brand,
        ];
        
        // Add optional fields if they exist
        if ($gtin) {
            $result['GTIN'] = $gtin;
        }
        
        if ($color) {
            $result['color'] = $color;
        }
        
        if ($costShipping !== null) {
            $result['cost_shipping'] = (int) $costShipping;
        }
        
        if ($timeDelivery) {
            $result['time_delivery'] = $timeDelivery;
        }
        
        return $result;
    }
}
