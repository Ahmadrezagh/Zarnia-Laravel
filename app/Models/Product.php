<?php

namespace App\Models;

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
}
