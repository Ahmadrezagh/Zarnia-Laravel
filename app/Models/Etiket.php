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
        'type',
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

    public function attributeValues()
    {
        return $this->hasMany(AttributeValue::class);
    }

    public function comprehensiveEtikets()
    {
        return $this->hasMany(ComprehensiveEtiket::class);
    }

    public function relatedComprehensiveEtikets()
    {
        return $this->hasMany(ComprehensiveEtiket::class, 'related_etiket_id');
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

    /**
     * Get the original price (stored price * 10 for display)
     * This returns the price from database without any discount applied.
     * For comprehensive etikets, this remains the raw stored price (0 by design),
     * while the effective price is calculated in getPriceAttribute().
     */
    public function getOriginalPriceAttribute()
    {
        return $this->getRawOriginal('price') * 10;
    }

    /**
     * Get discounted price based on product's discount_percentage
     * If product has a parent, checks parent's discount_percentage
     * Returns null if no discount, otherwise returns the discounted price
     */
    public function getDiscountedPriceAttribute()
    {
        // Load product if not already loaded
        if (!$this->relationLoaded('product')) {
            $this->load('product');
        }

        if (!$this->product) {
            return null;
        }

        $discountPercentage = 0;

        // If product has a parent, use parent's discount_percentage
        if ($this->product->parent_id) {
            if (!$this->product->relationLoaded('parent')) {
                $this->product->load('parent');
            }
            
            if ($this->product->parent) {
                $discountPercentage = $this->product->parent->discount_percentage ?? 0;
            }
        } else {
            // Use own discount percentage
            $discountPercentage = $this->product->discount_percentage ?? 0;
        }

        if ($discountPercentage > 0 && $this->original_price > 0) {
            // Calculate discounted price using original_price
            $discountedPrice = $this->original_price * (1 - $discountPercentage / 100);
            
            // Round to nearest integer
            return (int) round($discountedPrice);
        }

        return null;
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

    /**
     * Get the effective price.
     * - For real etikets: discounted price if available, otherwise original.
     * - For comprehensive etikets: sum of effective prices of related etikets.
     */
    public function getPriceAttribute($value)
    {
        if ($this->type === 'comprehensive') {
            // Try to use already loaded relation to avoid N+1
            $links = $this->relationLoaded('comprehensiveEtikets')
                ? $this->comprehensiveEtikets
                : $this->comprehensiveEtikets()->with('relatedEtiket')->get();

            if (!$links || $links->isEmpty()) {
                return 0;
            }

            return $links->sum(function (ComprehensiveEtiket $link) {
                $related = $link->relatedEtiket;
                if (!$related) {
                    return 0;
                }
                // Use effective price of each related etiket (handles its own discounts)
                return (int) ($related->price ?? 0);
            });
        }

        // For regular etikets, use discounted price if available; otherwise original price
        return $this->discounted_price ?? $this->original_price;
    }

    /**
     * Get effective weight.
     * - For real etikets: just the stored weight.
     * - For comprehensive etikets: sum of weights of related etikets.
     */
    public function getWeightAttribute($value)
    {
        if ($this->type === 'comprehensive') {
            $links = $this->relationLoaded('comprehensiveEtikets')
                ? $this->comprehensiveEtikets
                : $this->comprehensiveEtikets()->with('relatedEtiket')->get();

            if (!$links || $links->isEmpty()) {
                return 0;
            }

            $total = $links->sum(function (ComprehensiveEtiket $link) {
                $related = $link->relatedEtiket;
                if (!$related) {
                    return 0;
                }
                // Use effective weight of related etikets (supports nested comprehensive if ever needed)
                return (float) ($related->weight ?? 0);
            });

            // For comprehensive etikets, show weight with at most two digits after decimal
            return round($total, 2);
        }

        return (float) $value;
    }

    public function getNameAttribute()
    {
        if($this->product){
        return $this->product->name;
        }
        return '-';
    }
}
