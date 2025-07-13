<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Product\ProductListCollection;
use App\Http\Resources\Api\V1\Product\ProductListResouce;
use App\Models\Favorite;
use App\Models\Product;
use Illuminate\Http\Request;

class FavoriteProductController extends Controller
{
    public function addOrRemove(Product $product)
    {
        $user = auth()->user();
        $userAlreadyFavoriteThisProduct = Favorite::query()
            ->where('product_id','=',$product->id)
            ->where('user_id','=',$user->id)
            ->first();
        if($userAlreadyFavoriteThisProduct){
            $userAlreadyFavoriteThisProduct->delete();
            return response()->json([
                'status' => false,
                'message' => 'محصول از علاقه مندی های شما حذف شد'
            ]);
        }
        Favorite::create([
            'product_id' => $product->id,
            'user_id' => $user->id
        ]);
        return response()->json([
            'status' => true,
            'message' => 'محصول به علاقه مندی های شما اضافه شد'
        ]);
    }

    public function list()
    {
        $user = auth()->user();
        return new ProductListCollection($user->favoriteProducts()->paginate(),$user);
    }
}
