<?php

namespace App\Models;

use App\Services\Api\Tahesab;
use App\Services\NajvaService;
use App\Services\PaymentGateways\SamanGateway;
use App\Services\PaymentGateways\SnappPayGateway;
use App\Services\SMS\Kavehnegar;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Morilog\Jalali\Jalalian;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'address_id',
        'shipping_id',
        'shipping_time_id',
        'gateway_id',
        'status',
        'discount_code',
        'discount_price',
        'discount_percentage',
        'total_amount',
        'final_amount',
        'paid_at',
        'note',
        'user_agent',
        'transaction_id',
        'payment_token',
        'payment_url',
        'shipping_price',
        'gold_price',
        'reference',
        'uuid',
        'shipping_date',
        'has_comprehensive_etiket',
        'invoice_snapshot',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'shipping_date' => 'datetime',
        'deleted_at' => 'datetime',
        'has_comprehensive_etiket' => 'boolean',
        'invoice_snapshot' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->uuid)) {
                $order->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

    /**
     * Orders are soft-deleted only; permanent removal is never allowed.
     */
    public function delete()
    {
        if ($this->forceDeleting) {
            Log::warning('Blocked permanent order deletion', ['order_id' => $this->id]);

            return false;
        }

        $this->runSoftDelete();

        return true;
    }

    /**
     * Never hard-delete — already trashed orders stay in trash; active orders are soft-deleted.
     */
    public function forceDelete()
    {
        if ($this->trashed()) {
            return false;
        }

        return (bool) $this->delete();
    }

    public function scopeFilterByTransactionId(Builder $query, ?string $transactionId = null)
    {
        if ($transactionId) {
            $query->where('transaction_id', $transactionId);
        }

        return $query;
    }

    public function scopeSearch(Builder $query, ?string $search = null)
    {
        if ($search) {
            $search = self::normalizeSearchValue($search);

            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->where('id', intval($search));
                }

                $q->orWhere('id', 'LIKE', "%{$search}%")
                    ->orWhere('transaction_id', 'LIKE', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('last_name', 'LIKE', "%{$search}%");
                    })
                    ->orWhereHas('address', function ($q) use ($search) {
                        $q->where('receiver_name', 'LIKE', "%{$search}%");
                    })
                    ->orWhereHas('orderItems', function ($q) use ($search) {
                        $q->where('name', 'LIKE', "%{$search}%")
                            ->orWhereHas('product', function ($p) use ($search) {
                                $p->where('name', 'LIKE', "%{$search}%");
                            });
                    });
            });
        }

        return $query;
    }

    protected static function normalizeSearchValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $search = trim($value);

        $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabicDigits = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        $search = str_replace($persianDigits, $englishDigits, $search);
        $search = str_replace($arabicDigits, $englishDigits, $search);

        return $search;
    }

    public function scopeFilterByStatus(Builder $query, ?string $status = null)
    {
        if ($status) {
            $query->where('status', $status);
        }

        return $query;
    }

    public function scopeFilterByPhone(Builder $query, ?string $phone = null)
    {
        if ($phone) {
            $phone = self::normalizeSearchValue($phone);

            $query->where(function ($q) use ($phone) {
                $q->whereHas('user', function ($q) use ($phone) {
                    $q->where('phone', 'LIKE', "%{$phone}%");
                })
                    ->orWhereHas('address', function ($q) use ($phone) {
                        $q->where('receiver_phone', 'LIKE', "%{$phone}%");
                    });
            });
        }

        return $query;
    }

    public function scopeOrderByStatusPriority(Builder $query)
    {
        return $query->orderByRaw("
            CASE 
                WHEN status = 'paid' THEN 1
                WHEN status = 'boxing' THEN 2
                ELSE 3
            END
        ")->latest();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function shipping()
    {
        return $this->belongsTo(Shipping::class);
    }

    public function shippingTime()
    {
        return $this->belongsTo(ShippingTime::class);
    }

    public function gateway()
    {
        return $this->belongsTo(Gateway::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }

    public static $STATUSES = [
        'pending',
        'paid',
        'failed',
        'canceled',
        'rejected',
        'boxing',
        'sent',
        'post',
        'completed',
        'custom',
    ];

    public static $PERSIAN_STATUSES = [
        'pending' => 'در انتظار پرداخت',
        'paid' => 'موفق',
        'failed' => 'نا موفق (خطای درگاه)',
        'rejected' => 'مسترد شده',
        'canceled' => 'لغو (رها شدن خرید در مراحل پرداخت)',
        'boxing' => 'بسته بندی',
        'sent' => 'تحویل به پیک',
        'post' => 'پست',
        'completed' => 'تکمیل شده',
        'custom' => 'سفارشی',
    ];

    public static $STATUS_COLORS = [
        'pending' => '#C0C0C0', // خاکستری
        'paid' => '#80EF80', // سبز
        'failed' => '#F84F31', // قرمز
        'rejected' => '#F84F31', // قرمز
        'canceled' => '#ffd3d6', // صورتی
        'boxing' => '#0076BE', // آبی
        'sent' => '#7B52AE', // بنفش
        'post' => '#FFE20B', // زرد
        'completed' => '#033500', // مشکی
        'custom' => '#fda50f', // نارنجی
    ];

    public function getPersianStatusAttribute()
    {
        return self::$PERSIAN_STATUSES[$this->status] ?? $this->status;
    }

    public function getUserNameAttribute()
    {
        return $this->user->name;
    }

    public function getShippingNameAttribute()
    {
        return $this->shipping ? $this->shipping->title : '';
    }

    public function getShippingTimeNameAttribute()
    {
        return $this->shippingTime ? $this->shippingTime->title : '';
    }

    public function getGatewayNameAttribute()
    {
        return $this->Gateway ? $this->Gateway->title : '';
    }

    public function getGatewayColorAttribute()
    {
        return $this->Gateway ? $this->Gateway->color : '';
    }

    public function getCreatedAtJalaliAttribute()
    {
        return Jalalian::forge($this->created_at)->format('Y/m/d H:i:s');
    }

    public function getOrderColumnAttribute()
    {
        $value = $this->id.'<br/>'.$this->userName.' - '.$this->user->phone.'<br/>'.$this->createdAtJalali;

        // Check if any order item has a product with discount
        $hasDiscount = false;
        $discountPercentage = null;

        if ($this->relationLoaded('orderItems')) {
            // Ensure products are loaded if orderItems are loaded
            if ($this->orderItems->isNotEmpty() && ! $this->orderItems->first()->relationLoaded('product')) {
                $this->load('orderItems.product');
            }

            // Find first product with discount and get its discount_percentage
            foreach ($this->orderItems as $orderItem) {
                if ($orderItem->product) {
                    $product = $orderItem->product;
                    $hasProductDiscount = ($product->discounted_price && $product->discounted_price != 0)
                        || ($product->discount_percentage && $product->discount_percentage != 0);

                    if ($hasProductDiscount) {
                        $hasDiscount = true;
                        // Get discount_percentage if available
                        if ($product->discount_percentage && $product->discount_percentage != 0) {
                            $discountPercentage = $product->discount_percentage;
                            break; // Use first product with discount_percentage
                        }
                    }
                }
            }
        } else {
            // If not loaded, check with a query
            $hasDiscount = $this->orderItems()
                ->whereHas('product', function ($query) {
                    $query->where(function ($q) {
                        $q->where('discounted_price', '!=', 0)
                            ->whereNotNull('discounted_price');
                    })->orWhere('discount_percentage', '!=', 0);
                })
                ->exists();

            // Get discount_percentage from first product with discount
            if ($hasDiscount) {
                $orderItemWithDiscount = $this->orderItems()
                    ->whereHas('product', function ($query) {
                        $query->where(function ($q) {
                            $q->where('discounted_price', '!=', 0)
                                ->whereNotNull('discounted_price');
                        })->orWhere('discount_percentage', '!=', 0);
                    })
                    ->with('product')
                    ->first();

                if ($orderItemWithDiscount && $orderItemWithDiscount->product) {
                    $discountPercentage = $orderItemWithDiscount->product->discount_percentage;
                }
            }
        }

        if ($hasDiscount) {
            $discountText = "<br/><span style='color: green; font-weight: bold;'>سفارش با تخفیف</span>";
            if ($discountPercentage && $discountPercentage != 0) {
                $discountText .= ' - '.number_format($discountPercentage).'%';
            }
            $value .= $discountText;
        }

        return request()->expectsJson()
            ? $value    // JSON: plain string
            : new HtmlString($value); // Blade: safe HTML
    }

    public function getFirstImageOfOrderItemAttribute()
    {
        return $this->orderItems()->first()->product->image ?? '';
    }

    public function getFirstNameOfOrderItemAttribute()
    {
        return $this->orderItems()->first()->product->name ?? '';
    }

    public function getProductNameColAttribute()
    {
        $productName = $this->FirstNameOfOrderItem;

        // Get product from first order item (use loaded relationship if available)
        $firstOrderItem = $this->relationLoaded('orderItems')
            ? $this->orderItems->first()
            : $this->orderItems()->first();

        $product = $firstOrderItem->product ?? null;

        // Make product name clickable if product exists and has frontend URL
        if ($product && $product->frontend_url) {
            $productName = "<a href='".e($product->frontend_url)."' target='_blank' style='color: #007bff; text-decoration: none;'>".e($productName).'</a>';
        }

        $result = $productName.'<br/>'.number_format($this->final_amount).' تومان ';
        if ($this->total_amount != $this->final_amount) {
            $result = $result."<br/> <p style='color: blue'>".number_format($this->total_amount).' تومان '.'</p>';
        }

        // Add etiket codes from all order items
        $orderItems = $this->relationLoaded('orderItems')
            ? $this->orderItems
            : $this->orderItems()->get();

        $etiketCodes = [];
        foreach ($orderItems as $orderItem) {
            if (! empty($orderItem->etiket)) {
                $etiketCodes[] = $orderItem->etiket;
            }
        }

        if (! empty($etiketCodes)) {
            $etiketCodesText = implode('، ', array_unique($etiketCodes));
            $result .= "<br/><small style='color: #6c757d;'>کد اتیکت: ".e($etiketCodesText).'</small>';
        }

        // Add reference at the bottom (always show, default to "مستقیم" if null)
        $reference = $this->reference ?? 'مستقیم';
        $result .= "<br/><small style='color: #6c757d;'>منبع: ".e($reference).'</small>';

        return request()->expectsJson() ?
            $result :
            new HtmlString($result);
    }

    public function getWeightAttribute()
    {
        $weight = 0;
        foreach ($this->orderItems as $orderItem) {
            $etiket = $orderItem->resolveEtiket();
            if ($etiket) {
                $weight = $weight + $etiket->weight * $orderItem->count;
            }
        }

        return $weight;
    }

    public function getPercentageAttribute()
    {
        $firstItem = $this->orderItems()->first();

        return $firstItem?->resolveEtiket()?->darsad_kharid ?? 0;
    }

    public function getDarsadKharidAttribute()
    {
        return $this->orderItems()->first() && $this->orderItems()->first()->product ? $this->orderItems()->first()->product->darsad_kharid : 0;
    }

    public function getDarsadForooshAttribute()
    {
        $sum = 0;
        foreach ($this->orderItems as $orderItem) {
            $etiket = $orderItem->resolveEtiket();
            if ($etiket) {
                $sum = $sum + ($etiket->ojrat ?? 0);
            }
        }

        return $sum;
    }

    public function getWeightColAttribute()
    {
        $result = 'وزن : '.$this->weight.' گرم '.'<br/> خرید: '.$this->Percentage.' % '.'<br/> فروش: '.$this->DarsadForoosh.' % '.'<br/> تخفیف : '.$this->discount_percentage.' % ';

        return request()->expectsJson() ?
            $result :
            new HtmlString($result);
    }

    public function getAddressColAttribute()
    {
        $gateway = '<span style="background-color:'.e($this->gatewayColor).';border-radius:2.5rem;padding:4px">'
            .e($this->gatewayName).'</span>';

        // Handle in-store orders without address
        $addressText = $this->address ? $this->address->province->name : 'خرید حضوری';

        // Add shipping type
        $shippingText = $this->shipping ? $this->shipping->title : 'بدون ارسال';

        $result = $addressText.'<br/> نوع ارسال : '.$shippingText.'<br/> نوع پرداخت : '.$gateway;

        return request()->expectsJson()
            ? ($result) // return plain text for JSON
            : new HtmlString($result);
    }

    public function getSumCountBeforeAttribute()
    {
        return $this->user->orders()
            ->whereIn('status', $this->getSummableStatuses())
            ->where('id', '<', $this->id)
            ->count();
    }

    public function getSumFinalPriceBeforeAttribute()
    {
        return $this->user->orders()
            ->whereIn('status', $this->getSummableStatuses())
            ->where('id', '<', $this->id)
            ->sum('final_amount');
    }

    public function getSumCountAndAmountColAttribute()
    {
        $result = number_format($this->SumCountBefore).'عدد'.'<br/> '.number_format($this->SumFinalPriceBefore);

        return request()->expectsJson() ?
            $result :
            new HtmlString($result);
    }

    public function getDiscountColAttribute()
    {
        $result = $this->discount_code.'<br/> '.number_format($this->discount_price).' تومان ';

        return request()->expectsJson() ?
            $result :
            new HtmlString($result);
    }

    public function getFactorColAttribute()
    {
        $urt = route('admin_order.print', $this->uuid);
        $result = "<a href='$urt' class='btn btn-primary'>دانلود pdf</a> <a href='$urt' class='btn btn-success'>پرینت</a> ";

        return request()->expectsJson() ?
            $result :
            new HtmlString($result);
    }

    public function verify()
    {
        // No verification needed for in-store orders without gateway
        if (! $this->gateway) {
            return false;
        }

        if ($this->gateway->key == 'snapp') {
            return $this->verifySnapp();
        } elseif ($this->gateway->key == 'saman') {
            return $this->verifySaman();
        }

        return false;
    }

    //    public function verifySnapp()
    //    {
    //        $gateway = new SnappPayGateway();
    //        $verify = $gateway->verify($this->payment_token);
    //        if($verify){
    //            $this->update([
    //                'status' => 'paid'
    //            ]);
    //            $sms = new Kavehnegar();
    //            $sms->send_with_two_token($this->address->receiver_phone,$this->address->receiver_name,$this->id,$this->status);
    // //            $this->submitInAccountingApp();
    //            return true;
    //        }
    //        return false;
    //    }

    public function verifySnapp()
    {
        $gateway = new SnappPayGateway;

        // Step 1: Call verify (initial attempt)
        $verify = $gateway->verify($this->payment_token);

        // Step 2: Always check status after verify
        $response = $gateway->getStatus($this->payment_token);
        $paymentStatus = (isset($response['status']) && $response['status']) ? strtolower($response['status']) : 'pending';
        if (! isset($response['status'])) {
            // Optional: Retry verify once more
            $verify = $gateway->verify($this->payment_token);
            $status = $gateway->getStatus($this->payment_token);
            $paymentStatus = strtolower($status['status'] ?? 'pending');
        }

        if ($paymentStatus === 'verify') {
            // Try to settle
            $settle = $gateway->settle($this->payment_token);

            $status = $gateway->getStatus($this->payment_token);
            $paymentStatus = strtolower($status['status'] ?? 'pending');
            if ($paymentStatus === 'settle') {
                $this->markAsPaid();

                return true;
            } else {
                // Check status again if settle failed
                $status = $gateway->getStatus($this->payment_token);
                $paymentStatus = strtolower($status['response']['status'] ?? '');
                if ($paymentStatus === 'settle') {
                    $this->markAsPaid();

                    return true;
                }
            }
        } elseif ($paymentStatus === 'settle') {
            // Already settled → mark as paid
            $this->markAsPaid();

            return true;
        }

        // Any other case → not paid yet
        return false;
    }

    public function verifySaman()
    {
        if (! $this->payment_token) {
            Log::warning('Saman: No payment token found for order', ['order_id' => $this->id]);

            return false;
        }

        $gateway = new SamanGateway;

        // Amount in Rials (order stores in Tomans, so multiply by 10)
        $amount = $this->final_amount * 10;

        try {
            $verify = $gateway->verifyByToken($this->payment_token, $amount);

            if ($verify['success']) {
                $this->markAsPaid();

                return true;
            }

            Log::warning('Saman: Payment verification failed', [
                'order_id' => $this->id,
                'verify' => $verify,
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('Saman: Error verifying payment', [
                'order_id' => $this->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public static function adminSmsRecipientPhones(): array
    {
        return [
            '09127127053',
            '09193106488',
        ];
    }

    /**
     * Normalizes mobiles for Kavenegar verify/template calls (expects 09xxxxxxxxx-style receptor).
     */
    public static function normalizePhoneForSms(?string $phone): ?string
    {
        if ($phone === null || $phone === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', trim($phone));

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 12 && str_starts_with($digits, '98')) {
            return '0'.substr($digits, 2);
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '09')) {
            return $digits;
        }

        if (strlen($digits) === 10 && $digits[0] === '9') {
            return '0'.$digits;
        }

        return $digits;
    }

    /**
     * Statuses for which inventory (etikets) is committed against the catalog.
     */
    public static function inventoryCommittedStatuses(): array
    {
        return [
            self::$STATUSES[1], // paid
            self::$STATUSES[5], // boxing
            self::$STATUSES[6], // sent
            self::$STATUSES[7], // post
            self::$STATUSES[8], // completed
        ];
    }

    public function commitsInventory(): bool
    {
        return in_array($this->status, self::inventoryCommittedStatuses(), true);
    }

    /**
     * Send SMS notification to admins about new order
     */
    public function notifyAdminsNewOrder(): void
    {
        $this->loadMissing('user');

        $sms = new Kavehnegar;
        $userName = str_replace(' ', '_', (string) ($this->user->name ?? 'کاربر'));
        $orderAmount = number_format((float) $this->final_amount).' تومان';

        foreach (self::adminSmsRecipientPhones() as $phone) {
            $to = self::normalizePhoneForSms($phone) ?? $phone;

            try {
                $sms->send_with_two_token($to, $userName, $orderAmount, 'notifyAdminNewOrder');
            } catch (\Throwable $e) {
                Log::error('Failed to send admin SMS notification', [
                    'order_id' => $this->id,
                    'phone' => $phone,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Notify admins when a product from this order has no sellable etikets left (after stock update).
     */
    public function notifyAdminsIfAffectedProductsFullyOutOfStock(): void
    {
        $this->loadMissing('orderItems');

        $productIds = $this->orderItems->pluck('product_id')->unique()->filter();

        if ($productIds->isEmpty()) {
            return;
        }

        $products = Product::query()
            ->whereIn('id', $productIds->all())
            ->with([
                'etikets.comprehensiveEtikets.relatedEtiket',
                'children.etikets.comprehensiveEtikets.relatedEtiket',
                'products.etikets.comprehensiveEtikets.relatedEtiket',
            ])
            ->get()
            ->keyBy('id');

        foreach ($productIds as $productId) {
            $product = $products->get($productId);
            if (! $product) {
                continue;
            }

            $candidateEtikets = $product->candidateEtiketsForAvailability();
            if ($candidateEtikets->isEmpty()) {
                continue;
            }

            $stillSellable = $candidateEtikets->contains(function (Etiket $etiket): bool {
                return (int) $etiket->effective_is_mojood === 1;
            });
            if ($stillSellable) {
                continue;
            }

            $sms = new Kavehnegar;
            $productNameToken = str_replace(' ', '_', $product->name ?? 'محصول');

            foreach (self::adminSmsRecipientPhones() as $phone) {
                $to = self::normalizePhoneForSms($phone) ?? $phone;
                try {
                    $sms->send_with_pattern($to, $productNameToken, 'notifyAdminProductNotAvailable');
                } catch (\Throwable $e) {
                    Log::warning('notifyAdminProductNotAvailable SMS failed', [
                        'product_id' => $productId,
                        'order_id' => $this->id,
                        'phone' => $phone,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('notifyAdminProductNotAvailable sent — product fully unavailable', [
                'product_id' => $productId,
                'order_id' => $this->id,
            ]);
        }
    }

    /**
     * Mark order as paid and send SMS
     */
    public function markAsPaid(): void
    {
        if ($this->status === self::$STATUSES[1]) {
            return;
        }

        if (! $this->orderEtiketsAreAvailableForPayment()) {
            Log::warning('markAsPaid rejected: etiket already sold or reserved by another order', [
                'order_id' => $this->id,
            ]);

            return;
        }

        $this->update([
            'status' => self::$STATUSES[1],
        ]);

        $this->markOrderItemsOutOfStock(true);

        // Clear shopping cart items when order is verified/paid
        $this->user->shoppingCartItems()->delete();

        // Use user's phone and name for SMS
        $this->sendSmsNotifications();

        // $this->submitInAccountingApp(); // Uncomment if needed

        // Check and generate gift discount code
        $this->checkAndGenerateGift();

        $this->notifyAdminsNewOrder();
    }

    /**
     * Check and generate gift discount code if applicable
     */
    public function checkAndGenerateGift()
    {
        try {
            // Check and generate gift using static method
            $discount = GiftStructure::checkAndGenerateGift($this);

            if ($discount) {
                // Determine discount type for logging
                $discountType = $discount->percentage
                    ? "percentage ({$discount->percentage}%)"
                    : 'amount ('.number_format($discount->amount).' تومان)';

                // Log the gift code generation
                Log::info('Gift code generated', [
                    'order_id' => $this->id,
                    'user_id' => $this->user_id,
                    'discount_code' => $discount->code,
                    'discount_type' => $discountType,
                    'expires_at' => $discount->expires_at,
                ]);

                // Optional: Send SMS with gift code using user's phone and name
                $sms = new Kavehnegar;
                $sms->send_with_pattern($this->user->phone, $this->user->name, 'gift');
            }
        } catch (\Exception $e) {
            Log::error('Failed to generate gift code', [
                'order_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function status()
    {
        if (! $this->gateway) {
            return null;
        }

        return $this->gateway->status($this->payment_token);
    }

    public function cancel()
    {
        if (! $this->gateway) {
            return false;
        }

        return $this->gateway->cancel($this->payment_token);
    }

    public function settle()
    {
        if (! $this->gateway) {
            return false;
        }

        return $this->gateway->settle($this->payment_token);
    }

    public function updateSnappTransaction()
    {
        if (! $this->gateway) {
            return false;
        }

        return $this->gateway->updateSnappTransaction(Order::find($this->id));
    }

    public static function generateUniqueTransactionId()
    {
        do {
            // Generate a random 10-digit number
            $transactionId = mt_rand(1000000000, 9999999999);
        } while (self::where('transaction_id', $transactionId)->exists());

        return $transactionId;
    }

    public function submitInAccountingApp()
    {
        return 0;
    }

    public function cancelOrder()
    {
        $accounting_app = new Tahesab;
        $transaction_id = '0000000000'.$this->transaction_id;

        return $accounting_app->DoDeleteSanad($transaction_id);
    }

    public function getShippingPriceAttribute()
    {
        $shippingPrice = 0;
        if ($this->shipping) {
            if ($this->shipping->price) {
                $shippingPrice = $this->shipping->price;
            }
        }

        return $shippingPrice;
    }

    public function getFinalPriceAttribute()
    {
        return ($this->total_amount + $this->shippingPrice) - $this->discount_price;
    }

    public function markOrderItemsOutOfStockIfPaid(): void
    {
        if ($this->commitsInventory()) {
            $this->markOrderItemsOutOfStock(true);
        }
    }

    /**
     * Reserve etikets when the user enters payment (pending order). Writes cache + sets is_mojood = 0.
     */
    public function reserveOrderEtiketsForPayment(int $userId, int $ttlSeconds = Etiket::PAYMENT_RESERVATION_TTL_SECONDS): void
    {
        $this->loadMissing('orderItems');

        foreach ($this->orderItems as $item) {
            if (! $item->etiket || self::isNonReservableEtiketCode($item->etiket)) {
                continue;
            }

            $cacheKey = 'reserved_etiket_'.$item->etiket;
            $existing = Cache::get($cacheKey);
            if ($existing !== null && $existing !== $userId && $existing !== true) {
                continue;
            }

            Cache::put($cacheKey, $userId, $ttlSeconds);

            foreach ($this->resolveEtiketsForStockTransition($item->etiket, (bool) $this->has_comprehensive_etiket, $item->product_id) as $etiket) {
                if (! ($etiket->orderable_after_out_of_stock ?? false)) {
                    $etiket->update(['is_mojood' => 0]);
                }
            }
        }
    }

    /**
     * Whether this pending order can still be paid (no etiket sold in another committed order).
     */
    public function orderEtiketsAreAvailableForPayment(): bool
    {
        $this->loadMissing('orderItems');

        foreach ($this->orderItems as $item) {
            if (! $item->etiket || self::isNonReservableEtiketCode($item->etiket)) {
                continue;
            }

            if (self::etiketIsSoldInCommittedOrder($item->etiket, $this->id)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether another order already holds this etiket (pending or paid/committed).
     */
    public static function etiketIsHeldByAnotherOrder(string $etiketCode, int $exceptOrderId): bool
    {
        return OrderItem::query()
            ->where('etiket', $etiketCode)
            ->where('order_id', '!=', $exceptOrderId)
            ->whereHas('order', function ($query) {
                $query->whereIn('status', array_merge(
                    [self::$STATUSES[0]],
                    self::inventoryCommittedStatuses()
                ));
            })
            ->exists();
    }

    public static function etiketIsSoldInCommittedOrder(string $etiketCode, ?int $exceptOrderId = null): bool
    {
        $query = OrderItem::query()
            ->where('etiket', $etiketCode)
            ->whereHas('order', function ($q) {
                $q->whereIn('status', self::inventoryCommittedStatuses());
            });

        if ($exceptOrderId !== null) {
            $query->where('order_id', '!=', $exceptOrderId);
        }

        return $query->exists();
    }

    public static function isNonReservableEtiketCode(?string $code): bool
    {
        return $code !== null && str_starts_with($code, 's-');
    }

    public function markOrderItemsOutOfStock(bool $notifyAdminsWhenProductFullyUnavailable = false): void
    {
        $this->loadMissing('orderItems');

        foreach ($this->orderItems as $item) {
            if (! $item->etiket) {
                continue;
            }

            foreach ($this->resolveEtiketsForStockTransition($item->etiket, (bool) $this->has_comprehensive_etiket, $item->product_id) as $etiket) {
                // Only set is_mojood to 0 if orderable_after_out_of_stock is not 1
                if (! ($etiket->orderable_after_out_of_stock ?? false)) {
                    $etiket->update(['is_mojood' => 0]);
                }

                Cache::forget('reserved_etiket_'.$etiket->code);
            }
        }

        if ($notifyAdminsWhenProductFullyUnavailable) {
            $this->notifyAdminsIfAffectedProductsFullyOutOfStock();
        }
    }

    /**
     * Restore etikets on this order to available (is_mojood = 1). Used when order is canceled or rejected.
     */
    public function restoreOrderEtiketsAvailability(): void
    {
        $this->loadMissing('orderItems');

        foreach ($this->orderItems as $item) {
            if (! $item->etiket) {
                continue;
            }

            foreach ($this->resolveEtiketsForStockTransition($item->etiket, (bool) $this->has_comprehensive_etiket, $item->product_id) as $etiket) {
                if (self::etiketIsHeldByAnotherOrder($etiket->code, $this->id)) {
                    continue;
                }

                if (self::etiketIsSoldInCommittedOrder($etiket->code, $this->id)) {
                    continue;
                }

                $etiket->update(['is_mojood' => 1]);
                Cache::forget('reserved_etiket_'.$etiket->code);
            }
        }
    }

    /**
     * Resolve all etikets that should transition stock state for an order-item etiket code.
     * Includes:
     * - the etiket itself
     * - related real etikets when the etiket is comprehensive
     * - parent comprehensive etikets that include this real etiket
     */
    private function resolveEtiketsForStockTransition(string $etiketCode, bool $includeParentComprehensive = false, ?int $productId = null)
    {
        $baseQuery = Etiket::query()
            ->with(['comprehensiveEtikets.relatedEtiket'])
            ->where('code', $etiketCode);

        if ($productId !== null) {
            $baseQuery->where('product_id', $productId);
        }

        $baseEtiket = $baseQuery->first();

        if (! $baseEtiket) {
            return collect();
        }

        $allEtikets = collect([$baseEtiket]);

        if ($baseEtiket->type === 'comprehensive') {
            $relatedRealEtikets = $baseEtiket->comprehensiveEtikets
                ->pluck('relatedEtiket')
                ->filter();
            $allEtikets = $allEtikets->concat($relatedRealEtikets);
        } elseif ($includeParentComprehensive) {
            $parentComprehensiveEtikets = Etiket::query()
                ->whereHas('comprehensiveEtikets', function ($query) use ($baseEtiket) {
                    $query->where('related_etiket_id', $baseEtiket->id);
                })
                ->get();
            $allEtikets = $allEtikets->concat($parentComprehensiveEtikets);
        }

        return $allEtikets->unique('id')->values();
    }

    protected function getSummableStatuses(): array
    {
        return [
            self::$STATUSES[1], // paid
            self::$STATUSES[5], // boxing
            self::$STATUSES[6], // sent
            self::$STATUSES[7], // post
            self::$STATUSES[8], // completed
        ];
    }

    /**
     * Persist printable invoice headers and per-line product/etiket display fields (PDF uses snapshots only).
     */
    public function refreshInvoiceSnapshot(): void
    {
        $this->loadMissing([
            'user',
            'address.province',
            'address.city',
            'shipping',
            'shippingTime',
            'gateway',
        ]);

        $addressHtml = '';
        if ($this->address) {
            $addressHtml = '';
            if ($this->address->province) {
                $addressHtml = $this->address->province->name ?? '';
            }
            if ($this->address->city) {
                $addressHtml = $addressHtml !== ''
                    ? $addressHtml.' - '.$this->address->city->name
                    : ($this->address->city->name ?? '');
            }
            $street = $this->address->address ?? '';
            $addressHtml = ($addressHtml !== '' ? $addressHtml.' - ' : '').$street;

            if (mb_strlen($addressHtml, 'UTF-8') > 90) {
                $segments = preg_split('/(?<=\G.{90})/u', $addressHtml, -1, PREG_SPLIT_NO_EMPTY);
                $addressHtml = implode('<br>', $segments);
            }
        }

        $shipping = $this->shipping?->title ?? 'آنلاین';
        if ($this->shipping_time_id && $this->shippingTime) {
            $shippingTimeTitle = $this->shippingTime->title;
            if ($this->shipping_date) {
                $persianDayNames = [
                    'یکشنبه',
                    'دوشنبه',
                    'سه‌شنبه',
                    'چهارشنبه',
                    'پنج‌شنبه',
                    'جمعه',
                    'شنبه',
                ];
                $jalali = Jalalian::forge($this->shipping_date);
                $dayOfWeek = Carbon::parse($this->shipping_date)->dayOfWeek;
                $shippingDateText = $jalali->format('Y/m/d').' ('.($persianDayNames[$dayOfWeek] ?? '').')';
                $shipping = $shipping.'<br>'.$shippingDateText.'<br>'.$shippingTimeTitle;
            } else {
                $shipping = $shipping.'<br>'.$shippingTimeTitle;
            }
        }

        $gatewayName = '';
        if ($this->gateway) {
            $gatewayName = $this->gateway->title ?? '';
        }

        $previousPurchaseCount = self::query()
            ->where('user_id', '=', $this->user_id)
            ->where('id', '!=', $this->id)
            ->count();

        $sumOfPrevPurchases = self::query()
            ->where('user_id', '=', $this->user_id)
            ->where('id', '!=', $this->id)
            ->sum('final_amount');

        $this->invoice_snapshot = [
            'receiver_name' => $this->user?->name ?? '',
            'receiver_phone' => $this->user?->phone ?? '',
            'postal_code' => $this->address?->postal_code ?? '',
            'address_html' => $addressHtml,
            'shipping_html' => $shipping,
            'gateway_name' => $gatewayName,
            'previous_purchase_count' => $previousPurchaseCount,
            'sum_of_prev_purchases' => number_format((float) $sumOfPrevPurchases),
        ];

        $this->saveQuietly();

        $items = OrderItem::query()->where('order_id', $this->id)->orderBy('id')->get();

        foreach ($items as $orderItem) {
            $img = asset('img/no_image.jpg');
            if ($orderItem->product_id) {
                $product = Product::withTrashed()->find($orderItem->product_id);
                if ($product) {
                    $img = $product->image;
                }
            }

            $etiket = $orderItem->resolveEtiket();
            $weight = $etiket?->weight;
            $weightStr = $weight !== null ? (string) $weight : '';

            OrderItem::query()->where('id', $orderItem->id)->update([
                'invoice_product_image' => $img,
                'invoice_weight' => $weightStr,
                'invoice_ayar' => '18',
            ]);
        }
    }

    /**
     * پیامک الگوی دو توکن وضعیت سفارش برای خریدار (بدون قالب sefareshiproduct).
     */
    public function sendBuyerOrderStatusTwoTokenSms(): void
    {
        $this->loadMissing('user');

        $sms = new Kavehnegar;
        $userName = str_replace(' ', '_', (string) ($this->user->name ?? 'کاربر'));
        $buyerPhone = self::normalizePhoneForSms($this->user->phone ?? null);
        if ($buyerPhone !== null) {
            $sms->send_with_two_token($buyerPhone, $userName, $this->id, $this->status);
        }
    }

    /**
     * وضعیت + در صورت واجد شرایط، پیامک سفارش محصول جامع (s-).
     */
    public function sendSmsNotifications(): void
    {
        $this->sendBuyerOrderStatusTwoTokenSms();
        $this->sendComprehensiveEtiketProductSmsIfApplicable();
    }

    /**
     * Whether this order triggers the sefareshiproduct SMS: هر خطی که فیلد اتیکتش با "s-" شروع شود.
     */
    public function orderHasSPrefixedComprehensiveEtiket(): bool
    {
        $this->loadMissing('orderItems');

        return $this->orderItems
            ->pluck('etiket')
            ->filter()
            ->contains(fn ($code): bool => str_starts_with((string) $code, 's-'));
    }

    /**
     * Notify customer when the order includes an etiket code starting with "s-" (Kavenegar template sefareshiproduct).
     */
    public function sendComprehensiveEtiketProductSmsIfApplicable(): void
    {
        if (! $this->orderHasSPrefixedComprehensiveEtiket()) {
            return;
        }

        $this->loadMissing('user');

        $phone = self::normalizePhoneForSms($this->user->phone ?? null);
        if ($phone === null || $phone === '') {
            return;
        }

        $sms = new Kavehnegar;
        $customerNameToken = str_replace(' ', '_', (string) ($this->user->name ?? 'کاربر'));
        $sms->send_with_pattern($phone, $customerNameToken, 'sefareshiproduct');
    }

    /**
     * Send Najva notifications for all products in the order
     */
    public function sendNajvaNotifications(): void
    {
        try {
            Log::info('Najva notifications: Starting', [
                'order_id' => $this->id,
                'status' => $this->status,
            ]);

            // Load order items with product and user relationships
            $this->loadMissing('orderItems.product', 'user');

            if (! $this->user || ! $this->orderItems || $this->orderItems->isEmpty()) {
                Log::info('Najva notifications skipped: No user or order items', [
                    'order_id' => $this->id,
                    'has_user' => ! is_null($this->user),
                    'user_id' => $this->user_id,
                    'order_items_count' => $this->orderItems ? $this->orderItems->count() : 0,
                ]);

                return;
            }

            $najvaService = new NajvaService;
            $userName = $this->user->name ?? '';
            $userPhone = $this->user->phone ?? '';

            if (empty($userPhone)) {
                Log::info('Najva notifications skipped: Empty user phone', [
                    'order_id' => $this->id,
                    'user_id' => $this->user_id,
                ]);

                return;
            }

            Log::info('Najva notifications: Processing order items', [
                'order_id' => $this->id,
                'order_items_count' => $this->orderItems->count(),
                'user_phone' => $userPhone,
                'user_name' => $userName,
            ]);

            // Send notification for each product in the order
            foreach ($this->orderItems as $orderItem) {
                if ($orderItem->product) {
                    // Build request array
                    $request = [
                        'order_id' => $this->id,
                        'order_item_id' => $orderItem->id,
                        'product_id' => $orderItem->product_id,
                        'product_name' => $orderItem->product->name ?? null,
                        'phone_number' => $userPhone,
                        'user_name' => $userName,
                        'event' => 'Buy Product',
                    ];

                    // Send notification and capture response
                    $response = $najvaService->sendBuyProductNotification(
                        $userPhone,
                        $userName,
                        $orderItem->product_id
                    );

                    // Log request and response
                    Log::info('Najva notification sent', [
                        'request' => $request,
                        'response' => $response ? [
                            'status' => $response->status(),
                            'body' => $response->body(),
                            'json' => $response->json(),
                        ] : null,
                    ]);
                } else {
                    Log::warning('Najva notifications: Order item has no product', [
                        'order_id' => $this->id,
                        'order_item_id' => $orderItem->id,
                        'product_id' => $orderItem->product_id,
                    ]);
                }
            }

            Log::info('Najva notifications: Completed', [
                'order_id' => $this->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Najva notifications: Error occurred', [
                'order_id' => $this->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
