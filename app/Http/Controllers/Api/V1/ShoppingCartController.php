<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ShoppingCartResource;
use App\Models\Product;
use App\Models\ShoppingCartItem;
use Illuminate\Http\Request;

class ShoppingCartController extends Controller
{
    public function plus(Product $product)
    {
        $user = auth()->user();

        // Check if product is out of stock
        if ($product->SingleCount <= 0) {
            return response()->json([
                'message' => 'This product is out of stock.'
            ], 400);
        }

        $item = ShoppingCartItem::query()
            ->firstOrCreate(
                [
                    'product_id' => $product->id,
                    'user_id' => $user->id,
                ],
                [
                    'count' => 0 // applies only on creation
                ]
            );

        // Check if adding one more exceeds stock
        if ($item->count + 1 > $product->SingleCount) {
            return response()->json([
                'message' => 'You cannot add more than available stock.'
            ], 400);
        }

        $item->increment('count');

        return ShoppingCartResource::make([], $user->shoppingCartItems);
    }


    public function minus(Product $product)
    {
        $user = auth()->user();

        $item = ShoppingCartItem::query()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->first();

        if ($item) {
            if ($item->count > 1) {
                $item->decrement('count');
            } else {
                $item->delete();
            }
        }

        return ShoppingCartResource::make([],$user->shoppingCartItems);
    }
    public function remove(Product $product)
    {
        $user = auth()->user();

        $item = ShoppingCartItem::query()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->first();

        if ($item) {
            $item->delete();
        }

        return ShoppingCartResource::make([],$user->shoppingCartItems);
    }

    public function index()
    {
        $user = auth()->user();
        return ShoppingCartResource::make([],$user->shoppingCartItems);
    }
}
