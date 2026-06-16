<?php

namespace App\Http\Resources;

use App\Models\Etiket;
use App\Models\Product;
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
        /** @var Product $product */
        $product = $this->resource;
        /** @var Etiket $etiket */
        $etiket = $product->snappEtiket;

        $baseUrl = rtrim(setting('url') ?? config('app.url'), '/');
        $productAttributeValues = $product->attributeValues ?? collect();

        $imageUrl = $product->image;
        if ($imageUrl === asset('img/no_image.jpg')) {
            $imageUrl = null;
        }

        $galleryUrls = $product->getMedia('gallery')->map(fn ($media) => $media->getUrl())->values()->toArray();
        if ($imageUrl && ! in_array($imageUrl, $galleryUrls)) {
            array_unshift($galleryUrls, $imageUrl);
        }
        $imageLink = ! empty($galleryUrls) ? $galleryUrls : ($imageUrl ? [$imageUrl] : []);

        $salePrice = (int) $etiket->price;
        $regularPrice = (int) $etiket->original_price;

        $availability = $etiket->is_mojood ? 'in stock' : 'out of stock';

        $category = null;
        if ($product->categories && $product->categories->isNotEmpty()) {
            $category = $product->categories->pluck('title')->implode(' > ');
        }

        $subtitle = $product->meta_description;
        if (! $subtitle && $product->description) {
            $subtitle = strip_tags($product->description);
        }
        if (! $subtitle) {
            $subtitle = $product->name;
        }

        $shipping = $request->get('shipping', []);
        $shippingCost = $shipping['cost'] ?? null;

        $deliveryTime = str_starts_with((string) $etiket->code, 's-')
            ? '۷ تا ۱۰ روز کاری'
            : 'ارسال سریع';

        $description = $this->buildDescriptionObject($product, $productAttributeValues);

        $result = [
            'id' => $etiket->code,
            'title' => $product->name,
            'subtitle' => $subtitle,
            'link' => $baseUrl.'/products/'.$product->slug.'?e='.$etiket->code,
            'image_link' => $imageLink,
            'availability' => $availability,
            'regular_price' => $regularPrice,
            'sale_price' => $salePrice,
            'category' => $category,
            'description' => "",
            'brand' => 'گالری طلای زرنیا',
            'delivery_time' => $deliveryTime,
        ];

        if ($shippingCost !== null) {
            $result['shipping_cost'] = (int) $shippingCost;
        }

        return $result;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\AttributeValue>  $attributeValues
     * @return array<string, string>|null
     */
    private function buildDescriptionObject(Product $product, $attributeValues): ?array
    {
        if (! empty($product->description)) {
            $decoded = json_decode($product->description, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && ! empty($decoded)) {
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

        return ! empty($description) ? $description : null;
    }

    private function isExcludedDescriptionAttribute(string $attrName): bool
    {
        return in_array(strtolower($attrName), ['brand', 'برند', 'gtin'], true);
    }
}
