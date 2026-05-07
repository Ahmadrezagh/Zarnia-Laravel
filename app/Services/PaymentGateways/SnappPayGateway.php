<?php

namespace App\Services\PaymentGateways;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\GatewayConfig;

class SnappPayGateway
{
    protected ?string $accessToken = null;

    protected function config(string $key): ?string
    {
        return GatewayConfig::getConfig($key, 3); // 3 = snapp pay gateway id
    }

    /**
     * Authenticate & Get JWT Token
     */
    public function authenticate(): ?string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        try {
            $basicAuth = base64_encode(
                $this->config('SNAPPPAY_CLIENT_ID') . ':' . $this->config('SNAPPPAY_CLIENT_SECRET')
            );

            $response = Http::asForm()
                ->withHeaders([
                    'Authorization' => 'Basic ' . $basicAuth,
                ])
                ->post($this->config('SNAPPPAY_BASE_URL') . '/api/online/v1/oauth/token', [
                    'grant_type' => 'password',
                    'scope'     => 'online-merchant',
                    'username'  => $this->config('SNAPPPAY_USERNAME'),
                    'password'  => $this->config('SNAPPPAY_PASSWORD'),
                ]);

            if ($response->successful()) {
                $this->accessToken = $response->json('access_token');
                return $this->accessToken;
            }

            Log::error('SnappPay Auth Failed', ['response' => $response->body()]);
            return null;

        } catch (\Exception $e) {
            Log::error('SnappPay Auth Exception: ' . $e->getMessage());
            return null;
        }
    }

    public function getPaymentToken(array $payload): array
    {
        $token = $this->authenticate();
        if (!$token) return ['error' => 'خطا در بررسی پرداخت'];

        $response = Http::withToken($token)
            ->post($this->config('SNAPPPAY_BASE_URL') . '/api/online/payment/v1/token', $payload);

        return $response->successful() ? $response->json('response') : $response->json();
    }

    public function verify(string $paymentToken): array
    {
        $token = $this->authenticate();
        if (!$token) return ['error' => 'خطا در بررسی پرداخت'];

        $response = Http::withToken($token)
            ->post($this->config('SNAPPPAY_BASE_URL') . '/api/online/payment/v1/verify', [
                'paymentToken' => $paymentToken,
            ]);

        return $response->successful() ? $response->json('response') : $response->json();
    }

    public function settle(string $paymentToken): array
    {
        $token = $this->authenticate();
        if (!$token) return ['error' => 'خطا در بررسی پرداخت'];

        $response = Http::withToken($token)
            ->post($this->config('SNAPPPAY_BASE_URL') . '/api/online/payment/v1/settle', [
                'paymentToken' => $paymentToken,
            ]);

        return $response->successful() ? $response->json('response') : $response->json();
    }

    public function revert(string $paymentToken): array
    {
        $token = $this->authenticate();
        if (!$token) return ['error' => 'خطا در بررسی پرداخت'];

        $response = Http::withToken($token)
            ->post($this->config('SNAPPPAY_BASE_URL') . '/api/online/payment/v1/revert', [
                'paymentToken' => $paymentToken,
            ]);

        return $response->successful() ? $response->json('response') : $response->json();
    }

    public function getStatus(string $paymentToken): array
    {
        $token = $this->authenticate();
        if (!$token) return ['error' => 'خطا در بررسی پرداخت'];

        $response = Http::withToken($token)
            ->get($this->config('SNAPPPAY_BASE_URL') . '/api/online/payment/v1/status', [
                'paymentToken' => $paymentToken,
            ]);

        return $response->successful() ? $response->json('response') : $response->json();
    }

    public function update(array $payload):array
    {
        $token = $this->authenticate();
        if (!$token) return ['error' => 'خطا در بررسی پرداخت'];

        $response = Http::withToken($token)
            ->post($this->config('SNAPPPAY_BASE_URL') . '/api/online/payment/v1/update', $payload);
        return $response->successful() ? $response->json('response') : $response->json();
    }

    public function cancel(string $paymentToken): array
    {
        $token = $this->authenticate();
        if (!$token) return ['error' => 'خطا در بررسی پرداخت'];

        $response = Http::withToken($token)
            ->post($this->config('SNAPPPAY_BASE_URL') . '/api/online/payment/v1/cancel', [
                'paymentToken' => $paymentToken,
            ]);
        return $response->successful() ? $response->json('response') : $response->json();
    }
    public function eligible(string $price): array
    {
        $token = $this->authenticate();
        if (!$token) return ['error' => 'خطا در بررسی پرداخت'];

        $response = Http::withToken($token)
            ->get($this->config('SNAPPPAY_BASE_URL') . '/api/online/offer/v1/eligible?amount='.$price);
        return $response->successful() ? $response->json('response') : $response->json();
    }
}
