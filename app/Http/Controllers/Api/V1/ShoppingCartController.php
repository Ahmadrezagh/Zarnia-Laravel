<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ShoppingCartResource;
use App\Models\Product;
use App\Models\ShoppingCartItem;
use Illuminate\Http\Request;

class ShoppingCartController extends Controller
{
    public function plus(Request $request, $product_slug)
    {
        $user = auth()->user();

        $product = Product::findBySlug($product_slug);
        if (!$product) {
            return response()->json([
                'message' => 'محصول یافت نشد'
            ], 404);
        }
        
        // Test product bypass: check from middleware attribute
        $isTestUser = $request->attributes->get('is_test_user', false);
        $isTestProduct = ($product->slug === 'تست-3');
        $isTestScenario = ($isTestUser && $isTestProduct);
        
        // Check if product is out of stock (skip for test scenario)
        if (!$isTestScenario && $product->SingleCount <= 0) {
            return response()->json([
                'message' => 'این محصول قابل فروش نیست'
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

        // Check if adding one more exceeds stock (skip for test scenario)
        if (!$isTestScenario && $item->count + 1 > $product->SingleCount) {
            return response()->json([
                'message' => 'میزان درخواست شما بیشتر از موجودی انبار می باشد'
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
