<?php

namespace App\Models;

use App\Services\PaymentGateways\SnappPayGateway;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Gateway extends Model implements HasMedia
{
    use InteractsWithMedia;

    use InteractsWithMedia;
    protected $fillable = [
        'title',
        'sub_title',
        'color',
        'key'
    ];

    public function getImageAttribute()
    {
        $image = $this->getFirstMediaUrl('image');
        return $image != "" ? $image : asset('img/no_image.jpg');
    }

    public function createTransaction($order)
    {
        if($this->key == 'snapp'){
            $this->createSnappTransaction($order);
        }elseif($this->key == 'saman'){

        }
    }


    public function createSnappTransaction($order)
    {
        $gateway = new SnappPayGateway();
        $order = Order::find($order->id);
        $mobile = $this->normalizeIranPhone($order->address->receiver_phone) ?? "+989000000000";

        // Final amount (can come from DB or computed)
        $final_amount = $order->final_amount ?? $order->orderItems->sum(fn($item) => $item->price * $item->count);
        // Build cart items dynamically from orderItems
        $cartItems = $order->orderItems->map(function ($item,$index) use ($final_amount) {
            return [
                "amount"         => $item->price * 10,
                "category"       => "انگشتر",
                "count"          => $item->count,
                "id"             => $index,
                "name"           => $item->name,
                "commissionType" => 100
            ];
        })->toArray();

        $payload = [
            "amount" => $order->final_amount * 10,
            "cartList" => [
                [
                    "cartId"            => 0,
                    "cartItems"         => $cartItems,
                    "totalAmount"       => ($order->total_amount + $order->shipping_price) * 10,
                    "isShipmentIncluded" => true,
                    "shippingAmount"=> $order->shipping_price * 10,
                    "isTaxIncluded"=> true,
                    "taxAmount" => 0
                ]
            ],
            "discountAmount"      => $order->discount_price * 10,
            "externalSourceAmount"=> 0,
            "mobile"              => $mobile,
            "paymentMethodTypeDto"=> "INSTALLMENT",
            "returnURL"           => route('payment.callback'),
            "transactionId"       => Order::generateUniqueTransactionId(),
        ];
        Log::info(json_encode($payload));
        $response = $gateway->getPaymentToken($payload);
        if ($response && isset($response['paymentToken'])) {
            $order->update([
                'transaction_id' => $payload['transactionId'],
                'payment_token'  => $response['paymentToken'],
                'payment_url'    => $response['paymentPageUrl'] ?? null,
            ]);

            if (isset($response['paymentPageUrl'])) {
                return  [
                    'response' => $response,
                ];
            }
        }



        return back()->withErrors('SnappPay: Failed to initialize payment.');
    }


    public function configs()
    {
        return $this->hasMany(GatewayConfig::class);
    }

    public function status($payment_token)
    {
        if($this->key == 'snapp'){
           $snapp = new SnappPayGateway();
           return $snapp->getStatus($payment_token);
        }
        return response()->json([
            'error' => 'این قابلیت صرفا جهت سفارشات با درگاه اسنپ می باشد'
        ]);
    }

    public function cancel($payment_token): array
    {
        if($this->key == 'snapp'){
           $snapp = new SnappPayGateway();
           return $snapp->cancel($payment_token);
        }
        return [
            'error' => 'این قابلیت صرفا جهت سفارشات با درگاه اسنپ می باشد'
        ];
    }
    public function settle($payment_token)
    {
        if($this->key == 'snapp'){
           $snapp = new SnappPayGateway();
           return $snapp->settle($payment_token);
        }
        return response()->json([
            'error' => 'این قابلیت صرفا جهت سفارشات با درگاه اسنپ می باشد'
        ]);
    }

    function normalizeIranPhone(?string $phone): ?string
    {
        if (!$phone) return null;

        $phone = trim($phone);

        // If already in +98 format, keep it
        if (preg_match('/^\+98\d{10}$/', $phone)) {
            return $phone;
        }

        // If starts with 09xxxxxxxxx → convert to +98
        if (preg_match('/^09\d{9}$/', $phone)) {
            return '+98' . substr($phone, 1);
        }

        // If starts with 9xxxxxxxxx → convert to +98
        if (preg_match('/^9\d{9}$/', $phone)) {
            return '+98' . $phone;
        }

        // Otherwise return null (invalid format)
        return null;
    }

    public function updateSnappTransaction($order): array
    {

        if($this->key == 'snapp') {
            $gateway = new SnappPayGateway();
            $order = Order::find($order->id);
            // Normalize phone (not strictly needed for update, but keep consistent)
            $mobile = $this->normalizeIranPhone($order->address->receiver_phone) ?? "+989000000000";

            // Compute reduced final amount (example: sum of orderItems * price)
            $final_amount = $order->orderItems->sum(fn($item) => $item->price * $item->count);

            // IMPORTANT: Update must reduce -> make sure amount is less than original
//        if ($final_amount >= $order->original_amount) {
//            return response()->json([
//                'error' => 'Update must reduce the transaction amount.'
//            ]);
//        }

            // Build reduced cart items (exclude removed ones, or adjust counts)
            $cartItems = $order->orderItems->map(function ($item, $index) use ($final_amount) {
                return [
                    "id" => $index,
                    "amount" => $item->price * 10,
                    "category" => $item->etiket ?? "General",
                    "count" => $item->count,
                    "name" => $item->name,
                    "commissionType" => 100
                ];
            })->toArray();

            $payload = [
                "amount" => $order->final_amount * 10,
                "cartList" => [
                    [
                        "cartId" => $order->id,
                        "cartItems" => $cartItems,
                        "totalAmount" => ($order->total_amount + $order->shipping_price) * 10,
                        "isShipmentIncluded" => true,
                        "isTaxIncluded" => true,
                        "shippingAmount" => $order->shipping_price * 10,
                        "taxAmount" => 0,
                    ]
                ],
                "discountAmount" => $order->discount_price * 10,
                "externalSourceAmount" => 0,
                "paymentMethodTypeDto" => "INSTALLMENT",
                "paymentToken" => $order->payment_token, // from createSnappTransaction
            ];
            $response = $gateway->update($payload);
            if ($response && isset($response['transactionId'])) {
                $order->update([
                    'transaction_id' => $response['transactionId'],
                    'final_amount' => $final_amount,
                ]);
                Log::info(json_encode($response));
                return [
                    'response' => $response,
                ];
            }

            return $response;
        }else{
            return [
              'error' => 'این قابلیت فقط برای سفارشات اسنپ امکان پذیر است'
            ];
        }
    }

}
