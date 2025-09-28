<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\GatewayResource;
use App\Models\Gateway;
use App\Models\Order;
use App\Services\PaymentGateways\SamanGateway;
use App\Services\PaymentGateways\SnappPayGateway;
use App\Services\SMS\Kavehnegar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GatewayController extends Controller
{
    public function index()
    {
        return GatewayResource::collection(Gateway::all());
    }

    public function request()
    {
        $gateway = new SamanGateway();

        $amount      = 10000; // test amount in Rials
        $orderId     = 'TEST-' . time();
        $callbackUrl = 'https://zarniagoldgallery.ir/payment/callback';

        try {
            $result = $gateway->requestPayment($amount, $orderId, $callbackUrl, [
                'mobile'      => '09139759913',
                'description' => 'Test order',
            ]);
            return $result;
            // redirect user to bank page
            return redirect()->away($result['redirect_url']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function callback(Request $request)
    {
        $gateway = new SamanGateway();

        try {
            $verify = $gateway->callback($request);

            if ($verify['status'] === 'success') {
                return "Payment successful. RefNum: " . $verify['ref_num'];
            } else {
                return "Payment failed: " . $verify['message'];
            }
        } catch (\Exception $e) {
            return "Error verifying payment: " . $e->getMessage();
        }
    }


    public function pay()
    {
        $gateway = new SnappPayGateway();

        $payload = [
            "amount" => 45000, // total purchase amount
            "cartList" => [
                [
                    "cartId" => 0,
                    "cartItems" => [
                        [
                            "amount" => 45000,
                            "category" => "هدفون",
                            "count" => 1,
                            "id" => 0,
                            "name" => "هندزفری رنگ سفید",
                        ],
                    ],
                    "totalAmount" => 45000,
                ]
            ],
            "mobile" => "+989139759913",
            "paymentMethodTypeDto" => "INSTALLMENT",
            "returnURL" => route('payment.callback'), // Laravel callback route
            "transactionId" => (string) Str::uuid(),
        ];

        $response = $gateway->getPaymentToken($payload);
        Log::info('transactionId: ' . $payload['transactionId']."\n"."paymentToken: ".$response['paymentToken']);
        if ($response && isset($response['paymentPageUrl'])) {
            return redirect($response['paymentPageUrl']);
        }

        return back()->withErrors('SnappPay: Failed to initialize payment.');
    }

    public function callback2(Request $request)
    {

        $transaction_id = $request->transactionId;
        $order = Order::query()->where('transaction_id', $transaction_id)->first();
        if($order) {
            if ($order->status == Order::$STATUSES[0]) {
                 $order->verify();
            }
            return view('thank-you.index', compact('order'));
        }
        return abort(404);
    }
}
