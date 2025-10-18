<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Discount extends Model
{
    protected $fillable = [
        'code',
        'percentage',
        'amount',
        'min_price',
        'max_price',
        'quantity',
        'quantity_per_user',
        'start_at',
        'expires_at'
    ];

    public static function generateUniqueCode($length = 6): string
    {
        do {
            $code = Str::upper(Str::random($length));
        } while (Discount::where('code', $code)->exists());

        return $code;
    }

    public function getStartAtYmdAttribute()
    {
        return date('Y-m-d H:i:s',strtotime($this->getRawOriginal('start_at')));
    }

    public function getExpiresAtYmdAttribute()
    {
        return date('Y-m-d H:i:s',strtotime($this->getRawOriginal('expires_at')));
    }

    public function orders()
    {
        return $this->hasMany(Order::class,'discount_code','code')->whereIn('status',[ Order::$STATUSES[1], Order::$STATUSES[4], Order::$STATUSES[5],Order::$STATUSES[6],Order::$STATUSES[7] ]);
    }

    /**
     * Get all discountables for this discount.
     */
    public function discountables()
    {
        return $this->hasMany(Discountable::class);
    }

    /**
     * Get all users who can use this discount.
     */
    public function users()
    {
        return $this->morphedByMany(User::class, 'discountable');
    }

    /**
     * Get all products this discount applies to.
     */
    public function products()
    {
        return $this->morphedByMany(Product::class, 'discountable');
    }

    /**
     * Get all categories this discount applies to.
     */
    public function categories()
    {
        return $this->morphedByMany(Category::class, 'discountable');
    }

    public static function verify($code, $totalPrice, $userId = null, $productIds = [], $categoryIds = [])
    {
        $userId = $userId ?? Auth::id();
        $now = Carbon::now();

        // پیدا کردن کد تخفیف
        $discount = self::where('code', $code)->with(['users', 'products', 'categories'])->first();

        if (!$discount) {
            return ['valid' => false, 'message' => 'کد تخفیف یافت نشد.'];
        }

        // بررسی تاریخ انقضا
        if ($discount->expires_at && $now->gt($discount->expires_at)) {
            return ['valid' => false, 'message' => 'کد تخفیف منقضی شده است.'];
        }

        // بررسی تاریخ شروع
        if ($discount->start_at && $now->lt($discount->start_at)) {
            return ['valid' => false, 'message' => 'کد تخفیف هنوز فعال نشده است.'];
        }

        // بررسی کاربران مجاز
        // if ($discount->users()->count() > 0) {
        //     if (!$userId || !$discount->users()->where('users.id', $userId)->exists()) {
        //         return ['valid' => false, 'message' => 'این کد تخفیف برای شما مجاز نیست.'];
        //     }
        // }

        // بررسی محصولات مجاز
        if ($discount->products()->count() > 0) {
            $productIds = is_array($productIds) ? $productIds : [$productIds];
            
            if (empty($productIds)) {
                return ['valid' => false, 'message' => 'این کد تخفیف فقط برای محصولات خاصی معتبر است.'];
            }
            
            $allowedProductIds = $discount->products()->pluck('products.id')->toArray();
            $hasValidProduct = count(array_intersect($productIds, $allowedProductIds)) > 0;
            
            if (!$hasValidProduct) {
                return ['valid' => false, 'message' => 'این کد تخفیف برای محصولات موجود در سبد خرید شما معتبر نیست.'];
            }
        }

        // بررسی دسته‌بندی‌های مجاز
        if ($discount->categories()->count() > 0) {
            $categoryIds = is_array($categoryIds) ? $categoryIds : [$categoryIds];
            
            if (empty($categoryIds)) {
                return ['valid' => false, 'message' => 'این کد تخفیف فقط برای دسته‌بندی‌های خاصی معتبر است.'];
            }
            
            $allowedCategoryIds = $discount->categories()->pluck('categories.id')->toArray();
            $hasValidCategory = count(array_intersect($categoryIds, $allowedCategoryIds)) > 0;
            
            if (!$hasValidCategory) {
                return ['valid' => false, 'message' => 'این کد تخفیف برای دسته‌بندی محصولات موجود در سبد خرید شما معتبر نیست.'];
            }
        }

        // بررسی تعداد کل مجاز
        if ($discount->quantity !== null && $discount->quantity == $discount->orders()->count()) {
            return ['valid' => false, 'message' => 'حداکثر استفاده از این کد تخفیف به پایان رسیده است.'];
        }

        // بررسی حداقل قیمت
        if ($discount->min_price && $totalPrice < $discount->min_price) {
            return ['valid' => false, 'message' => 'مبلغ خرید کمتر از حداقل مورد نیاز برای این تخفیف است.'];
        }

        // بررسی حداکثر قیمت
        if ($discount->max_price && $totalPrice > $discount->max_price) {
            return ['valid' => false, 'message' => 'مبلغ خرید بیشتر از حداکثر مجاز برای این تخفیف است.'];
        }

        // بررسی تعداد استفاده هر کاربر
        if ($discount->quantity_per_user) {
            $userUsage = Order::where('discount_code', $discount->code)
                ->where('user_id', $userId)
                ->count();

            if ($userUsage >= $discount->quantity_per_user) {
                return ['valid' => false, 'message' => 'شما قبلاً از این کد تخفیف استفاده کرده‌اید.'];
            }
        }

        // محاسبه مبلغ تخفیف
        $discountAmount = 0;
        if ($discount->amount) { // مبلغ ثابت
            $discountAmount = $discount->amount;
        } elseif ($discount->percentage) { // درصدی
            $discountAmount = ($discount->percentage / 100) * $totalPrice;
        }

        // اگر همه چیز درست بود
        return [
            'valid' => true,
            'message' => 'کد تخفیف معتبر است. مبلغ تخفیف: ' . number_format($discountAmount) . ' تومان',
            'discount' => $discount,
            'discount_amount' => $discountAmount
        ];
    }


}
