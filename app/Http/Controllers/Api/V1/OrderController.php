<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Orders\createOrderRequest;
use App\Http\Resources\Api\V1\Orders\OrderItemResource;
use App\Http\Resources\Api\V1\Orders\OrderResource;
use App\Models\Discount;
use App\Models\Etiket;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shipping;
use App\Services\PaymentGateways\SnappPayGateway;
use App\Services\SMS\Kavehnegar;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        $user = auth('sanctum')->user();
        return OrderResource::collection($user->orders);
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
        $discountPercentage = 0;
        // Apply discount if code exists
        if (!empty($validated['discount_code'])) {
            $discount = Discount::verify($validated['discount_code'], $totalAmount, $user->id);
            if ($discount['valid']) {
                $discount = Discount::query()->where('code',$validated['discount_code'])->first();
                if($discount->amount){
                    $discountPrice = $discount->amount;
                }elseif($discount->percentage){
                    $discountPrice = ($discount->percentage/100) * $totalAmount;
                    $discountPercentage = $discount->percentage;
                }
            }
        }
        $shipping_price = 0;
        $shipping = Shipping::query()->where('id',$validated['shipping_id'])->first();
        if($shipping && $shipping->price){
            $shipping_price = $shipping->price;
        }
        $finalAmount = ($totalAmount + $shipping_price) - $discountPrice;

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
            'discount_percentage' => $discountPercentage,
            'total_amount' => $totalAmount,
            'final_amount' => $finalAmount,
            'note' => $validated['note'] ?? null,
            'user_agent' => $validated['user_agent'] ?? null,
            'shipping_price' => $shipping_price,
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
        $order_url = $order->gateway->createSnappTransaction($order);
        return $order_url;
        return OrderResource::make(Order::find($order->id),$order_url['response']['paymentPageUrl']);
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
        $orderItemIds = $request->input('order_item_ids', []);

        if (!empty($orderItemIds)) {
            // Delete all order items of this order where id is not in the given list
            $order->orderItems()
                ->whereNotIn('id', $orderItemIds)
                ->delete();
        }
        return $order->updateSnappTransaction();
    }

    public function eligible($price)
    {
        $snapp = new SnappPayGateway();
        return $snapp->eligible($price * 10);
    }
}
