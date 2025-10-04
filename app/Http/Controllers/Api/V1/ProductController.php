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
        $products = Product::query()
            ->main()
            ->OrderByEffectivePrice($request->price_dir)
            ->categories($request->category_ids)
            ->search($request->search)
            ->minPrice($request->minPrice)
            ->maxPrice($request->maxPrice)
            ->HasDiscount($request->hasDiscount)
            ->hasCountAndImage();
        if($request->has('random')){
            $products->inRandomOrder();
        }
        $products = $products
            ->paginate($request->get('per_page') ?? 12);
        return new ProductListCollection($products, $user);
    }

    public function show(Request $request, Product $product)
    {
        $user = $request->user('sanctum');
        return ProductItemResouce::make($product,$user);
    }

}
