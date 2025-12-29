<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ShoppingCartResource;
use App\Models\Etiket;
use App\Models\Product;
use App\Models\ShoppingCartItem;
use Illuminate\Http\Request;

class ShoppingCartController extends Controller
{
    public function plus($product_slug)
    {
        $user = auth()->user();

        $product = Product::where('slug', $product_slug)->first();
        if (!$product) {
            return response()->json([
                'message' => 'محصول یافت نشد'
            ], 404);
        }

        // Get all etiket IDs already in user's cart for this product
        $cartEtiketIds = ShoppingCartItem::query()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->whereNotNull('etiket_id')
            ->pluck('etiket_id')
            ->toArray();

        // Find an available etiket that is not already in cart
        // First check direct etikets, then check children's etikets
        // Include etikets that are orderable_after_out_of_stock even if is_mojood = 0
        $availableEtiket = Etiket::query()
            ->where('product_id', $product->id)
            ->where(function($query) {
                $query->where('is_mojood', 1)
                      ->orWhere('orderable_after_out_of_stock', 1);
            })
            ->whereNotIn('id', $cartEtiketIds)
            ->first();

        // If no direct etiket found and product has children, check children's etikets
        if (!$availableEtiket && $product->children()->exists()) {
            $childrenProductIds = $product->children()->pluck('id')->toArray();
            $availableEtiket = Etiket::query()
                ->whereIn('product_id', $childrenProductIds)
                ->where(function($query) {
                    $query->where('is_mojood', 1)
                          ->orWhere('orderable_after_out_of_stock', 1);
                })
                ->whereNotIn('id', $cartEtiketIds)
                ->first();
        }

        // Check if product is out of stock (unless we found an orderable_after_out_of_stock etiket)
        if (!$availableEtiket) {
            return response()->json([
                'message' => 'هیچ اتیکت موجودی برای این محصول یافت نشد'
            ], 400);
        }
        
        // If product is out of stock and etiket is not orderable_after_out_of_stock, check availability
        if ($product->SingleCount <= 0 && !($availableEtiket->orderable_after_out_of_stock ?? false)) {
            return response()->json([
                'message' => 'این محصول قابل فروش نیست'
            ], 400);
        }

        // Create a new cart item for this etiket (each etiket gets a separate row)
        ShoppingCartItem::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'etiket_id' => $availableEtiket->id,
            'count' => 1
        ]);

        return ShoppingCartResource::make([], $user->shoppingCartItems()->with('etiket')->get());
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

        return ShoppingCartResource::make([], $user->shoppingCartItems()->with('etiket')->get());
    }

    public function index()
    {
        $user = auth()->user();
        return ShoppingCartResource::make([], $user->shoppingCartItems()->with('etiket')->get());
    }
}
