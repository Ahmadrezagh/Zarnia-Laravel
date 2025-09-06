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
        'color'
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

        $payload = [
            "amount" => $order->final_amount, // total purchase amount
            "cartList" => [
                [
                    "cartId" => 0,
                    "cartItems" => [
                        [
                            "amount" => $order->final_amount,
                            "category" => "هدفون",
                            "count" => 1,
                            "id" => 0,
                            "name" => "هندزفری رنگ سفید",
                        ],
                    ],
                    "totalAmount" => $order->final_amount,
                ]
            ],
            "mobile" => "+989139759913",
            "paymentMethodTypeDto" => "INSTALLMENT",
            "returnURL" => route('payment.callback'), // Laravel callback route
            "transactionId" => (string) Str::uuid(),
        ];

        $response = $gateway->getPaymentToken($payload);
        $order->update([
            'transaction_id' => $payload['transactionId'],
            'payment_token' => $response['paymentToken'],
            'payment_url' => $response['paymentPageUrl'],
        ]);
        if ($response && isset($response['paymentPageUrl'])) {
            return redirect($response['paymentPageUrl']);
        }

        return back()->withErrors('SnappPay: Failed to initialize payment.');
    }
}
