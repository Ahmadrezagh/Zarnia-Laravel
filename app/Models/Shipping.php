<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Shipping extends Model implements HasMedia
{

    use InteractsWithMedia;
    protected $fillable = [
        'title',
        'price',
        'passive_price'
    ];

    public function times()
    {
        return $this->hasMany(ShippingTime::class);
    }

    public function getImageAttribute()
    {
        $image = $this->getFirstMediaUrl('image');
        return $image != "" ? $image : asset('img/no_image.jpg');
    }
}
