<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Pishran\LaravelPersianSlug\HasPersianSlug;
use Spatie\Sluggable\SlugOptions;
class Product extends Model
{
    use HasPersianSlug;

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

    public function scopeSearch(Builder $query,$search = null)
    {
        if($search){
            return $query->where('name','like','%'.$search.'%');
        }
        return $query;
    }
    public function scopeMinPrice(Builder $query, $minPrice = null)
    {
        if (is_null($minPrice)) {
            return $query;
        }
        $minPrice = $minPrice * 10;
        return $query->where(function ($q) use ($minPrice) {
            $q->whereNotNull('discounted_price')
                ->where('discounted_price', '>=', $minPrice)
                ->orWhereNull('discounted_price')
                ->where('price', '>=', $minPrice);
        });
    }
    public function scopeMaxPrice(Builder $query, $maxPrice = null)
    {
        if (is_null($maxPrice)) {
            return $query;
        }
        $maxPrice = $maxPrice * 10;
        return $query->where(function ($q) use ($maxPrice) {
            $q->whereNotNull('discounted_price')
                ->where('discounted_price', '<=', $maxPrice)
                ->orWhereNull('discounted_price')
                ->where('price', '<=', $maxPrice);
        });
    }

    public function scopeHasDiscount(Builder $query, $hasDiscount = null)
    {
        if($hasDiscount){
            return $query->whereNotNull('discounted_price');
        }
        return $query;
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
