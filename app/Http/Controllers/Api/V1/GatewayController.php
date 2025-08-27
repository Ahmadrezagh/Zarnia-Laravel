<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\GatewayResource;
use App\Models\Gateway;
use App\Services\PaymentGateways\SamanGateway;
use App\Services\PaymentGateways\SnappPayGateway;
use Illuminate\Http\Request;
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
            "amount" => 3500000, // total purchase amount
            "cartList" => [
                [
                    "cartId" => 0,
                    "cartItems" => [
                        [
                            "amount" => 600000,
                            "category" => "هدفون",
                            "count" => 1,
                            "id" => 0,
                            "name" => "هندزفری رنگ سفید",
                        ],
                        [
                            "amount" => 1300000,
                            "category" => "اکسسوری",
                            "count" => 2,
                            "id" => 0,
                            "name" => "بند ساعت رنگ صورتی تیره",
                        ],
                    ],
                    "isShipmentIncluded" => true,
                    "isTaxIncluded" => true,
                    "shippingAmount" => 350000,
                    "taxAmount" => 300000,
                    "totalAmount" => 3850000,
                ]
            ],
            "discountAmount" => 310000,
            "externalSourceAmount" => 40000,
            "mobile" => "+989139759913",
            "paymentMethodTypeDto" => "INSTALLMENT",
            "returnURL" => 'https://zarniagoldgallery.ir/payment/callback', // Laravel callback route
            "transactionId" => (string) Str::uuid(),
        ];

        $response = $gateway->getPaymentToken($payload);
//        return $response;
        if ($response && isset($response['paymentPageUrl'])) {
            return redirect($response['paymentPageUrl']);
        }

        return back()->withErrors('SnappPay: Failed to initialize payment.');
    }

    public function callback2(Request $request)
    {
        $gateway = new SnappPayGateway();

        $verify = $gateway->verify($request->paymentToken);

        if ($verify) {
            // success
            return "✅ Payment verified successfully!";
        }

        return "❌ Payment verification failed!";
    }
}
