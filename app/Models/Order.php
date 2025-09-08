<?php

namespace App\Models;

use App\Services\PaymentGateways\SnappPayGateway;
use App\Services\SMS\Kavehnegar;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Morilog\Jalali\Jalalian;

class Order extends Model
{
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
        'payment_url'
    ];

    protected $dates = ['paid_at'];

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
        return $this->hasMany(OrderItem::class);
    }
    public static $STATUSES = [
        'pending',
        'paid',
        'failed',
        'canceled',
        'boxing',
        'sent',
        'post',
        'completed',
        'rejected',
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
        return $this->address ? $this->address->receiver_name : '';
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
        return $this->orderItems()->first() ? $this->orderItems()->first()->product->darsad_kharid: 0 ;
    }
    public function getWeightColAttribute()
    {
        $result = $this->weight ." گرم ". "<br/>" . $this->Percentage." درصد "."<br/>".$this->DarsadKharid." درصد "."<br/>".$this->discount_percentage." درصد ";
        return request()->expectsJson() ?
            $result :
            new HtmlString($result);
    }

    public function getAddressColAttribute()
    {
        $gateway = '<span style="background-color:' . e($this->gatewayColor) . ';border-radius:2.5rem;padding:4px">'
            . e($this->gatewayName) . '</span>';

        $result = $this->address->address . "<br/> نوع پرداخت : " . $gateway;

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
        $result =  "<button class='btn btn-primary'>دانلود pdf</button> <button class='btn btn-success'>پرینت</button> ";

        return request()->expectsJson() ?
            $result :
            new HtmlString($result);
    }

    public function verify()
    {
        if($this->gateway->key == 'snapp'){
            return $this->verifySnapp();
        }
    }

    public function verifySnapp()
    {
        $gateway = new SnappPayGateway();
        $verify = $gateway->verify($this->payment_token);
        if($verify){
            $this->update([
                'status' => 'paid'
            ]);
            $sms = new Kavehnegar();
            $sms->send_with_two_token($this->address->receiver_phone,$this->address->receiver_name,$this->id,$this->status);
            return true;
        }
        return false;
    }
}
