<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PorductSlider\ProductSliderResource;
use App\Models\Product;
use App\Models\ProductSlider;
use Illuminate\Http\Request;

class ProductSliderController extends Controller
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
            ->hasCountAndImage()
            ->paginate($request->get('per_page') ?? 12);
        return ProductSliderResource::collection(ProductSlider::all());
    }
}
