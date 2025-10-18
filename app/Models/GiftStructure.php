<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiftStructure extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_price',
        'to_price',
        'amount',
        'percentage',
        'limit_in_days',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Find applicable gift structure for a given price
     */
    public static function findApplicable($totalPrice)
    {
        return self::where('is_active', true)
            ->where('from_price', '<=', $totalPrice)
            ->where('to_price', '>=', $totalPrice)
            ->first();
    }

    /**
     * Generate a discount code for a user based on this gift structure
     */
    public function generateDiscountForUser($userId)
    {
        $code = Discount::generateUniqueCode(8);
        
        // Determine discount type: percentage takes priority over amount
        $discountData = [
            'code' => $code,
            'quantity' => 1, // One-time use
            'quantity_per_user' => 1, // One use per user
            'start_at' => now(),
            'expires_at' => now()->addDays($this->limit_in_days),
        ];

        // If percentage is available, create percentage discount
        if ($this->percentage) {
            $discountData['percentage'] = $this->percentage;
            $discountData['amount'] = null;
        }
        // Otherwise if amount is available, create fixed amount discount
        elseif ($this->amount) {
            $discountData['amount'] = $this->amount;
            $discountData['percentage'] = null;
        }
        
        $discount = Discount::create($discountData);

        // Attach to specific user
        $discount->users()->attach($userId);

        return $discount;
    }

    /**
     * Check order and generate gift discount if applicable
     * 
     * @param \App\Models\Order $order
     * @return \App\Models\Discount|null
     */
    public static function checkAndGenerateGift($order)
    {
        // Find applicable gift structure based on order's final amount
        $giftStructure = self::findApplicable($order->final_amount);
        
        if (!$giftStructure) {
            return null;
        }

        // Check if percentage is available
        if ($giftStructure->percentage) {
            // Generate percentage-based discount
            return $giftStructure->generateDiscountForUser($order->user_id);
        }
        // Otherwise check if amount is available
        elseif ($giftStructure->amount) {
            // Generate fixed amount discount
            return $giftStructure->generateDiscountForUser($order->user_id);
        }

        return null;
    }
}

