<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSliderButton extends Model
{
    protected $fillable = [
        'product_slider_id',
        'title',
        'query'
    ];

    public function productSlider(){
        return $this->belongsTo(ProductSlider::class);
    }
}
