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
        'ojrat',
        'darsad_kharid'
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
            $price = $value/10;
        }
        return $price;
    }
    public function getPriceWithoutDiscountAttribute($value)
    {
        if($this->discounted_price){
            $price = $this->getRawOriginal('price');
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
    public function getEtiketsCodeAsArrayAttribute()
    {
        $codes = "";

        foreach ($this->etikets()->get() as $etiket) {
            if ($etiket->is_mojood == 0) {
                $codes .= '<span style="color:red;">' . e($etiket->code) . '</span>, ';
            } else {
                $codes .= e($etiket->code) . ', ';
            }
        }

        // Remove trailing comma and space
        return rtrim($codes, ', ');
    }
    public function getCountAttribute()
    {
        return $this->etikets()->where('is_mojood', 1)->count();
    }

    public function scopeHasCount(Builder $query): Builder
    {
        return $query->withCount([
            'etikets as count' => function ($query) {
                $query->where('is_mojood', 1);
            }
        ]);
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

    public function getCoverImageResponsiveAttribute()
    {
        $coverImage = $this->getFirstMedia('cover_image');
        if($coverImage){
            return [
                'large' => $coverImage->getUrl('large') ?? null,
                'medium' => $coverImage->getUrl('medium') ?? null,
                'small' => $coverImage->getUrl('small') ?? null,
                ];
        }
        return [
            'large' => asset('img/no_image.jpg'),
            'medium' => asset('img/no_image.jpg'),
            'small' => asset('img/no_image.jpg'),
        ];
    }

    public function scopeWithMojoodStatus($query)
    {
        return $query->selectSub(function ($q) {
            $q->selectRaw('IF(COUNT(CASE WHEN is_mojood = 1 THEN 1 END) > 0, 1, 0)')
                ->from('etikets')
                ->whereColumn('etikets.product_id', 'products.id');
        }, 'is_mojood');
    }

    public function scopeSortMojood(Builder $query, $direction = null)
    {
        if($direction && in_array($direction, ['asc', 'desc'])){
            return $query
                ->withMojoodStatus()
                ->orderBy("is_mojood", $direction);
        }
        return $query;
    }
    public function scopeWithImageStatus(Builder $query, $direction = null)
    {
        if($direction){
            $query->selectSub(function ($q) {
                $q->selectRaw('COUNT(*) > 0')
                    ->from('media')
                    ->whereColumn('media.model_id', 'products.id')
                    ->where('media.model_type', '=', Product::class);
            }, 'has_image');

            if (in_array(strtolower($direction), ['asc', 'desc'])) {
                $query->orderBy('has_image', strtolower($direction));
            }
        }

        return $query;
    }

    public function scopeWithMojoodCount($query, $direction = null)
    {
        if($direction){
            $query->selectSub(function ($q) {
                $q->selectRaw('COUNT(*)')
                    ->from('etikets')
                    ->whereColumn('etikets.product_id', 'products.id')
                    ->where('is_mojood', 1);
            }, 'mojood_count');

            if (in_array(strtolower($direction), ['asc', 'desc'])) {
                $query->orderBy('mojood_count', strtolower($direction));
            }
        }

        return $query;
    }

    public function scopeHasImage(Builder $query): Builder
    {
        return $query->whereHas('media', function ($q) {
            $q->where('collection_name', 'cover_image'); // optional: if you use specific collections
        });
    }

    public function scopeWhereMojoodIsZero($query)
    {
        return $query->whereRaw('(
        SELECT COUNT(*)
        FROM etikets
        WHERE etikets.product_id = products.id
        AND is_mojood = 1
    ) = 0');
    }

    public function scopeWithoutImage(Builder $query): Builder
    {
        return $query->whereDoesntHave('media', function ($q) {
            $q->where('collection_name', 'cover_image'); // optional: target a specific collection
        });
    }
    public function scopeWithoutGallery(Builder $query): Builder
    {
        return $query->whereDoesntHave('media', function ($q) {
            $q->where('collection_name', 'cover_image'); // optional: target a specific collection
        });
    }
    public function scopeFilterProduct(Builder $query, $filter = null)
    {
        if($filter){
            switch ($filter) {
                case 'only_images':
                    return $query->hasImage();
                case 'only_without_images':
                    return $query->WithoutImage();
                case 'only_without_gallery':
                    return $query->WithoutGallery();
                case 'only_unavilables':
                    return $query->WhereMojoodIsZero();
                case 'only_main_products':
                    return $query->whereNull('parent_id');
                case 'only_discountables':
                    return $query->HasDiscount(1);
                default:
                    return $query;
            }
        }
        return $query;
    }


    public function scopeMultipleSearch(Builder $query, array $search = [])
    {
        $key = $search[0] ?? null;
        $val = $search[1] ?? null;

        if (!$key || !$val) {
            return $query; // Nothing to filter
        }

        // Direct columns in the products table
        $directColumns = ['weight', 'ojrat', 'discount_percentage'];

        if (in_array($key, $directColumns)) {
            return $query->where($key, '=', $val);
        }
        if ($key == 'name') {
            return $query->where($key, 'like', '%'.$val.'%');
        }

        // Virtual attribute: count
        if ($key === 'count') {
            return $query->whereRaw("(
            SELECT COUNT(*) FROM etikets 
            WHERE etikets.product_id = products.id AND is_mojood = 1
        ) = ?", [$val]);
        }

        // Related field in etikets: etiket_code
        if ($key === 'etiket_code') {
            return $query->whereHas('etikets', function ($q) use ($val) {
                $q->where('code', '=', $val);
            });
        }

        return $query; // fallback
    }

    public function getOriginalPriceAttribute()
    {
        return $this->getRawOriginal('price') /10;
    }

    public function scopeHasCountAndImage(Builder $query): Builder
    {
        return $query
            ->withCount([
                'etikets as count' => function ($query) {
                    $query->where('is_mojood', 1);
                }
            ])
            ->whereHas('media', function ($q) {
                $q->where('collection_name', 'cover_image');
            })
            ->having('count', '>', 0); // <- this ensures count is not zero
    }

}
