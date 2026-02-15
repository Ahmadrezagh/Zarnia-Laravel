<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Etiket extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'code',
        'weight',
        'price',
        'product_id',
        'ojrat',
        'is_mojood',
        'darsad_kharid',
        'mazaneh',
        'darsad_vazn_foroosh',
        'orderable_after_out_of_stock'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Check if this etiket is currently reserved (cached for 32 minutes during order processing)
     * Reserved etikets should return is_mojood = 0 during the reservation period
     */
    public function isReserved(): bool
    {
        $cacheKey = 'reserved_etiket_' . $this->code;
        return Cache::has($cacheKey);
    }

    /**
     * Get the effective availability status (considering reservations)
     * Returns 0 if reserved, otherwise returns the actual is_mojood value
     */
    public function getEffectiveIsMojoodAttribute(): int
    {
        if ($this->isReserved()) {
            return 0;
        }
        return (int) $this->is_mojood;
    }


    public function getTabanGoharPriceAttribute()
    {
        $weight = $this->weight ?? 0;
        $baseGoldPrice = (float) setting('gold_price') ?? 0;
        $ojrat = $this->ojrat ?? 0;
        
        // Add 1% to gold price
        $goldPrice = $baseGoldPrice * 1.01;
        
        if ($weight > 0 && $goldPrice > 0 && $ojrat > 0) {
            $price = $weight * $goldPrice * (1 + ($ojrat / 100));
            // Round down to nearest thousand (last three digits become 0)
            return floor($price / 1000) * 1000;
        }
        
        return 0;
    }
}
