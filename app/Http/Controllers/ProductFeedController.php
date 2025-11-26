<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\Shipping;
use Illuminate\Http\Response;

class ProductFeedController extends Controller
{
    public function index(): Response
    {
        $baseUrl = rtrim(setting('url') ?? config('app.url'), '/');
        
        // Get attribute IDs for brand, GTIN, color, size
        $brandAttribute = Attribute::where('name', 'brand')->orWhere('name', 'برند')->first();
        $gtinAttribute = Attribute::where('name', 'GTIN')->orWhere('name', 'gtin')->first();
        $colorAttribute = Attribute::where('name', 'color')->orWhere('name', 'رنگ')->first();
        $sizeAttribute = Attribute::where('name', 'size')->orWhere('name', 'سایز')->first();

        // Collect attribute IDs
        $attributeIds = collect([$brandAttribute, $gtinAttribute, $colorAttribute, $sizeAttribute])
            ->filter()
            ->pluck('id')
            ->toArray();

        // Get only main products (no children), with count >= 1, and with images
        $products = Product::query()
            ->main() // Only main products (parent_id is null)
            ->hasCountAndImage() // Has count >= 1 and has image
            ->with(['categories', 'etikets'])
            ->get();

        // Get all attribute values for these products in one query
        $attributeValues = AttributeValue::whereIn('product_id', $products->pluck('id'))
            ->whereIn('attribute_id', $attributeIds)
            ->get()
            ->groupBy('product_id');

        // Get shipping information
        $shipping = Shipping::first(); // Get first shipping method, or you can modify this logic
        $shippingCost = $shipping ? $shipping->price : null;
        $deliveryTime = $shipping && $shipping->times()->exists() 
            ? $shipping->times()->first()->title ?? null 
            : null;

        // Generate XML
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0">' . "\n";
        $xml .= '  <channel>' . "\n";
        $xml .= '    <title>' . htmlspecialchars(setting('site_name') ?? 'Product Feed', ENT_XML1, 'UTF-8') . '</title>' . "\n";
        $xml .= '    <link>' . htmlspecialchars($baseUrl, ENT_XML1, 'UTF-8') . '</link>' . "\n";
        $xml .= '    <description>Product Feed</description>' . "\n";
        $xml .= '    <lastBuildDate>' . now()->toRssString() . '</lastBuildDate>' . "\n";

        foreach ($products as $product) {
            $xml .= '    <item>' . "\n";
            
            // Get attribute values for this product
            $productAttributeValues = $attributeValues->get($product->id, collect())
                ->keyBy('attribute_id');
            
            // Required fields
            $xml .= '      <id>' . htmlspecialchars($product->id, ENT_XML1, 'UTF-8') . '</id>' . "\n";
            $xml .= '      <title>' . htmlspecialchars($product->name, ENT_XML1, 'UTF-8') . '</title>' . "\n";
            
            // Subtitle (using meta_description or description if available)
            $subtitle = $product->meta_description ?? $product->description ?? '';
            if ($subtitle) {
                $xml .= '      <subtitle>' . htmlspecialchars(strip_tags($subtitle), ENT_XML1, 'UTF-8') . '</subtitle>' . "\n";
            }
            
            // Link
            $productLink = $baseUrl . '/products/' . $product->slug;
            $xml .= '      <link>' . htmlspecialchars($productLink, ENT_XML1, 'UTF-8') . '</link>' . "\n";
            
            // Image link
            $imageUrl = $product->image;
            if ($imageUrl && $imageUrl !== asset('img/no_image.jpg')) {
                $xml .= '      <image_link>' . htmlspecialchars($imageUrl, ENT_XML1, 'UTF-8') . '</image_link>' . "\n";
            }
            
            // Availability
            $isAvailable = $product->single_count > 0;
            $availability = $isAvailable ? 'in stock' : 'out of stock';
            $xml .= '      <availability>' . htmlspecialchars($availability, ENT_XML1, 'UTF-8') . '</availability>' . "\n";
            
            // Regular price (original price without discount)
            $regularPrice = $product->original_price ?? 0;
            if ($regularPrice > 0) {
                $xml .= '      <regular_price>' . htmlspecialchars(number_format($regularPrice, 0, '.', ''), ENT_XML1, 'UTF-8') . '</regular_price>' . "\n";
            }
            
            // Sale price (price after discount) - only include if there's a discount
            $salePrice = $product->price;
            if ($salePrice > 0 && $regularPrice > 0 && $product->discounted_price && $salePrice < $regularPrice) {
                $xml .= '      <sale_price>' . htmlspecialchars(number_format($salePrice, 0, '.', ''), ENT_XML1, 'UTF-8') . '</sale_price>' . "\n";
            }
            
            // Category
            if ($product->categories->isNotEmpty()) {
                $categoryNames = $product->categories->pluck('title')->implode(' > ');
                $xml .= '      <category>' . htmlspecialchars($categoryNames, ENT_XML1, 'UTF-8') . '</category>' . "\n";
            }
            
            // Description as JSON object
            $description = $this->formatDescription($product->description);
            if ($description) {
                $xml .= '      <description>' . htmlspecialchars($description, ENT_XML1, 'UTF-8') . '</description>' . "\n";
            }
            
            // Brand
            if ($brandAttribute && $productAttributeValues->has($brandAttribute->id)) {
                $brandValue = $productAttributeValues->get($brandAttribute->id)->value;
                if ($brandValue) {
                    $xml .= '      <brand>' . htmlspecialchars($brandValue, ENT_XML1, 'UTF-8') . '</brand>' . "\n";
                }
            }
            
            // GTIN
            if ($gtinAttribute && $productAttributeValues->has($gtinAttribute->id)) {
                $gtinValue = $productAttributeValues->get($gtinAttribute->id)->value;
                if ($gtinValue) {
                    $xml .= '      <GTIN>' . htmlspecialchars($gtinValue, ENT_XML1, 'UTF-8') . '</GTIN>' . "\n";
                }
            }
            
            // Optional fields
            // Color
            if ($colorAttribute && $productAttributeValues->has($colorAttribute->id)) {
                $colorValue = $productAttributeValues->get($colorAttribute->id)->value;
                if ($colorValue) {
                    $xml .= '      <color>' . htmlspecialchars($colorValue, ENT_XML1, 'UTF-8') . '</color>' . "\n";
                }
            }
            
            // Size
            if ($sizeAttribute && $productAttributeValues->has($sizeAttribute->id)) {
                $sizeValue = $productAttributeValues->get($sizeAttribute->id)->value;
                if ($sizeValue) {
                    $xml .= '      <size>' . htmlspecialchars($sizeValue, ENT_XML1, 'UTF-8') . '</size>' . "\n";
                }
            }
            
            // Shipping cost
            if ($shippingCost !== null) {
                $xml .= '      <shipping_cost>' . htmlspecialchars(number_format($shippingCost, 0, '.', ''), ENT_XML1, 'UTF-8') . '</shipping_cost>' . "\n";
            }
            
            // Delivery time
            if ($deliveryTime) {
                $xml .= '      <delivery_time>' . htmlspecialchars($deliveryTime, ENT_XML1, 'UTF-8') . '</delivery_time>' . "\n";
            }
            
            $xml .= '    </item>' . "\n";
        }

        $xml .= '  </channel>' . "\n";
        $xml .= '</rss>';

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }

    /**
     * Format description as JSON object
     * If description is already JSON, return it as is
     * Otherwise, create a JSON object with the description
     */
    private function formatDescription($description): ?string
    {
        if (empty($description)) {
            return null;
        }

        // Try to decode as JSON first
        $decoded = json_decode($description, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            // Already valid JSON, return as formatted JSON string
            return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        // If not JSON, create a simple object with the description
        $descriptionObject = [
            'description' => strip_tags($description)
        ];

        return json_encode($descriptionObject, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

