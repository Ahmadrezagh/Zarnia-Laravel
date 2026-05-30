<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ShoppingCartResource;
use App\Models\Etiket;
use App\Models\Product;
use App\Models\ShoppingCartItem;

class ShoppingCartController extends Controller
{
    public function plus($etiket_code)
    {
        $user = auth()->user();

        // Find etiket by code
        $etiket = Etiket::where('code', $etiket_code)->first();

        if (! $etiket) {
            return response()->json([
                'message' => 'اتیکت یافت نشد',
            ], 404);
        }

        // Get product from etiket
        $product = $etiket->product;
        if (! $product) {
            return response()->json([
                'message' => 'محصول مرتبط با این اتیکت یافت نشد',
            ], 404);
        }

        // Check if this etiket is already in user's cart
        $existingCartItem = ShoppingCartItem::query()
            ->where('user_id', $user->id)
            ->where('etiket_id', $etiket->id)
            ->first();

        if ($existingCartItem) {
            return response()->json([
                'message' => 'این اتیکت قبلاً به سبد خرید اضافه شده است',
            ], 400);
        }

        if (! $etiket->isAvailableForUser($user->id)) {
            return response()->json([
                'message' => 'این اتیکت موجود نیست',
            ], 400);
        }

        // Create a new cart item for this etiket
        ShoppingCartItem::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'etiket_id' => $etiket->id,
            'count' => 1,
        ]);

        return ShoppingCartResource::make([], $user->shoppingCartItems()->with('etiketItem')->get());
    }

    public function remove($id)
    {
        $user = auth()->user();

        // Remove by cart item ID
        $item = ShoppingCartItem::query()
            ->where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if ($item) {
            $item->delete();
        }

        return ShoppingCartResource::make([], $user->shoppingCartItems()->with('etiketItem')->get());
    }

    public function index()
    {
        $user = auth()->user();

        return ShoppingCartResource::make([], $user->shoppingCartItems()->with('etiketItem')->get());
    }
}
