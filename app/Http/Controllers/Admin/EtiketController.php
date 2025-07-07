<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class EtiketController extends Controller
{
    public function getEtiketsOfProduct(Product $product)
    {
        return $product->EtiketsCodeAsArray;
    }
}
