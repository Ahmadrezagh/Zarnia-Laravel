<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Discountable extends Model
{
    protected $fillable = [
        'discount_id',
        'discountable_id',
        'discountable_type'
    ];

    /**
     * Get the parent discountable model (User, Product, or Category).
     */
    public function discountable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the discount that owns the discountable.
     */
    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }
}
