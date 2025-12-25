<?php

namespace App\Services\Api;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TabanGohar
{
    private $api_url = 'https://webservice.tgnsrv.ir/Pr/Get/zarnia7053/z09127127053z';

    /**
     * Fetch gold prices from Taban Gohar API
     *
     * @return array|null
     */
    public function getGoldPrices()
    {
        try {
            $response = Http::timeout(10)->get($this->api_url);

            if ($response->successful()) {
                $data = $response->json();
                
                if (is_array($data) && isset($data['YekGram18'])) {
                    return $data;
                }
            }

            Log::warning('TabanGohar API: Invalid response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('TabanGohar API: Request failed', [
                'message' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Update gold price setting from API
     *
     * @return bool
     */
    public function updateGoldPrice()
    {
        $prices = $this->getGoldPrices();

        if ($prices === null) {
            return false;
        }

        $yekGram18 = isset($prices['YekGram18']) ? (int) $prices['YekGram18'] : 0;

        // Only update if YekGram18 > 0
        if ($yekGram18 > 0) {
            Setting::where('key', 'gold_price')->update([
                'value' => (string) $yekGram18
            ]);

            Log::info('Gold price updated successfully', [
                'yek_gram_18' => $yekGram18,
                'time_read' => $prices['TimeRead'] ?? null
            ]);

            return true;
        }

        Log::warning('TabanGohar API: YekGram18 is not greater than 0', [
            'yek_gram_18' => $yekGram18
        ]);

        return false;
    }
}

