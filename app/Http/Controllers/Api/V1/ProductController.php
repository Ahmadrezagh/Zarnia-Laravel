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
        $fromPrice = $request->from_price ?? $request->minPrice ?? null;
        $toPrice = $request->to_price ?? $request->maxPrice ?? null;
        
        $products = Product::query()
            ->main()
            ->categories($request->category_ids)
            ->search($request->search)
            ->priceRange($fromPrice, $toPrice)
            ->HasDiscount($request->hasDiscount)
            ->hasCountAndImage()
            ->applyDefaultSort($sortType) // Apply sorting based on request or setting
            ->paginate($request->get('per_page') ?? 12);
            
        return new ProductListCollection($products, $user);
    }

    public function show(Request $request, Product $product)
    {
        $user = $request->user('sanctum');
        return ProductItemResouce::make($product,$user);
    }

}
