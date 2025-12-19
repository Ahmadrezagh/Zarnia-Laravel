<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'etiket',
        'name',
        'count',
        'price',
//        'unique_order_number_per_etiket'
    ];

//    protected static function boot()
//    {
//        parent::boot();
//
//        static::creating(function ($orderItem) {
//            if (empty($orderItem->unique_order_number_per_etiket)) {
//                $orderItem->unique_order_number_per_etiket = self::generateUniqueNumber();
//            }
//        });
//    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    } 

    private static function generateUniqueNumber()
    {
        do {
            // Generate a random 25-digit number
            $number = str_pad(mt_rand(1, 9999999999999999999999999), 25, '0', STR_PAD_LEFT);
        } while (self::where('unique_order_number_per_etiket', $number)->exists());

        return $number;
    }

    public function etiket()
    {
        return $this->hasOne(Etiket::class,'id','etiket');
    }


}
