<?php

namespace App\Models;

use App\Traits\Scopes\HasDiscount;
use App\Traits\Scopes\MaxPrice;
use App\Traits\Scopes\MinPrice;
use App\Traits\Scopes\Search;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Pishran\LaravelPersianSlug\HasPersianSlug;
use Spatie\Sluggable\SlugOptions;
class Product extends Model
{
    use HasPersianSlug;
    use Search,HasDiscount,MaxPrice,MinPrice;
    protected $fillable = [
        'name',
        'slug',
        'weight',
        'price',
        'discounted_price',
        'parent_id'
    ];


    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    public function getPriceAttribute($value)
    {
        if($this->discounted_price){
            $price = $this->discounted_price;
        }else{
            $price = $value;
        }
        return $price/10;
    }
    public function getPriceWithoutDiscountAttribute($value)
    {
        if($this->discounted_price){
            $price = $value;
        }else{
            $price = 0;
        }
        return $price/10;
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function getImageAttribute()
    {
        return asset('img/sample.jpg');
    }
    public function getGalleryAttribute()
    {
        return [
            asset('img/sample.jpg'),
            asset('img/sample.jpg'),
            asset('img/sample.jpg'),
            asset('img/sample.jpg'),
        ];
    }
}
