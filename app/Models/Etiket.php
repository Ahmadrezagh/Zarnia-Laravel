<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Etiket extends Model
{
    protected $fillable = [
        'code',
        'name',
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

    public function setNameAttribute($value)
    {
        // Arabic Ye characters → Persian Ye (U+06CC)
        $value = str_replace(['ي', 'ی'], 'ی', $value);

        // (Optional) also handle Arabic Kaf → Persian Kaf
        $value = str_replace('ك', 'ک', $value);

        $this->attributes['name'] = $value;
    }
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
}
