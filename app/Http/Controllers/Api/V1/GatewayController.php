<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\GatewayResource;
use App\Models\Gateway;
use App\Services\PaymentGateways\SamanGateway;
use Illuminate\Http\Request;

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
        $callbackUrl = route('payment.callback');

        try {
            $result = $gateway->requestPayment($amount, $orderId, $callbackUrl, [
                'mobile'      => '09120000000',
                'description' => 'Test order',
            ]);

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
}
