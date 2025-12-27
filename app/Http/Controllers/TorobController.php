<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TorobController extends Controller
{
    /**
     * Get products for Torob API
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getProducts(Request $request): JsonResponse
    {
        // Optional: Add token authentication
        $token = $request->header('X-API-Token') ?? $request->input('token');
        $expectedToken = config('services.torob.api_token', env('TOROB_API_TOKEN'));
        
        if ($expectedToken && $token !== $expectedToken) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Invalid API token.'
            ], 401);
        }

        // Get pagination parameters
        $page = max(1, (int) ($request->input('page', 1)));
        $perPage = min(100, max(1, (int) ($request->input('per_page', 50)))); // Max 100 per page
        
        $baseUrl = rtrim(setting('url') ?? config('app.url'), '/');
        
        // Get attribute IDs for brand, GTIN
        $brandAttribute = Attribute::where('name', 'brand')->orWhere('name', 'برند')->first();
        $gtinAttribute = Attribute::where('name', 'GTIN')->orWhere('name', 'gtin')->first();
        
        // Collect attribute IDs
        $attributeIds = collect([$brandAttribute, $gtinAttribute])
            ->filter()
            ->pluck('id')
            ->toArray();
        
        // Get only main products (no children), with count >= 1, and with images
        $productsQuery = Product::query()
            ->main() // Only main products (parent_id is null)
            ->hasCountAndImage() // Has count >= 1 and has image
            ->with(['categories', 'etikets', 'children']); // Load children for minimum_available_price
        
        // Get total count before pagination
        $total = $productsQuery->count();
        
        // Apply pagination
        $products = $productsQuery
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();
        
        // Get all attribute values for these products in one query
        $attributeValues = AttributeValue::whereIn('product_id', $products->pluck('id'))
            ->whereIn('attribute_id', $attributeIds)
            ->get()
            ->groupBy('product_id');
        
        // Transform products to Torob format
        $items = $products->map(function ($product) use ($baseUrl, $attributeValues, $brandAttribute, $gtinAttribute) {
            // Get attribute values for this product
            $productAttributeValues = $attributeValues->get($product->id, collect())
                ->keyBy('attribute_id');
            
            // Get product image
            $imageUrl = $product->image;
            if ($imageUrl === asset('img/no_image.jpg')) {
                $imageUrl = null;
            }
            
            // Get minimum available price (minimum weight price) like single product resource
            $minimumPrice = $product->minimum_available_price ?? $product->price;
            
            // Build product data
            $productData = [
                'id' => (string) $product->id,
                'title' => $product->name,
                'link' => $baseUrl . '/products/' . $product->slug,
                'price' => (int) ($minimumPrice * 10), // Convert to integer (price is stored * 10 in DB)
                'availability' => $product->single_count > 0 ? 'in_stock' : 'out_of_stock',
            ];
            
            // Add image if available
            if ($imageUrl) {
                $productData['image'] = $imageUrl;
            }
            
            // Add original price if there's a discount (use price_without_discount_minimum_available_product)
            $priceWithoutDiscount = $product->price_without_discount_minimum_available_product ?? $product->price_without_discount;
            if ($product->discounted_price && $priceWithoutDiscount) {
                $productData['original_price'] = (int) ($priceWithoutDiscount * 10); // Convert to integer
            }
            
            // Add category
            if ($product->categories->isNotEmpty()) {
                $categoryNames = $product->categories->pluck('title')->implode(' > ');
                $productData['category'] = $categoryNames;
            }
            
            // Add description
            if ($product->description) {
                $productData['description'] = strip_tags($product->description);
            }
            
            // Add brand if available
            if ($brandAttribute && $productAttributeValues->has($brandAttribute->id)) {
                $brandValue = $productAttributeValues->get($brandAttribute->id)->value;
                if ($brandValue) {
                    $productData['brand'] = $brandValue;
                }
            }
            
            // Add GTIN if available
            if ($gtinAttribute && $productAttributeValues->has($gtinAttribute->id)) {
                $gtinValue = $productAttributeValues->get($gtinAttribute->id)->value;
                if ($gtinValue) {
                    $productData['gtin'] = $gtinValue;
                }
            }
            
            // Add stock count
            $productData['stock_count'] = $product->single_count;
            
            return $productData;
        });
        
        return response()->json([
            'success' => true,
            'data' => [
                'items' => $items,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => ceil($total / $perPage),
                ],
            ],
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

