<?php

namespace App\Services\Api;

use App\Models\Product;
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

            // Update all products with new gold price
            $this->updateAllProductsPrices();

            return true;
        }

        Log::warning('TabanGohar API: YekGram18 is not greater than 0', [
            'yek_gram_18' => $yekGram18
        ]);

        return false;
    }

    /**
     * Update all products' prices based on tabanGoharPrice and update discounted prices
     */
    private function updateAllProductsPrices(): void
    {
        try {
            $updatedCount = 0;
            
            // Process products in chunks to avoid memory issues
            Product::query()->chunk(100, function ($products) use (&$updatedCount) {
                foreach ($products as $product) {
                    // Refresh to ensure we have latest attributes
                    $product->refresh();
                    
                    // Calculate tabanGoharPrice
                    $tabanGoharPrice = $product->taban_gohar_price;
                    
                    if ($tabanGoharPrice > 0) {
                        // Update product price (multiply by 10 to match database format)
                        $product->updateQuietly([
                            'price' => $tabanGoharPrice * 10
                        ]);
                        
                        // Update discounted price
                        $this->updateDiscountedPrice($product);
                        
                        $updatedCount++;
                    }
                }
            });

            Log::info('All products updated with new gold price', [
                'updated_count' => $updatedCount
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating products after gold price change', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Calculate and update discounted price for a product
     */
    private function updateDiscountedPrice(Product $product): void
    {
        // Get raw price value (stored multiplied by 10) and discount percentage
        $rawPrice = $product->getRawOriginal('price');
        
        // If product has parent, use parent's discount_percentage
        $discountPercentage = 0;
        if ($product->parent_id) {
            $parent = Product::find($product->parent_id);
            if ($parent) {
                $discountPercentage = $parent->getRawOriginal('discount_percentage') ?? 0;
            }
        } else {
            // Use own discount_percentage
            $discountPercentage = $product->getRawOriginal('discount_percentage') ?? 0;
        }

        if ($rawPrice != 0 && $discountPercentage != 0) {
            // Calculate discounted price
            // Raw price is stored multiplied by 10, so divide by 10 to get actual price
            // discounted_price is stored as-is (not multiplied by 10)
            $discountedPrice = ($rawPrice / 10) * (1 - $discountPercentage / 100);

            // Round to nearest 1000 (last three digits to 000)
            $discountedPrice = round($discountedPrice, -3);

            $product->discounted_price = $discountedPrice;
        } else {
            $product->discounted_price = null;
        }

        $product->saveQuietly();
    }
}

