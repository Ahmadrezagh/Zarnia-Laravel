<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NajvaService
{
    const NAJVA_WEBHOOK_URL = 'https://automation.najva.com/webhook/461bc4e9-758c-411a-be89-a836a8e2dabc';
    const NAJVA_USER = '67040';
    const NAJVA_PASS = 'zarnia67040';
    const NAJVA_TOKEN = 'cd36b51c-3b97-456b-8a25-fbf533de95e4';

    /**
     * Send buy product notification to Najva
     *
     * @param string $phoneNumber User's phone number
     * @param string $name User's name
     * @param string|int $productId Product ID
     * @return \Illuminate\Http\Client\Response|null
     */
    public function sendBuyProductNotification($phoneNumber, $name, $productId)
    {
        try {
            $body = [
                'Event' => 'Buy Product',
                'Phone_number' => $phoneNumber,
                'Token' => self::NAJVA_TOKEN,
                'Product_id' => (string) $productId,
                'Name' => $name,
            ];

            // Add Basic Authentication
            $basicAuth = base64_encode(self::NAJVA_USER . ':' . self::NAJVA_PASS);
            
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $basicAuth,
            ])->post(self::NAJVA_WEBHOOK_URL, $body);

            // Log the request and response
            Log::info('Najva webhook sent', [
                'url' => self::NAJVA_WEBHOOK_URL,
                'body' => $body,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('Najva webhook failed', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber,
                'product_id' => $productId,
            ]);

            return null;
        }
    }

    /**
     * Send custom event notification to Najva
     *
     * @param string $event Event name
     * @param string $phoneNumber User's phone number
     * @param string $name User's name
     * @param string|int $productId Product ID
     * @return \Illuminate\Http\Client\Response|null
     */
    public function sendNotification($event, $phoneNumber, $name, $productId)
    {
        try {
            $body = [
                'Event' => $event,
                'Phone_number' => $phoneNumber,
                'Token' => self::NAJVA_TOKEN,
                'Product_id' => (string) $productId,
                'Name' => $name,
            ];

            // Add Basic Authentication
            $basicAuth = base64_encode(self::NAJVA_USER . ':' . self::NAJVA_PASS);
            
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $basicAuth,
            ])->post(self::NAJVA_WEBHOOK_URL, $body);

            Log::info('Najva webhook sent', [
                'url' => self::NAJVA_WEBHOOK_URL,
                'body' => $body,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('Najva webhook failed', [
                'error' => $e->getMessage(),
                'event' => $event,
                'phone' => $phoneNumber,
                'product_id' => $productId,
            ]);

            return null;
        }
    }
}

