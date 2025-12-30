<?php

namespace App\Models;

use App\Services\Api\Tahesab;
use App\Services\PaymentGateways\SamanGateway;
use App\Services\PaymentGateways\SnappPayGateway;
use App\Services\SMS\Kavehnegar;
use App\Services\NajvaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Morilog\Jalali\Jalalian;
use App\Models\Etiket;

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
        'shipping_date'
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'shipping_date' => 'datetime',
        'deleted_at' => 'datetime',
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

    public function scopeFilterByTransactionId(Builder $query, string $transactionId = null)
    {
        if($transactionId){
            $query->where('transaction_id', $transactionId);
        }
        return $query;
    }

    public function scopeSearch(Builder $query, string $search = null)
    {
        if($search){
            $search = self::normalizeSearchValue($search);

            $query->where(function($q) use ($search) {
                if (is_numeric($search)) {
                    $q->where('id', intval($search));
                }

                $q->orWhere('id', 'LIKE', "%{$search}%")
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

        $persianDigits  = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $arabicDigits   = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
        $englishDigits  = ['0','1','2','3','4','5','6','7','8','9'];

        $search = str_replace($persianDigits, $englishDigits, $search);
        $search = str_replace($arabicDigits, $englishDigits, $search);

        return $search;
    }

    public function scopeFilterByStatus(Builder $query, string $status = null)
    {
        if($status){
            $query->where('status', $status);
        }
        return $query;
    }

    public function scopeFilterByPhone(Builder $query, string $phone = null)
    {
        if($phone){
            $phone = self::normalizeSearchValue($phone);
            
            $query->where(function($q) use ($phone) {
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
    public function user(){
        return $this->belongsTo(User::class);
    }
    public function address(){
        return $this->belongsTo(Address::class);
    }
    public function shipping(){
        return $this->belongsTo(Shipping::class);
    }
    public function shippingTime(){
        return $this->belongsTo(ShippingTime::class);
    }
    public function gateway(){
        return $this->belongsTo(Gateway::class);
    }
    public function orderItems(){
        return $this->hasMany(OrderItem::class,'order_id','id');
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
    ];

    public static $PERSIAN_STATUSES = [
        'pending'   => 'در انتظار پرداخت',
        'paid'      => 'موفق',
        'failed'    => 'نا موفق (خطای درگاه)',
        'rejected'    => 'مسترد شده',
        'canceled'  => 'لغو (رها شدن خرید در مراحل پرداخت)',
        'boxing'    => 'بسته بندی',
        'sent'      => 'تحویل به پیک',
        'post'      => 'پست',
        'completed' => 'تکمیل شده',
    ];

    public static $STATUS_COLORS = [
        'pending'   => '#C0C0C0', // خاکستری
        'paid'      => '#80EF80', // سبز
        'failed'    => '#F84F31', // قرمز
        'rejected'    => '#F84F31', // قرمز
        'canceled'  => '#ffd3d6', // صورتی
        'boxing'    => '#0076BE', // آبی
        'sent'      => '#7B52AE', // بنفش
        'post'      => '#FFE20B', // زرد
        'completed' => '#033500', // مشکی
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
        $value = $this->id . "<br/>" . $this->userName.' - '.$this->user->phone . "<br/>" . $this->createdAtJalali;

        // Check if any order item has a product with discount
        $hasDiscount = false;
        $discountPercentage = null;
        
        if ($this->relationLoaded('orderItems')) {
            // Ensure products are loaded if orderItems are loaded
            if ($this->orderItems->isNotEmpty() && !$this->orderItems->first()->relationLoaded('product')) {
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
                $discountText .= " - " . number_format($discountPercentage) . "%";
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
            $productName = "<a href='" . e($product->frontend_url) . "' target='_blank' style='color: #007bff; text-decoration: none;'>" . e($productName) . "</a>";
        }
        
        $result = $productName . "<br/>" . number_format($this->final_amount)." تومان ";
        if($this->total_amount != $this->final_amount){
            $result = $result."<br/> <p style='color: blue'>" . number_format($this->total_amount)." تومان "."</p>";
        }
        
        // Add etiket codes from all order items
        $orderItems = $this->relationLoaded('orderItems') 
            ? $this->orderItems 
            : $this->orderItems()->get();
        
        $etiketCodes = [];
        foreach ($orderItems as $orderItem) {
            if (!empty($orderItem->etiket)) {
                $etiketCodes[] = $orderItem->etiket;
            }
        }
        
        if (!empty($etiketCodes)) {
            $etiketCodesText = implode('، ', array_unique($etiketCodes));
            $result .= "<br/><small style='color: #6c757d;'>کد اتیکت: " . e($etiketCodesText) . "</small>";
        }
        
        // Add reference at the bottom (always show, default to "مستقیم" if null)
        $reference = $this->reference ?? 'مستقیم';
        $result .= "<br/><small style='color: #6c757d;'>منبع: " . e($reference) . "</small>";
        
        return request()->expectsJson() ?
            $result :
            new HtmlString($result );
    }

    public function getWeightAttribute()
    {
        $weight = 0;
        foreach ($this->orderItems as $orderItem) {
            if($orderItem->product){
                $weight = $weight + $orderItem->product->weight * $orderItem->count;
            }
        }
        return $weight;
    }
    public function getPercentageAttribute()
    {
        return $this->orderItems()->first()->product->darsad_kharid ?? 0;
    }

    public function getDarsadKharidAttribute()
    {
        return $this->orderItems()->first() && $this->orderItems()->first()->product ? $this->orderItems()->first()->product->darsad_kharid: 0 ;
    }
    public function getDarsadForooshAttribute()
    {
        $sum = 0;
        foreach ($this->orderItems as $orderItem) {
            if($orderItem->product){
                $sum = $sum + $orderItem->product->darsad_vazn_foroosh;
            }
        }
        return $sum;
    }
    public function getWeightColAttribute()
    {
        $result = "وزن : ".$this->weight ." گرم ". "<br/> خرید: " . $this->Percentage." % "."<br/> فروش: ".$this->DarsadForoosh." % "."<br/> تخفیف : ".$this->discount_percentage." % ";
        return request()->expectsJson() ?
            $result :
            new HtmlString($result);
    }

    public function getAddressColAttribute()
    {
        $gateway = '<span style="background-color:' . e($this->gatewayColor) . ';border-radius:2.5rem;padding:4px">'
            . e($this->gatewayName) . '</span>';

        // Handle in-store orders without address
        $addressText = $this->address ? $this->address->province->name : 'خرید حضوری';
        
        // Add shipping type
        $shippingText = $this->shipping ? $this->shipping->title : 'بدون ارسال';
        
        $result = $addressText . "<br/> نوع ارسال : " . $shippingText . "<br/> نوع پرداخت : " . $gateway;

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
        $result =    number_format($this->SumCountBefore) ."عدد". "<br/> " . number_format($this->SumFinalPriceBefore);

        return request()->expectsJson() ?
            $result :
            new HtmlString($result);
    }
    public function getDiscountColAttribute()
    {
        $result =  $this->discount_code . "<br/> " . number_format($this->discount_price).' تومان ';

        return request()->expectsJson() ?
            $result :
            new HtmlString($result);
    }
    public function getFactorColAttribute()
    {
        $urt = route('admin_order.print', $this->uuid);
        $result =  "<a href='$urt' class='btn btn-primary'>دانلود pdf</a> <a href='$urt' class='btn btn-success'>پرینت</a> ";

        return request()->expectsJson() ?
            $result :
            new HtmlString($result);
    }

    public function verify()
    {
        // No verification needed for in-store orders without gateway
        if(!$this->gateway) {
            return false;
        }
        
        if($this->gateway->key == 'snapp'){
            return $this->verifySnapp();
        } elseif($this->gateway->key == 'saman'){
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
////            $this->submitInAccountingApp();
//            return true;
//        }
//        return false;
//    }

    public function verifySnapp()
    {
        $gateway = new SnappPayGateway();

        // Step 1: Call verify (initial attempt)
        $verify = $gateway->verify($this->payment_token);

        // Step 2: Always check status after verify
        $response = $gateway->getStatus($this->payment_token);
        $paymentStatus = (isset($response['status']) && $response['status'] ) ? strtolower($response['status']) : 'pending' ;
        if ( !isset($response['status']) ) {
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
        if (!$this->payment_token) {
            Log::warning('Saman: No payment token found for order', ['order_id' => $this->id]);
            return false;
        }

        $gateway = new SamanGateway();
        
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

    /**
     * Send SMS notification to admins about new order
     */
    public function notifyAdminsNewOrder()
    {
        $adminNumbers = [
            '09127127053',
            '09193106488'
        ];
        
        $sms = new Kavehnegar();
        $userName = $this->user->name ?? 'کاربر';
        $orderAmount = number_format($this->final_amount);
        
        foreach ($adminNumbers as $phone) {
            $sms->send_with_two_token($phone, $userName, $orderAmount, 'notifyAdminNewOrder');
        }
    }

    /**
     * Mark order as paid and send SMS
     */
    public function markAsPaid()
    {
        $this->update([
            'status' => 'paid'
        ]);

        $this->markOrderItemsOutOfStock();

        // Clear shopping cart items when order is verified/paid
        $this->user->shoppingCartItems()->delete();

        // Use user's phone and name for SMS
        $sms = new Kavehnegar();
        $sms->send_with_two_token(
            $this->user->phone,
            $this->user->name,
            $this->id,
            $this->status
        );

        $this->submitInAccountingApp(); // Uncomment if needed
        
        // Check and generate gift discount code
        $this->checkAndGenerateGift();
        
        // Notify admins about new paid order
        // $this->notifyAdminsNewOrder();
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
                    : "amount (" . number_format($discount->amount) . " تومان)";
                
                // Log the gift code generation
                Log::info('Gift code generated', [
                    'order_id' => $this->id,
                    'user_id' => $this->user_id,
                    'discount_code' => $discount->code,
                    'discount_type' => $discountType,
                    'expires_at' => $discount->expires_at,
                ]);
                
                // Optional: Send SMS with gift code using user's phone and name
                $sms = new Kavehnegar();
                $sms->send_with_pattern($this->user->phone, $this->user->name, 'gift');
            }
        } catch (\Exception $e) {
            Log::error('Failed to generate gift code', [
                'order_id' => $this->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function status()
    {
        if(!$this->gateway) {
            return null;
        }
        return $this->gateway->status($this->payment_token);
    }
    
    public function cancel()
    {
        if(!$this->gateway) {
            return false;
        }
        return $this->gateway->cancel($this->payment_token);
    }
    
    public function settle()
    {
        if(!$this->gateway) {
            return false;
        }
        return $this->gateway->settle($this->payment_token);
    }
    
    public function updateSnappTransaction()
    {
        if(!$this->gateway) {
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
        $accounting_app = new Tahesab();
        $final_amount = $this->final_amount;
        $allSuccessful = true;
        $transaction_id = "0000000000".$this->transaction_id;

        // Get receiver name (use user's name if address is null for in-store orders)
        $receiverName = $this->address ? $this->address->receiver_name : $this->user->name;
        
        // Collect responses from each order item
        foreach ($this->orderItems as $orderItem) {
            Log::info('Submitting order item to accounting', [
                'order_id' => $this->id,
                'transaction_id' => $transaction_id,
                'etiket' => $orderItem->etiket
            ]);
            
            $response = $accounting_app->DoNewSanadBuySaleEtiket(
                $transaction_id,
                $orderItem->etiket,
                $orderItem->product->mazaneh,
                $orderItem->price,
                $receiverName
            );
            
            // Check if response has error
            // API returns ['error' => true, 'status' => ..., 'message' => ...] on failure
            if (isset($response['error']) && $response['error'] === true) {
                $allSuccessful = false;
                $sms = new Kavehnegar();
                $sms->send_with_two_token('09127127053',$orderItem->etiket,$this->id,'notifyAdminEtiketFailedOnTahesabAct');
                \Log::warning('Accounting API call failed for order item', [
                    'order_id' => $this->id,
                    'order_item_id' => $orderItem->id,
                    'etiket' => $orderItem->etiket,
                    'response' => $response
                ]);
            }
        }

        // Only proceed with shipping and gateway if all order items were successful
        if ($allSuccessful) {
            // Check shipping if exists (null for in-store orders)
            if($this->shipping && $this->shipping->key == 'post'){
//                $final_amount = $final_amount + 150000;
                $accounting_app->DoNewSanadTalabBedehi($transaction_id,1,150000,0,1,"POST");
            }
            
            // Check gateway if exists (null for in-store orders)
            if($this->gateway && $this->gateway->key == 'snapp'){
                 $accounting_app->DoNewSanadTalabBedehi($transaction_id,0,$final_amount,210,1,"Snapp");
            }
            elseif($this->gateway && $this->gateway->key == 'digipay'){
                 $accounting_app->DoNewSanadTalabBedehi($transaction_id,0,$final_amount,3330,1,"Digipay");
            }
            elseif($this->gateway && $this->gateway->key == 'saman'){
                $accounting_app->DoNewSanadVKHBank($transaction_id,0,$final_amount,"ملي",1,1,'Saman');
            }else{
                $accounting_app->DoNewSanadVKHBank($transaction_id,0,$final_amount,"ملي",1,1,'Hozoori');
            }
        } else {
            \Log::error('Skipping shipping and gateway accounting entries due to failed order item entries', [
                'order_id' => $this->id
            ]);
        }
        
        return $allSuccessful;
    }

    public function cancelOrder()
    {
        $accounting_app = new Tahesab();
        $transaction_id = "0000000000".$this->transaction_id;
        return $accounting_app->DoDeleteSanad($transaction_id);
    }

    public function getShippingPriceAttribute()
    {
        $shippingPrice = 0;
        if($this->shipping){
            if($this->shipping->price){
                $shippingPrice = $this->shipping->price;
            }
        }
        return $shippingPrice;
    }

    public function getFinalPriceAttribute()
    {
        return ( $this->total_amount + $this->shippingPrice ) - $this->discount_price ;
    }

    public function markOrderItemsOutOfStockIfPaid(): void
    {
        if (in_array($this->status, [
            self::$STATUSES[1], // paid
            self::$STATUSES[5], // boxing
            self::$STATUSES[6], // sent
            self::$STATUSES[7], // post
            self::$STATUSES[8], // completed
        ], true)) {
            $this->markOrderItemsOutOfStock();
        }
    }

    public function markOrderItemsOutOfStock(): void
    {
        $this->loadMissing('orderItems');

        foreach ($this->orderItems as $item) {
            if (!$item->etiket) {
                continue;
            }

            // Find the etiket
            $etiket = Etiket::where('product_id', $item->product_id)
                ->where('code', $item->etiket)
                ->first();
            
            if (!$etiket) {
                continue;
            }
            
            // Only set is_mojood to 0 if orderable_after_out_of_stock is not 1
            if (!($etiket->orderable_after_out_of_stock ?? false)) {
                $etiket->update(['is_mojood' => 0]);
            }
        }
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

            if (!$this->user || !$this->orderItems || $this->orderItems->isEmpty()) {
                Log::info('Najva notifications skipped: No user or order items', [
                    'order_id' => $this->id,
                    'has_user' => !is_null($this->user),
                    'user_id' => $this->user_id,
                    'order_items_count' => $this->orderItems ? $this->orderItems->count() : 0,
                ]);
                return;
            }

            $najvaService = new NajvaService();
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
