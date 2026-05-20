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
        // For comprehensive etikets, consider reserved if ANY related etiket is reserved
        if ($this->type === 'comprehensive') {
            $links = $this->relationLoaded('comprehensiveEtikets')
                ? $this->comprehensiveEtikets
                : $this->comprehensiveEtikets()->with('relatedEtiket')->get();

            if (!$links || $links->isEmpty()) {
                return false;
            }

            foreach ($links as $link) {
                $related = $link->relatedEtiket;
                if ($related && $related->isReserved()) {
                    return true;
                }
            }

            return false;
        }

        $cacheKey = 'reserved_etiket_' . $this->code;
        return Cache::has($cacheKey);
    }

    /**
     * Base availability accessor.
     * - For comprehensive etikets: 1 only if ALL related etikets have is_mojood == 1.
     * - For other etikets: returns the raw database value.
     */
    public function getIsMojoodAttribute($value): int
    {
        if ($this->type === 'comprehensive') {
            $links = $this->relationLoaded('comprehensiveEtikets')
                ? $this->comprehensiveEtikets
                : $this->comprehensiveEtikets()->with('relatedEtiket')->get();

            if (!$links || $links->isEmpty()) {
                return 0;
            }

            $allAvailable = $links->every(function (ComprehensiveEtiket $link) {
                $related = $link->relatedEtiket;
                if (!$related) {
                    return false;
                }
                return (int) ($related->is_mojood ?? 0) === 1;
            });

            return $allAvailable ? 1 : 0;
        }

        return (int) $value;
    }

    /**
     * Effective availability (considers reservations).
     * Returns 0 if reserved, otherwise uses the is_mojood accessor above.
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

    /**
     * Whether a code is already used (includes soft-deleted etikets).
     */
    public static function codeExists(string $code): bool
    {
        return static::withTrashed()->where('code', $code)->exists();
    }

    /**
     * Generate next unique auto etiket code ({number} or {prefix}-{number}).
     * Considers soft-deleted rows so codes are never reused.
     *
     * @param  array<int, string>  $batchCodes  Codes already assigned in the current request
     * @param  string  $prefix  '' for numeric codes, 's' for orderable (s-XXXX)
     */
    public static function generateUniqueCode(array $batchCodes = [], string $prefix = ''): string
    {
        $startNumber = 7000;
        $highestNumber = $startNumber - 1;

        $query = static::withTrashed();

        if ($prefix !== '') {
            $pattern = '/^'.preg_quote($prefix, '/').'-(\d+)$/';
            foreach ($query->where('code', 'like', $prefix.'-%')->pluck('code') as $code) {
                if (preg_match($pattern, (string) $code, $matches)) {
                    $highestNumber = max($highestNumber, (int) $matches[1]);
                }
            }
        } else {
            foreach ($query->whereRaw("code REGEXP '^[0-9]+$'")->pluck('code') as $code) {
                if (preg_match('/^(\d+)$/', (string) $code, $matches)) {
                    $highestNumber = max($highestNumber, (int) $matches[1]);
                }
            }
        }

        foreach ($batchCodes as $code) {
            if ($prefix !== '') {
                $pattern = '/^'.preg_quote($prefix, '/').'-(\d+)$/';
                if (preg_match($pattern, (string) $code, $matches)) {
                    $highestNumber = max($highestNumber, (int) $matches[1]);
                }
            } elseif (preg_match('/^(\d+)$/', (string) $code, $matches)) {
                $highestNumber = max($highestNumber, (int) $matches[1]);
            }
        }

        $maxId = (int) static::withTrashed()->max('id');
        $nextFromId = $maxId + 1;
        $nextFromCodes = max($highestNumber + 1, $startNumber);
        $nextNumber = $nextFromCodes <= $nextFromId ? $nextFromCodes : $nextFromId;

        do {
            $candidate = $prefix !== '' ? $prefix.'-'.$nextNumber : (string) $nextNumber;
            $nextNumber++;
        } while (static::codeExists($candidate) || in_array($candidate, $batchCodes, true));

        return $candidate;
    }

    /**
     * Next numeric part for UI preview (includes soft-deleted etikets in max id).
     */
    public static function nextAutoCodeNumber(string $prefix = '', int $pending = 0): int
    {
        $startNumber = 7000;
        $maxId = (int) static::withTrashed()->max('id');

        return max($maxId + 1 + $pending, $startNumber);
    }
}
