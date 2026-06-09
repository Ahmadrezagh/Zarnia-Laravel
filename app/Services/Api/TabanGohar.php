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
        return true;
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
        return true;
    }

    /**
     * Update all etikets' prices based on their weight and product's ojrat
     * This can be called independently to recalculate all etiket prices
     * Note: Products no longer store price/weight directly - these are calculated from etikets
     */
    public function updateAllProductsPrices(): void
    {
        
    }
    
    /**
     * Calculate etiket price based on etiket weight and product attributes
     * Formula: weight * gold_price * 1.01 * (1 + ojrat/100)
     * Note: darsad_kharid has been removed from products, using ojrat instead
     */

}

