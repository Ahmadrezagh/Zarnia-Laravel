<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Product\ProductItemResouce;
use App\Http\Resources\Api\V1\Product\ProductListCollection;
use App\Http\Resources\Api\V1\Product\ProductListResouce;
use App\Models\Favorite;
use App\Models\Product;

use Illuminate\Http\Request;

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
            // New parameter: sort_by can be: latest, oldest, price_asc, price_desc, name_asc, name_desc, random
            $sortType = $request->sort_by;
        }
        
        // Get price range parameters (support both minPrice/maxPrice and from_price/to_price)
        // Handle empty strings as null
        $fromPrice = $request->filled('from_price') ? $request->from_price : ($request->filled('minPrice') ? $request->minPrice : null);
        $toPrice = $request->filled('to_price') ? $request->to_price : ($request->filled('maxPrice') ? $request->maxPrice : null);
        
        // Debug: Step-by-step count to identify which filter is reducing results
        $baseQuery = Product::query()->main();
        \Log::info('After main(): ' . $baseQuery->count());
        
        $baseQuery->categories($request->category_ids);
        \Log::info('After categories: ' . $baseQuery->count());
        
        $baseQuery->search($request->search);
        \Log::info('After search: ' . $baseQuery->count());
        
        $baseQuery->priceRange($fromPrice, $toPrice);
        \Log::info('After priceRange: ' . $baseQuery->count() . ' (from=' . ($fromPrice ?? 'null') . ', to=' . ($toPrice ?? 'null') . ')');
        
        $baseQuery->HasDiscount($request->hasDiscount);
        \Log::info('After HasDiscount: ' . $baseQuery->count());
        
        $baseQuery->hasCountAndImage();
        \Log::info('After hasCountAndImage: ' . $baseQuery->count());
        
        \Log::info('Request params:', $request->all());
        
        $products = Product::query()
            ->with('children') // Eager load children for price_range_title
            ->main()
            ->categories($request->category_ids)
            ->search($request->search)
            ->priceRange($fromPrice, $toPrice)
            ->HasDiscount($request->hasDiscount)
            ->hasCountAndImage()
            ->applyDefaultSort($sortType)
            ->paginate($request->get('per_page') ?? 12);
            
        return new ProductListCollection($products, $user);
    }

    public function show(Request $request, Product $product)
    {
        $user = $request->user('sanctum');
        
        // Eager load children for price_range_title
        $product->load('children');
        
        return ProductItemResouce::make($product,$user);
    }

}
