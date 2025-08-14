<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Orders\createOrderRequest;
use App\Http\Resources\Api\V1\Orders\OrderItemResource;
use App\Models\Etiket;
use App\Models\Order;
use App\Models\OrderItem;
class OrderController extends Controller
{
    public function index()
    {
        $user = auth('sanctum')->user();
        return OrderItemResource::collection($user->orders);
    }
    public function store(createOrderRequest $request)
    {
        $user = auth()->guard('sanctum')->user();
        $validated = $request->validated();

        $cartItems = $user->shoppingCartItems()->with('product')->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'message' => 'Your shopping cart is empty.'
            ], 400);
        }

        $totalAmount = 0;
        $discountPrice = 0;

        // Calculate total amount from cart
        foreach ($cartItems as $cartItem) {
            $totalAmount += $cartItem->product->price * $cartItem->count;
        }

        // Apply discount if code exists
        if (!empty($validated['discount_code'])) {
            // Example: 10% discount
            $discountPrice = intval($totalAmount * 0.10);
        }

        $finalAmount = $totalAmount - $discountPrice;

        // Create the order
        $order = Order::create([
            'user_id' => $user->id,
            'address_id' => $validated['address_id'],
            'shipping_id' => $validated['shipping_id'],
            'shipping_time_id' => $validated['shipping_time_id'] ?? null,
            'gateway_id' => $validated['gateway_id'] ?? null,
            'status' => 'pending',
            'discount_code' => $validated['discount_code'] ?? '',
            'discount_price' => $discountPrice,
            'discount_percentage' => $discountPrice > 0 ? round(($discountPrice / $totalAmount) * 100) : null,
            'total_amount' => $totalAmount,
            'final_amount' => $finalAmount,
            'note' => $validated['note'] ?? null,
        ]);

        // Create order items from cart
        foreach ($cartItems as $cartItem) {
            $etiketCode = Etiket::where('product_id', $cartItem->product_id)->value('code');

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $cartItem->product_id,
                'etiket' => $etiketCode ?? '',
                'name' => $cartItem->product->name,
                'count' => $cartItem->count,
                'price' => $cartItem->product->price,
            ]);
        }

        // Clear the shopping cart
        $user->shoppingCartItems()->delete();

        return OrderItemResource::make($order);
    }
}
