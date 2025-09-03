<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSlider extends Model
{
    protected $fillable = [
        'title',
        'query',
        'show_more'
    ];

    public function buttons()
    {
        return $this->hasMany(ProductSliderButton::class);
    }

    public function getButtonsTitleAttribute()
    {
        return "لیست دکمه ها";
    }
}
