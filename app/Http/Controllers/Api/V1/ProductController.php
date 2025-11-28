<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Product\ProductItemCollection;
use App\Http\Resources\Api\V1\Product\ProductItemResouce;
use App\Http\Resources\Api\V1\Product\ProductListCollection;
use App\Http\Resources\Api\V1\Product\ProductListResouce;
use App\Models\Category;
use App\Models\Favorite;
use App\Models\Product;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user('sanctum');
        
        // Determine sort type
        $sortType = null;
        if ($request->has('random')) {
            $sortType = 'random';
        } elseif ($request->price_dir) {
            // Legacy support: map price_dir to new sort types
            $sortType = $request->price_dir === 'asc' ? 'price_asc' : 'price_desc';
        } elseif ($request->sort_by) {
            // New parameter: sort_by can be: latest, oldest, price_asc, price_desc, name_asc, name_desc, random, most_favorite
            $sortType = $request->sort_by;
        }
        
        // Get price range parameters (support both minPrice/maxPrice and from_price/to_price)
        // Handle empty strings as null
        $fromPrice = $request->filled('from_price') ? $request->from_price : ($request->filled('minPrice') ? $request->minPrice : null);
        $toPrice = $request->filled('to_price') ? $request->to_price : ($request->filled('maxPrice') ? $request->maxPrice : null);
        
        // Create unique cache key based on all parameters
        $cacheKey = 'products_index_' . md5(json_encode([
            'category_ids' => $request->category_ids,
            'search' => $request->search,
            'from_price' => $fromPrice,
            'to_price' => $toPrice,
            'sort_type' => $sortType,
            'has_discount' => $request->hasDiscount,
            'per_page' => $request->get('per_page', 12),
            'page' => $request->get('page', 1),
            'user_id' => $user?->id,
        ]));
        
        return Cache::remember($cacheKey, 1200, function () use ($request, $user, $sortType, $fromPrice, $toPrice) {
            $products = Product::query()
                ->with('children') // Eager load children for price_range_title
                ->main()
                ->categories($request->category_ids)
                ->search($request->search)
                ->priceRange($fromPrice, $toPrice)
                ->hasCountAndImage()
                ->applyDefaultSort($sortType)
                ->HasDiscount($request->hasDiscount)
                ->paginate($request->get('per_page') ?? 12);
                
            return new ProductListCollection($products, $user);
        });
    }

    public function categoryProducts(Request $request, Category $category)
    {
        $user = $request->user('sanctum');

        $sortType = null;
        if ($request->has('random')) {
            $sortType = 'random';
        } elseif ($request->price_dir) {
            $sortType = $request->price_dir === 'asc' ? 'price_asc' : 'price_desc';
        } elseif ($request->sort_by) {
            $sortType = $request->sort_by;
        }

        $fromPrice = $request->filled('from_price') ? $request->from_price : ($request->filled('minPrice') ? $request->minPrice : null);
        $toPrice = $request->filled('to_price') ? $request->to_price : ($request->filled('maxPrice') ? $request->maxPrice : null);

        $categoryIds = collect((array) $request->category_ids)
            ->filter()
            ->push($category->id)
            ->unique()
            ->all();

        // Create unique cache key based on all parameters
        $cacheKey = 'products_category_' . $category->id . '_' . md5(json_encode([
            'category_ids' => $categoryIds,
            'search' => $request->search,
            'from_price' => $fromPrice,
            'to_price' => $toPrice,
            'sort_type' => $sortType,
            'has_discount' => $request->hasDiscount,
            'per_page' => $request->get('per_page', 12),
            'page' => $request->get('page', 1),
            'user_id' => $user?->id,
        ]));

        return Cache::remember($cacheKey, 1200, function () use ($request, $user, $categoryIds, $sortType, $fromPrice, $toPrice) {
            $products = Product::query()
                ->with('children')
                ->main()
                ->categories($categoryIds)
                ->search($request->search)
                ->priceRange($fromPrice, $toPrice)
                ->hasCountAndImage()
                ->applyDefaultSort($sortType)
                ->HasDiscount($request->hasDiscount)
                ->paginate($request->get('per_page') ?? 12);

            return new ProductItemCollection($products, $user);
        });
    }

    public function show(Request $request, Product $product)
    {
        $user = $request->user('sanctum');
        
        // Create unique cache key based on product ID and user ID
        $cacheKey = 'product_show_' . $product->id . '_user_' . ($user?->id ?? 'guest');
        
        return Cache::remember($cacheKey, 1200, function () use ($product, $user) {
            // Eager load children for price_range_title
            $product->load('children');
            
            return ProductItemResouce::make($product, $user);
        });
    }

    public function relatedAndComplementary(Request $request, Product $product)
    {
        $user = $request->user('sanctum');
        
        // Create unique cache key based on product ID and user ID
        $cacheKey = 'product_related_complementary_' . $product->id . '_user_' . ($user?->id ?? 'guest');
        
        return Cache::remember($cacheKey, 1200, function () use ($product, $user) {
            // Get related products - filter by single_count >= 1 and has image
            $relatedProducts = $product->relatedProducts()
                ->filter(function($prod) {
                    // Check if product has cover_image media and is available
                    return $prod->single_count >= 1 && $prod->hasMedia('cover_image');
                })
                ->take(15);

            // Get complementary products - filter by single_count >= 1 and has image
            $complementaryProducts = $product->complementaryProducts()
                ->filter(function($prod) {
                    // Check if product has cover_image media and is available
                    return $prod->single_count >= 1 && $prod->hasMedia('cover_image');
                })
                ->take(15);

            return response()->json([
                'related_products' => \App\Http\Resources\Api\V1\Product\SimpleProductResource::collection($relatedProducts),
                'complementary_products' => \App\Http\Resources\Api\V1\Product\SimpleProductResource::collection($complementaryProducts),
            ]);
        });
    }

}
