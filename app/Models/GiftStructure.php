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
     * Get discount information (amount or percentage)
     */
    public function getDiscountInfoAttribute()
    {
        // Percentage takes priority over amount (same logic as generateDiscountForUser)
        if (!is_null($this->percentage) && $this->percentage > 0) {
            return $this->percentage . '%';
        }
        
        // Otherwise show amount if available
        if (!is_null($this->amount) && $this->amount > 0) {
            return number_format($this->amount) . ' تومان';
        }
        
        // If neither exists, return dash
        return '-';
    }

    /**
     * Get formatted from_price
     */
    public function getFromPriceFormattedAttribute()
    {
        return number_format($this->from_price) . ' تومان';
    }

    /**
     * Get formatted to_price
     */
    public function getToPriceFormattedAttribute()
    {
        return number_format($this->to_price) . ' تومان';
    }

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
     * 
     * @param int $userId The user ID
     * @param float $orderFinalAmount The order's final amount (required for percentage calculation)
     * @return \App\Models\Discount
     */
    public function generateDiscountForUser($userId, $orderFinalAmount = null)
    {
        $code = Discount::generateUniqueCode(8);
        
        $discountData = [
            'code' => $code,
            'quantity' => 1, // One-time use
            'quantity_per_user' => 1, // One use per user
            'start_at' => now(),
            'expires_at' => now()->addDays($this->limit_in_days),
            'percentage' => null, // Always create fixed amount discount
        ];

        // If percentage is available, calculate fixed amount from order's final amount
        if (!is_null($this->percentage) && $this->percentage > 0 && $orderFinalAmount) {
            $discountData['amount'] = ($this->percentage / 100) * $orderFinalAmount;
        }
        // Otherwise if fixed amount is available, use it directly
        elseif (!is_null($this->amount) && $this->amount > 0) {
            $discountData['amount'] = $this->amount;
        }
        else {
            // No valid discount configuration
            throw new \Exception('Gift structure must have either amount or percentage set');
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

        // Generate discount with order's final amount
        // If percentage exists, it will be converted to fixed amount based on order's final_amount
        // If fixed amount exists, it will be used directly
        return $giftStructure->generateDiscountForUser($order->user_id, $order->final_amount);
    }
}

