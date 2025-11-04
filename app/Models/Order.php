<?php

namespace App\Models;

use App\Services\Api\Tahesab;
use App\Services\PaymentGateways\SnappPayGateway;
use App\Services\SMS\Kavehnegar;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
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
        'shipping_price'
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

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
            $query->where(function($q) use ($search) {
                $q->where('id', 'LIKE', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'LIKE', "%{$search}%");
                    })
                    ->orWhereHas('address', function ($q) use ($search) {
                        $q->where('receiver_name', 'LIKE', "%{$search}%");
                    })
                    ->orWhereHas('orderItems', function ($q) use ($search) {
                        $q->where('name', 'LIKE', "%{$search}%");
                    });
            });
        }
        return $query;
    }

    public function scopeFilterByStatus(Builder $query, string $status = null)
    {
        if($status){
            $query->where('status', $status);
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
        return $this->address ? $this->address->receiver_name : $this->user->name;
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
        return Jalalian::forge($this->created_at)->format('Y/m/d');
    }
    public function getOrderColumnAttribute()
    {
        $value = $this->id . "<br/>" . $this->userName . "<br/>" . $this->createdAtJalali;

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
        $result = $this->FirstNameOfOrderItem . "<br/>" . number_format($this->final_amount)." تومان ";
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
        $result = $addressText . "<br/> نوع پرداخت : " . $gateway;

        return request()->expectsJson()
            ? ($result) // return plain text for JSON
            : new HtmlString($result);
    }


    public function getSumCountBeforeAttribute()
    {
        return $this->user->orders()->where('id','<',$this->id)->count();
    }
    public function getSumFinalPriceBeforeAttribute()
    {
        return $this->user->orders()->where('id','<',$this->id)->sum('final_amount');
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
        $urt = route('admin_order.print',$this->id);
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
        }
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
    private function markAsPaid()
    {
        $this->update([
            'status' => 'paid'
        ]);

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

//        $this->submitInAccountingApp(); // Uncomment if needed
        
        // Check and generate gift discount code
        $this->checkAndGenerateGift();
        
        // Notify admins about new paid order
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
}
