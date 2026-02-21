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

        // Get gallery images
        $galleryUrls = $this->getMedia('gallery')->map(fn($media) => $media->getUrl())->values()->toArray();
        if ($imageUrl && !in_array($imageUrl, $galleryUrls)) {
            array_unshift($galleryUrls, $imageUrl);
        }
        $imageLink = !empty($galleryUrls) ? $galleryUrls : ($imageUrl ? [$imageUrl] : []);
        
        // Get available etikets (own + children's), pick the one with lowest price
        $availableEtikets = ($this->relationLoaded('etikets') ? $this->etikets : $this->etikets()->get())
            ->where('is_mojood', 1);

        if ($availableEtikets->isEmpty() && $this->relationLoaded('children')) {
            $availableEtikets = $this->children->flatMap(function ($child) {
                $etikets = $child->relationLoaded('etikets') ? $child->etikets : $child->etikets()->get();
                return $etikets->where('is_mojood', 1);
            });
        }

        $lowestEtiket = $availableEtikets->sortBy(fn($e) => $e->price)->first();

        // sale_price: effective price of the cheapest available etiket (discounted if applicable)
        $salePrice = $lowestEtiket ? $lowestEtiket->price : 0;

        // regular_price: original price (before discount) of the same etiket
        $regularPrice = $lowestEtiket ? $lowestEtiket->original_price : $salePrice;
        
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
            'image_link' => $imageLink,
            'availability' => $availability,
            'regular_price' => (int) $regularPrice,
            'sale_price' => (int) $salePrice,
            'category' => $category,
            'description' => !empty($description) ? $description : null,
        ];
        
        // Add optional fields if they exist
        if ($brand) {
            $result['brand'] = $brand;
        }
        
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
