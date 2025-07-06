<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Product\ProductItemResouce;
use App\Http\Resources\Api\V1\Product\ProductListResouce;
use App\Models\Favorite;
use App\Models\Product;

use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::query()
            ->OrderByEffectivePrice($request->price_dir)
            ->categories($request->category_ids)
            ->search($request->search)
            ->minPrice($request->minPrice)
            ->maxPrice($request->maxPrice)
            ->HasDiscount($request->hasDiscount)
            ->paginate($request->get('per_page') ?? 15);
        return ProductListResouce::collection($products);
    }

    public function show(Request $request, Product $product)
    {
        $user = $request->user('sanctum');
        return ProductItemResouce::make($product,$user);
    }

}
