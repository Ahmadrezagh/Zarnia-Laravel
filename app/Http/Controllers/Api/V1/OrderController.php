<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Orders\createOrderRequest;
use App\Http\Resources\Api\V1\Orders\OrderItemResource;
use App\Models\Etiket;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\SMS\Kavehnegar;
use Illuminate\Http\Request;

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
            'user_agent' => $validated['user_agent'] ?? null,
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
//        $user->shoppingCartItems()->delete();
        $order_url = $order->gateway->createSnappTransaction($order);
        return OrderItemResource::make(Order::find($order->id),$order_url['response']['paymentPageUrl']);
    }

    public function status(Order $order)
    {
        return $order->status();
    }
    public function cancel(Order $order)
    {
        $response = $order->cancel();
        $order->update(['status' => Order::$STATUSES[3]]);
        $sms = new Kavehnegar();
        $sms->send_with_two_token($order->address->receiver_phone,$order->address->receiver_name,$order->id,$order->status);
        return $response;
    }
    public function settle(Order $order)
    {
        return $order->settle();
    }
    public function updateSnappTransaction(Request $request, Order $order)
    {
        return $order->updateSnappTransaction();
    }
}
