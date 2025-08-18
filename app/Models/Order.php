<?php

namespace App\Models;

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
        'note'
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
        'boxing',
        'sent',
        'canceled',
        'failed',
    ];
    public static $PERSIAN_STATUSES = [
        'pending' => 'در انتظار پرداخت',
        'paid' => 'پرداخت شده',
        'boxing' => 'جمع آوری',
        'sent' => 'ارسال شده',
        'canceled' => 'لغو شده',
        'failed' => 'خطا',
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

    public function getCreatedAtJalaliAttribute()
    {
        return Jalalian::forge($this->created_at)->format('Y/m/d');
    }
    public function getOrderColumnAttribute()
    {
        return new HtmlString(
            $this->id . "<br/>" . $this->userName . "<br/>" . $this->createdAtJalali
        );
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

        return new HtmlString(
            $this->FirstNameOfOrderItem . "<br/>" . number_format($this->final_amount)
        );
    }

    public function getWeightAttribute()
    {
        $weight = 0;
        foreach ($this->orderItems as $orderItem) {
            $weight = $weight + $orderItem->product->weight * $orderItem->count;
        }
        return $weight;
    }
    public function getPercentageAttribute()
    {
        return $this->orderItems()->first()->product->darsad_kharid ?? 0;
    }

    public function getWeightColAttribute()
    {

        return new HtmlString(
            $this->weight . "<br/>" . $this->Percentage
        );
    }

    public function getAddressColAttribute()
    {
        return new HtmlString(
            $this->address->address . "<br/> نوع پرداخت :" . $this->gatewayName
        );
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
        return new HtmlString(
            number_format($this->SumCountBefore) ."عدد". "<br/> " . number_format($this->SumFinalPriceBefore)
        );
    }
}
