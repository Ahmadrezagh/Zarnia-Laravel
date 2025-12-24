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
use Illuminate\Support\Facades\Cache;

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
                'message' => 'سبد خرید خالی می باشد'
            ], 400);
        }

        // Check all products for availability and remove unavailable items
        $unavailableProducts = [];
        $availableCartItems = collect();
        
        foreach ($cartItems as $cartItem) {
            // Check if product is available (has at least one available etiket)
            // Skip check if orderable_after_out_of_stock is true
            $isOrderableAfterOutOfStock = $cartItem->product->orderable_after_out_of_stock ?? false;
            
            if (!$isOrderableAfterOutOfStock && $cartItem->product->single_count < 1) {
                // Product is not available, remove from cart and add to error list
                $unavailableProducts[] = $cartItem->product->name;
                $cartItem->delete();
            } else {
                $availableCartItems->push($cartItem);
            }
        }

        // If any products were unavailable, return error
        if (!empty($unavailableProducts)) {
            $errorMessages = array_map(function ($productName) {
                return "محصول {$productName} موجود نمی باشد";
            }, $unavailableProducts);
            
            return response()->json([
                'message' => implode('. ', $errorMessages)
            ], 400);
        }

        // Check if cart is empty after removing unavailable items
        if ($availableCartItems->isEmpty()) {
            return response()->json([
                'message' => 'Your shopping cart is empty.'
            ], 400);
        }

        // Update cartItems to only include available items
        $cartItems = $availableCartItems;

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

        // Check availability after cache filtering (reserved etikets)
        // This ensures products have unreserved etikets available
        // Skip check if orderable_after_out_of_stock is true
        $unavailableProductsAfterCache = [];
        foreach ($cartItems as $cartItem) {
            $isOrderableAfterOutOfStock = $cartItem->product->orderable_after_out_of_stock ?? false;
            
            // Skip availability check if product is orderable after out of stock
            if ($isOrderableAfterOutOfStock) {
                continue;
            }
            
            // Get all available etikets for this product
            $availableEtikets = Etiket::where('product_id', $cartItem->product_id)
                ->where('is_mojood', '=', '1')
                ->get();
            
            // Check if there's at least one unreserved etiket
            $hasUnreservedEtiket = false;
            foreach ($availableEtikets as $availableEtiket) {
                $cacheKey = 'reserved_etiket_' . $availableEtiket->code;
                // If etiket is not in cache (not reserved), product is available
                if (!Cache::has($cacheKey)) {
                    $hasUnreservedEtiket = true;
                    break;
                }
            }
            
            // If no unreserved etiket found, add to unavailable list
            if (!$hasUnreservedEtiket) {
                $unavailableProductsAfterCache[] = $cartItem->product->name;
            }
        }

        // If any products are unavailable after cache check, return error
        if (!empty($unavailableProductsAfterCache)) {
            $errorMessages = array_map(function ($productName) {
                return "محصول {$productName} موجود نمی باشد";
            }, $unavailableProductsAfterCache);
            
            return response()->json([
                'message' => implode('. ', $errorMessages)
            ], 400);
        }

        // Calculate gold price
        $gold_price = number_format(get_gold_price()/10);

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
            'gold_price' => $gold_price,
            'reference' => $validated['reference'] ?? null,
        ]);

        // Create order items from cart and collect reserved etiket codes
        $reservedEtiketCodes = [];
        
        foreach ($cartItems as $cartItem) {
            $isOrderableAfterOutOfStock = $cartItem->product->orderable_after_out_of_stock ?? false;
            $etiketCode = null;
            
            // Only try to get etiket if product is not orderable after out of stock
            if (!$isOrderableAfterOutOfStock) {
                // Get all available etikets for this product
                $availableEtikets = Etiket::where('product_id', $cartItem->product_id)
                    ->where('is_mojood', '=', '1')
                    ->get();
                
                // Filter out etikets that are currently reserved (cached)
                $unreservedEtiket = null;
                foreach ($availableEtikets as $availableEtiket) {
                    $cacheKey = 'reserved_etiket_' . $availableEtiket->code;
                    // If etiket is not in cache (not reserved), use it
                    if (!Cache::has($cacheKey)) {
                        $unreservedEtiket = $availableEtiket;
                        break;
                    }
                }
                
                $etiketCode = $unreservedEtiket ? $unreservedEtiket->code : null;
            }

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $cartItem->product_id,
                'etiket' => $etiketCode ?? '',
                'name' => $cartItem->product->name,
                'count' => $cartItem->count,
                'price' => $cartItem->product->price,
            ]);
            
            // Collect etiket codes that are being reserved (only if we have an etiket)
            if ($etiketCode) {
                $reservedEtiketCodes[] = $etiketCode;
            }
        }

        // Cache reserved etiket codes for 32 minutes (1920 seconds)
        // This reserves them until gateway response comes (success or failed)
        // During this time, when checking availability, these etikets will return is_mojood = 0
        foreach ($reservedEtiketCodes as $etiketCode) {
            $cacheKey = 'reserved_etiket_' . $etiketCode;
            Cache::put($cacheKey, true, 1920); // 32 minutes = 1920 seconds
        }

        $order_url = null;
        
        if($order->gateway) {
            // Note: Shopping cart will be cleared when order is verified/paid
            $transactionResult = $order->gateway->createTransaction($order);
            
            if ($transactionResult && isset($transactionResult['response']['paymentPageUrl'])) {
                $order_url = $transactionResult['response']['paymentPageUrl'];
            }
        }
        
        return OrderResource::make(Order::find($order->id), $order_url);
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
        $sms->send_with_two_token($order->user->phone, $order->user->name, $order->id, $order->status);
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
