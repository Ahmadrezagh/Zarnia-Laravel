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
        return ProductSliderResource::collection(ProductSlider::all());
    }
}
