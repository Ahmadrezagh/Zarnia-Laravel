<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Product\ProductFeedResource;
use App\Http\Resources\Api\V1\Product\ProductItemCollection;
use App\Http\Resources\Api\V1\Product\ProductItemResouce;
use App\Http\Resources\Api\V1\Product\ProductListCollection;
use App\Http\Resources\Api\V1\Product\ProductListResouce;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Favorite;
use App\Models\Product;
use App\Models\Shipping;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user('sanctum');
        
        // Determine sort type (support price_dir / priceDir for legacy, and sort_by)
        $sortType = null;
        if ($request->has('random')) {
            $sortType = 'random';
        } elseif ($request->filled('price_dir') || $request->filled('priceDir')) {
            $dir = $request->input('price_dir') ?? $request->input('priceDir');
            $sortType = strtolower((string) $dir) === 'asc' ? 'price_asc' : 'price_desc';
        } elseif ($request->filled('sort_by')) {
            // sort_by can be: latest, oldest, price_asc, price_desc, name_asc, name_desc, random, most_favorite
            $sortType = $request->sort_by;
        }
        
        // Get price range parameters (support both minPrice/maxPrice and from_price/to_price)
        // Handle empty strings as null
        $fromPrice = $request->filled('from_price') ? $request->from_price : ($request->filled('minPrice') ? $request->minPrice : null);
        $toPrice = $request->filled('to_price') ? $request->to_price : ($request->filled('maxPrice') ? $request->maxPrice : null);
        
        $perPage = (int) ($request->get('per_page') ?? 12);
        $page = max(1, (int) $request->get('page', 1));

        // When minPrice or maxPrice (or from_price/to_price) is present, only show products with at least one available etiket
        $priceFilterActive = $fromPrice !== null || $toPrice !== null;

        // 1) Available products (current list – unchanged)
        $availableQuery = Product::query()
            ->with('children')
            ->main()
            ->categories($request->category_ids)
            ->search($request->search)
            ->priceRange($fromPrice, $toPrice)
            ->hasCountAndImage()
            ->applyDefaultSort($sortType)
            ->HasDiscount($request->hasDiscount);
        $availableCount = $availableQuery->count();

        // 2) Unavailable products (cover image but no available etiket) – appended at the end (skip when price filter is active)
        $unavailableCount = 0;
        $unavailableQuery = null;
        if (!$priceFilterActive) {
            $unavailableQuery = Product::query()
                ->with('children')
                ->main()
                ->categories($request->category_ids)
                ->search($request->search)
                ->hasCoverImage()
                ->hasNoAvailableEtiket()
                ->applyDefaultSort($sortType);
            $unavailableCount = $unavailableQuery->count();
        }

        $total = $availableCount + $unavailableCount;
        $offset = ($page - 1) * $perPage;

        if ($priceFilterActive) {
            // Only available products: paginate the available query
            $products = $availableQuery->paginate($perPage, ['*'], 'page', $page);
            $products->appends($request->query());
        } else {
            if ($offset < $availableCount) {
                $takeAvailable = min($perPage, $availableCount - $offset);
                $availableItems = $availableQuery->skip($offset)->take($takeAvailable)->get();
                $needMore = $perPage - $availableItems->count();
                $unavailableItems = $needMore > 0
                    ? $unavailableQuery->skip(0)->take($needMore)->get()
                    : new Collection;
            } else {
                $availableItems = new Collection;
                $unavailableItems = $unavailableQuery->skip($offset - $availableCount)->take($perPage)->get();
            }
            $items = $availableItems->concat($unavailableItems);
            $products = new LengthAwarePaginator($items, $total, $perPage, $page, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);
        }

        return new ProductListCollection($products, $user);
    }

    public function categoryProducts(Request $request, Category $category)
    {
        $sortType = null;
        if ($request->has('random')) {
            $sortType = 'random';
        } elseif ($request->filled('price_dir') || $request->filled('priceDir')) {
            $dir = $request->input('price_dir') ?? $request->input('priceDir');
            $sortType = strtolower((string) $dir) === 'asc' ? 'price_asc' : 'price_desc';
        } elseif ($request->filled('sort_by')) {
            $sortType = $request->sort_by;
        }

        $perPage = (int) ($request->get('per_page') ?? 12);
        $page = max(1, (int) $request->get('page', 1));

        // 1) Available products (current list – unchanged)
        $availableQuery = Product::query()
            ->with('children')
            ->main()
            ->categories([$category->id])
            ->hasCountAndImage()
            ->applyDefaultSort($sortType);
        $availableCount = $availableQuery->count();

        // 2) Unavailable products – appended at the end
        $unavailableQuery = Product::query()
            ->with('children')
            ->main()
            ->categories([$category->id])
            ->hasCoverImage()
            ->hasNoAvailableEtiket()
            ->applyDefaultSort($sortType);
        $unavailableCount = $unavailableQuery->count();

        $total = $availableCount + $unavailableCount;
        $offset = ($page - 1) * $perPage;

        if ($offset < $availableCount) {
            $takeAvailable = min($perPage, $availableCount - $offset);
            $availableItems = $availableQuery->skip($offset)->take($takeAvailable)->get();
            $needMore = $perPage - $availableItems->count();
            $unavailableItems = $needMore > 0
                ? $unavailableQuery->skip(0)->take($needMore)->get()
                : new Collection;
        } else {
            $availableItems = new Collection;
            $unavailableItems = $unavailableQuery->skip($offset - $availableCount)->take($perPage)->get();
        }

        $items = $availableItems->concat($unavailableItems);
        $products = new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        return new ProductListCollection($products);
    }

    public function show(Request $request, Product $product)
    {
        $user = $request->user('sanctum');
        
        // Eager load children for price_range_title
        $product->load('children');
        
        return ProductItemResouce::make($product, $user);
    }

    public function relatedAndComplementary(Request $request, Product $product)
    {
        $user = $request->user('sanctum');
        
        // Get related products - filter by single_count >= 1 and has image
        $relatedProducts = $product->relatedProducts()
            ->filter(function($prod) {
                // Check if product has cover_image media and is available
                return $prod->count >= 1 && $prod->hasMedia('cover_image');
            })
            ->take(15);

        // Get complementary products - filter by single_count >= 1 and has image
        $complementaryProducts = $product->complementaryProducts()
            ->filter(function($prod) {
                // Check if product has cover_image media and is available
                return $prod->count >= 1 && $prod->hasMedia('cover_image');
            })
            ->take(15);

        return response()->json([
            'related_products' => \App\Http\Resources\Api\V1\Product\SimpleProductResource::collection($relatedProducts),
            'complementary_products' => \App\Http\Resources\Api\V1\Product\SimpleProductResource::collection($complementaryProducts),
        ]);
    }

    public function feed(Request $request)
    {
        // Get pagination parameters
        $page = max(1, (int) ($request->input('page', 1)));
        $perPage = min(100, max(1, (int) ($request->input('per_page', 50)))); // Max 100 per page
        
        // Get attribute IDs for brand, GTIN, color
        $brandAttribute = Attribute::where('name', 'brand')->orWhere('name', 'برند')->first();
        $gtinAttribute = Attribute::where('name', 'GTIN')->orWhere('name', 'gtin')->first();
        $colorAttribute = Attribute::where('name', 'color')->orWhere('name', 'رنگ')->first();
        
        // Collect attribute IDs
        $attributeIds = collect([$brandAttribute, $gtinAttribute, $colorAttribute])
            ->filter()
            ->pluck('id')
            ->toArray();
        
        // Get shipping information
        $shipping = Shipping::first();
        $shippingCost = $shipping ? $shipping->price : null;
        $deliveryTime = $shipping && $shipping->times()->exists() 
            ? $shipping->times()->first()->title ?? null 
            : null;
        
        // Get only main products (no children), with count >= 1, with images, and at least one available etiket
        $productsQuery = Product::query()
            ->main() // Only main products (parent_id is null)
            ->hasCountAndImage() // Has count >= 1 and has image
            ->whereHas('etikets', fn($q) => $q->where('is_mojood', 1)) // At least one available etiket
            ->with(['categories', 'children']); // Load children for minimum_available_price
        
        // Get total count before pagination
        $total = $productsQuery->count();
        
        // Apply pagination
        $products = $productsQuery
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();
        
        // Get all attribute values for these products in one query (via etikets)
        $attributeValues = collect();
        if (!empty($attributeIds)) {
            $etiketIds = \App\Models\Etiket::whereIn('product_id', $products->pluck('id'))
                ->orderBy('id')
                ->get(['id', 'product_id'])
                ->groupBy('product_id')
                ->map(fn($group) => $group->first()->id); // first etiket per product

            $attributeValues = AttributeValue::whereIn('etiket_id', $etiketIds->values())
                ->whereIn('attribute_id', $attributeIds)
                ->with('attribute')
                ->get()
                ->groupBy(function ($av) use ($etiketIds) {
                    // Map etiket_id back to product_id
                    return $etiketIds->search($av->etiket_id);
                });
        }
        
        // Attach attribute values to products and add shipping info to request
        $products->each(function ($product) use ($attributeValues, $shippingCost, $deliveryTime, $request) {
            $product->attributeValues = $attributeValues->get($product->id, collect());
            // Add shipping info to request for the resource
            $request->merge([
                'shipping' => [
                    'cost' => $shippingCost,
                    'time' => $deliveryTime,
                ]
            ]);
        });
        
        return response()->json([
            'success' => true,
            'data' => ProductFeedResource::collection($products),
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
            ],
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

}
