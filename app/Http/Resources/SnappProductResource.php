<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SnappProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $baseUrl = rtrim(setting('url') ?? config('app.url'), '/');
        $productAttributeValues = $this->attributeValues ?? collect();

        $brand = $this->extractAttributeValue($productAttributeValues, ['brand', 'برند']);

        $imageUrl = $this->image;
        if ($imageUrl === asset('img/no_image.jpg')) {
            $imageUrl = null;
        }

        $galleryUrls = $this->getMedia('gallery')->map(fn ($media) => $media->getUrl())->values()->toArray();
        if ($imageUrl && ! in_array($imageUrl, $galleryUrls)) {
            array_unshift($galleryUrls, $imageUrl);
        }
        $imageLink = ! empty($galleryUrls) ? $galleryUrls : ($imageUrl ? [$imageUrl] : []);

        $availableEtikets = ($this->relationLoaded('etikets') ? $this->etikets : $this->etikets()->get())
            ->where('is_mojood', 1);

        if ($availableEtikets->isEmpty() && $this->relationLoaded('children')) {
            $availableEtikets = $this->children->flatMap(function ($child) {
                $etikets = $child->relationLoaded('etikets') ? $child->etikets : $child->etikets()->get();

                return $etikets->where('is_mojood', 1);
            });
        }

        $lowestEtiket = $availableEtikets->sortBy(fn ($e) => $e->price)->first();
        $salePrice = $lowestEtiket ? (int) $lowestEtiket->price : 0;
        $regularPrice = $lowestEtiket ? (int) $lowestEtiket->original_price : $salePrice;

        $availability = $this->single_count > 0 ? 'in stock' : 'out of stock';

        $category = null;
        if ($this->categories && $this->categories->isNotEmpty()) {
            $category = $this->categories->pluck('title')->implode(' > ');
        }

        $subtitle = $this->meta_description;
        if (! $subtitle && $this->description) {
            $subtitle = strip_tags($this->description);
        }

        $shipping = $request->get('shipping', []);
        $shippingCost = $shipping['cost'] ?? null;
        $deliveryTime = $shipping['time'] ?? null;

        $result = [
            'id' => (int) $this->id,
            'title' => $this->name,
            'subtitle' => $subtitle ?? '',
            'link' => $baseUrl.'/products/'.$this->slug,
            'image_link' => $imageLink,
            'availability' => $availability,
            'regular_price' => $regularPrice,
            'sale_price' => $salePrice,
            'category' => $category,
            'description' => $this->buildDescriptionObject($productAttributeValues),
        ];

        if ($brand) {
            $result['brand'] = $brand;
        }

        if ($shippingCost !== null) {
            $result['shipping_cost'] = (int) $shippingCost;
        }

        if ($deliveryTime) {
            $result['delivery_time'] = $deliveryTime;
        }

        return $result;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\AttributeValue>  $attributeValues
     */
    private function buildDescriptionObject($attributeValues): array
    {
        if (! empty($this->description)) {
            $decoded = json_decode($this->description, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        $description = [];

        foreach ($attributeValues as $attrValue) {
            $attrName = $attrValue->attribute->name ?? '';
            if ($attrName === '' || $this->isExcludedDescriptionAttribute($attrName)) {
                continue;
            }

            $value = trim(
                ($attrValue->attribute->prefix_sentence ?? '').' '.
                $attrValue->value.' '.
                ($attrValue->attribute->postfix_sentence ?? '')
            );

            if ($value !== '') {
                $description[$attrName] = $value;
            }
        }

        return $description;
    }

    private function isExcludedDescriptionAttribute(string $attrName): bool
    {
        return in_array(strtolower($attrName), ['brand', 'برند', 'gtin'], true);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\AttributeValue>  $attributeValues
     * @param  array<int, string>  $names
     */
    private function extractAttributeValue($attributeValues, array $names): ?string
    {
        foreach ($attributeValues as $attrValue) {
            $attrName = $attrValue->attribute->name ?? '';
            if (in_array(strtolower($attrName), array_map('strtolower', $names), true)) {
                return $attrValue->value ?: null;
            }
        }

        return null;
    }
}
