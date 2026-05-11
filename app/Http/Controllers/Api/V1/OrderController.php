<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Orders\createOrderRequest;
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

        $cartItems = $user->shoppingCartItems()->with(['product', 'etiketItem'])->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'message' => 'سبد خرید خالی می باشد',
            ], 400);
        }

        // Check all cart items for etiket availability
        $unavailableProducts = [];
        $availableCartItems = collect();

        foreach ($cartItems as $cartItem) {
            // Check if cart item has an etiket assigned
            if (! $cartItem->etiket_id || ! $cartItem->etiketItem) {
                $unavailableProducts[] = $cartItem->product->name.' (اتیکت انتخاب نشده)';
                $cartItem->delete();

                continue;
            }

            // Check if the selected etiket is available
            $etiket = $cartItem->etiketItem;
            $isOrderableAfterOutOfStock = $etiket->orderable_after_out_of_stock ?? false;
            $cacheKey = 'reserved_etiket_'.$etiket->code;
            $reservedByUserId = Cache::get($cacheKey);
            $isReserved = $reservedByUserId !== null;

            // Skip availability check if etiket is orderable after out of stock
            if ($isOrderableAfterOutOfStock) {
                $availableCartItems->push($cartItem);

                continue;
            }

            // Etiket must be available (is_mojood). If reserved, only the user who reserved it can purchase.
            $canPurchaseReserved = $isReserved && $reservedByUserId === $user->id;
            if ($etiket->is_mojood != 1 || ($isReserved && ! $canPurchaseReserved)) {
                $unavailableProducts[] = $cartItem->product->name.' (اتیکت انتخاب شده موجود نیست)';
                $cartItem->delete();

                continue;
            }

            $availableCartItems->push($cartItem);
        }

        // If any products were unavailable, return error
        if (! empty($unavailableProducts)) {
            $errorMessages = array_map(function ($productName) {
                return "محصول {$productName} موجود نمی باشد";
            }, $unavailableProducts);

            return response()->json([
                'message' => implode('. ', $errorMessages),
            ], 400);
        }

        // Check if cart is empty after removing unavailable items
        if ($availableCartItems->isEmpty()) {
            return response()->json([
                'message' => 'Your shopping cart is empty.',
            ], 400);
        }

        // Update cartItems to only include available items
        $cartItems = $availableCartItems;

        $cartEtiketIds = $cartItems->pluck('etiket_id')->unique()->filter()->values();

        $hasComprehensiveEtiket = $cartEtiketIds->isNotEmpty()
            && Etiket::query()
                ->whereIn('code', $cartEtiketIds)
                ->where('code', 'like','s-%')
                ->exists();

        $totalAmount = 0;
        $discountPrice = 0;

        // Calculate total amount from cart
        foreach ($cartItems as $cartItem) {
            // Use etiket price if available, otherwise fallback to product's lowest etiket price
            $itemPrice = $cartItem->etiketItem ? ($cartItem->etiketItem->price / 10) : 0;
            $totalAmount += $itemPrice * $cartItem->count;
        }
        $discountPercentage = 0;
        // Apply discount if code exists
        if (! empty($validated['discount_code'])) {
            $discount = Discount::verify($validated['discount_code'], $totalAmount, $user->id);
            if ($discount['valid']) {
                $discount = Discount::query()->where('code', $validated['discount_code'])->first();
                if ($discount->amount) {
                    $discountPrice = $discount->amount;
                } elseif ($discount->percentage) {
                    $discountPrice = ($discount->percentage / 100) * $totalAmount;
                    $discountPercentage = $discount->percentage;
                }
            }
        }
        $shipping_price = 0;
        $shipping = Shipping::query()->where('id', $validated['shipping_id'])->first();
        if ($shipping && $shipping->price) {
            $shipping_price = $shipping->price;
        }
        $finalAmount = ($totalAmount + $shipping_price) - $discountPrice;

        // Note: Availability check is already done above for each cart item's specific etiket

        // Calculate gold price
        $gold_price = number_format(get_gold_price() / 10);

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
            'shipping_date' => $validated['shipping_date'] ?? null,
            'has_comprehensive_etiket' => $hasComprehensiveEtiket,
        ]);

        // Create order items from cart and collect reserved etiket codes
        $reservedEtiketCodes = [];

        foreach ($cartItems as $cartItem) {
            $etiket = $cartItem->etiket_id ? $cartItem->etiketItem : null;

            // If etiket is comprehensive, expand it into its related etikets
            if ($etiket && $etiket->type === 'comprehensive') {
                $links = $etiket->relationLoaded('comprehensiveEtikets')
                    ? $etiket->comprehensiveEtikets
                    : $etiket->comprehensiveEtikets()->with('relatedEtiket.product')->get();

                foreach ($links as $link) {
                    $related = $link->relatedEtiket;
                    if (! $related) {
                        continue;
                    }

                    $relatedProduct = $related->relationLoaded('product')
                        ? $related->product
                        : $related->product()->first();

                    $itemPrice = $related->price ? ($related->price / 10) : 0;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $relatedProduct ? $relatedProduct->id : $cartItem->product_id,
                        'etiket' => $related->code,
                        'name' => $relatedProduct ? $relatedProduct->name : $cartItem->product->name,
                        'count' => $cartItem->count,
                        'price' => $itemPrice,
                    ]);

                    // Reserve each real etiket that participates in the comprehensive etiket
                    $reservedEtiketCodes[] = $related->code;
                }

                // Skip normal real-etiket handling for comprehensive etikets
                continue;
            }

            $etiketCode = null;
            $isOrderableAfterOutOfStock = false;

            // Use the etiket from the cart item if available
            if ($etiket) {
                $etiketCode = $etiket->code;
                $isOrderableAfterOutOfStock = $etiket->orderable_after_out_of_stock ?? false;
            }

            // Use etiket price if available, otherwise fallback to product's lowest etiket price
            $itemPrice = $etiket ? ($etiket->price / 10) : 0;

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $cartItem->product_id,
                'etiket' => $etiketCode ?? '',
                'name' => $cartItem->product->name,
                'count' => $cartItem->count,
                'price' => $itemPrice,
            ]);

            // Collect etiket codes that are being reserved (only if we have an etiket)
            if ($etiketCode) {
                $reservedEtiketCodes[] = $etiketCode;
            }
        }

        $order->refreshInvoiceSnapshot();

        // Cache reserved etiket codes for 32 minutes (1920 seconds), store reserving user id
        // Only the user who reserved can purchase; do not overwrite another user's reservation
        foreach ($reservedEtiketCodes as $etiketCode) {
            $cacheKey = 'reserved_etiket_'.$etiketCode;
            $existing = Cache::get($cacheKey);
            if ($existing === null || $existing === $user->id || $existing === true) {
                Cache::put($cacheKey, $user->id, 1920);
            }
        }

        $order_url = null;

        if ($order->gateway) {
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
        $order->restoreOrderEtiketsAvailability();
        $sms = new Kavehnegar;
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

        if (! empty($orderItemIds)) {
            // Delete all order items of this order where id is not in the given list
            $order->orderItems()
                ->whereNotIn('id', $orderItemIds)
                ->delete();
        }

        return $order->updateSnappTransaction();
    }

    public function eligible($price)
    {
        $snapp = new SnappPayGateway;

        return $snapp->eligible($price * 10);
    }
}
