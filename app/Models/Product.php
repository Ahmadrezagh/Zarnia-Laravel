<?php

namespace App\Models;

use App\Traits\Scopes\HasDiscount;
use App\Traits\Scopes\MaxPrice;
use App\Traits\Scopes\MinPrice;
use App\Traits\Scopes\Search;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Pishran\LaravelPersianSlug\HasPersianSlug;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Sluggable\SlugOptions;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
class Product extends Model implements HasMedia
{
    use InteractsWithMedia;
    use HasPersianSlug;
    use Search,HasDiscount,MaxPrice,MinPrice;
    protected $fillable = [
        'name',
        'slug',
        'weight',
        'price',
        'discounted_price',
        'parent_id',
        'description',
        'attribute_group_id',
        'discount_percentage',
        'ojrat'
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

    public function categories()
    {
        return $this->belongsToMany(Category::class,'product_categories');
    }
    public function getImageAttribute()
    {
        $image = $this->getFirstMediaUrl('cover_image');
        return $image != "" ? $image : asset('img/no_image.jpg');
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

    public function scopeCategories(Builder $query, $category_ids = [])
    {
        if(!empty($category_ids)){
            return $query->whereHas('categories', function ($q) use ($category_ids) {
                $q->whereIn('categories.id', (array) $category_ids);
            });
        }
        return $query;
    }

    public function scopeOrderByEffectivePrice($query, $direction = null)
    {
        $availableDirections = ['asc', 'desc'];
        if($direction && in_array($direction, $availableDirections)){
                return $query->orderByRaw("
                CASE
                    WHEN discounted_price > 0 THEN discounted_price
                    ELSE price
                END {$direction}
            ");
        }
        return $query;
    }

    public function etikets()
    {
        return $this->hasMany(Etiket::class);
    }
    public function getCountAttribute()
    {
        return $this->etikets()->count();
    }



    public function getCategoriesTitleAttribute()
    {
        if($this->categories()->count()){
            $categoriesTitle = "";
            foreach ($this->categories as $category){
                $categoriesTitle .= $category->title.", ";
            }
            return $categoriesTitle;
        }else{
            return "بدون دسته بندی";
        }
    }


    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover_image')
            ->singleFile(); // Only one file for cover image

        $this->addMediaCollection('gallery'); // Multiple files for gallery
    }

    // Optional: Generate thumbnail conversions for media
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('large')
            ->width(505)
            ->height(344)
            ->format('webp')
            ->performOnCollections('cover_image', 'gallery');

        $this->addMediaConversion('medium')
            ->width(344)
            ->height(344)
            ->format('webp')
            ->performOnCollections('cover_image', 'gallery');

        $this->addMediaConversion('small')
            ->width(108)
            ->height(108)
            ->format('webp')
            ->performOnCollections('cover_image', 'gallery');
    }

    public function getOjratAttribute($value)
    {
        if($value)
            return intval($value);
    }
}
