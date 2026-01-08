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
        $order = null;
        
        // Handle SnappPay callback (uses transactionId)
        if ($request->has('transactionId')) {
            $transaction_id = $request->transactionId;
            $order = Order::query()->where('transaction_id', $transaction_id)->first();
            if($order && $order->status == Order::$STATUSES[0]){
                $verified = $order->verify();
                if($verified){
                    // Send Najva notifications when payment is successfully verified
                    $order->sendNajvaNotifications();
                    $order->notifyAdminsNewOrder();
                }
            }
        }
        // Handle Saman callback (uses Token to find order by payment_token)
        elseif ($request->has('Token')) {
            try {
                $token = $request->Token;
                $order = Order::where('payment_token', $token)->first();
                
                // Check if State is OK and order exists with Saman gateway
                if ($request->State == 'OK' && $order && $order->gateway && $order->gateway->key == 'saman' && $order->status == Order::$STATUSES[0]) {
                    $samanGateway = new SamanGateway();
                    
                    // Verify transaction using RefNum and TerminalId
                    if ($request->has('RefNum') && $request->has('TerminalId')) {
                        $verifyResult = $samanGateway->verifyTransaction($request->RefNum, $request->TerminalId);
                        
                        if ($verifyResult['success']) {
                            // Verification successful, mark order as paid directly
                            // (no need to call verify() again as we've already verified with verifyTransaction)
                            if ($order->status == Order::$STATUSES[0]) {
                                $order->markAsPaid();
                                // Send Najva notifications when payment is successfully verified
                                $order->sendNajvaNotifications();
                                $order->notifyAdminsNewOrder();
                            }
                        } else {
                            // Verification failed
                            Log::info('Saman: Transaction verification failed', [
                                'order_id' => $order->id,
                                'ref_num' => $request->RefNum,
                                'message' => $verifyResult['message'] ?? 'Unknown error',
                            ]);
                        }
                    } else {
                        Log::warning('Saman: Missing RefNum or TerminalId in callback', [
                            'order_id' => $order->id,
                            'request' => $request->all(),
                        ]);
                    }
                } elseif ($request->State != 'OK' && $order) {
                    // Payment failed or cancelled
                    Log::info('Saman: Payment failed or cancelled', [
                        'order_id' => $order->id,
                        'state' => $request->State,
                        'message' => $request->Message ?? 'Payment cancelled or failed',
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Saman Gateway Error : ' . $e->getMessage(), [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'token' => $token ?? null,
                ]);
            }
        }
        
        if($order) {
            if ($order->status === 'paid') {
                return view('thank-you.index', compact('order'));
            }
            return redirect(setting('url') . '/cart');
        }
        return abort(404);
    }
}
