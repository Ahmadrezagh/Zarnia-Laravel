<?php

namespace App\Services\Api;

use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
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
     * Note: This only updates the gold_price setting, not product prices
     * Use updateAllProductsPrices() separately to recalculate product prices
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

    /**
     * Update all etikets' prices based on their weight and product's ojrat
     * This can be called independently to recalculate all etiket prices
     * Note: Products no longer store price/weight directly - these are calculated from etikets
     */
    public function updateAllProductsPrices(): void
    {
        try {
            $updatedEtiketsCount = 0;
            
            // Get all products (both regular and comprehensive)
            $products = Product::with('etikets')->get();
            
            foreach ($products as $product) {
                // Update all etikets for this product
                $etikets = $product->etikets;
                
                foreach ($etikets as $etiket) {
                    // Calculate price based on etiket's weight and product's attributes
                    $etiketPrice = $this->calculateEtiketPrice($product, $etiket->weight);
                    
                    if ($etiketPrice > 0) {
                        $etiket->updateQuietly(['price' => $etiketPrice]);
                        $updatedEtiketsCount++;
                    }
                }
            }

            Log::info('All etikets updated with new gold price', [
                'updated_etikets' => $updatedEtiketsCount
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating etikets after gold price change', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Calculate etiket price based on etiket weight and product attributes
     * Formula: weight * gold_price * 1.01 * (1 + ojrat/100)
     * Note: darsad_kharid has been removed from products, using ojrat instead
     */
    private function calculateEtiketPrice(Product $product, float $etiketWeight): float
    {
        $baseGoldPrice = (float) setting('gold_price') ?? 0;
        $ojrat = $product->ojrat ?? 0;
        
        // Add 1% to gold price
        $goldPrice = $baseGoldPrice * 1.01;
        
        if ($etiketWeight > 0 && $goldPrice > 0 && $ojrat > 0) {
            // Calculate price: weight * gold_price * (1 + ojrat/100)
            $finalPrice = $etiketWeight * $goldPrice * (1 + ($ojrat / 100));
            
            // Round down to nearest thousand
            return floor($finalPrice / 1000) * 1000;
        }
        
        return 0;
    }
}

