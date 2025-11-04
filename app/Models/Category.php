<?php

namespace App\Models;

use App\Traits\HasComplementaryProducts;
use App\Traits\HasRelatedProducts;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;
use Pishran\LaravelPersianSlug\HasPersianSlug;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Support\Facades\File;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\SlugOptions;

class Category extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    use HasComplementaryProducts,HasRelatedProducts;
    use HasPersianSlug;
    protected $fillable = [
        'title',
        'parent_id'
        ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }
    public function parent()
    {
        return $this->belongsTo(Category::class,'parent_id', 'id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class,'product_categories');
    }

    public function getParentIdsAttribute()
    {
        $parent_ids = [];
        $current_category = $this;

        while ($current_category->parent){
            array_push($parent_ids, $current_category->parent_id);
            $current_category = $current_category->parent;
        }
        return $parent_ids;
    }

    public function isParentOfCategory(Category $category)
    {
        return (in_array($this->id, $category->parent_ids));
    }

    public function assignImageFromPublicPath($path)
    {
        if (!File::exists($path)) {
            throw new \Exception("File does not exist at path: $path");
        }

        $fileName = basename($path);
        $tempPath = storage_path('app/temp/' . uniqid() . '_' . $fileName);

        File::ensureDirectoryExists(dirname($tempPath));
        File::copy($path, $tempPath);

        // ✅ Remove old images from 'categories' collection
        $this->clearMediaCollection('categories');

        $this->addMedia($tempPath)
            ->preservingOriginal()
            ->toMediaCollection('categories');

        File::delete($tempPath);
    }

    public function getImageAttribute()
    {
        $image = $this->getFirstMediaUrl('cover_image');
        return $image != "" ? $image : asset('img/no_image.jpg');
    }

    public function scopeParents(Builder $query)
    {
        return $query->where('parent_id',0);
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

    public function attributeGroups()
    {
        return $this->belongsToMany(AttributeGroup::class,'attribute_group_categories','category_id','attribute_group_id');
    }


    // Category → products
    public function complementaryProducts(): MorphToMany
    {
        return $this->morphToMany(
            Product::class,
            'source',
            'complementary_products',
            'source_id',
            'target_id'
        )->wherePivot('target_type', Product::class);
    }

    // Category → categories (optional, if you also want category-category)
    public function complementaryCategories(): MorphToMany
    {
        return $this->morphToMany(
            Category::class,
            'source',
            'complementary_products',
            'source_id',
            'target_id'
        )->wherePivot('target_type', Category::class);
    } public function relatedProducts(): MorphToMany
    {
        return $this->morphToMany(
            Product::class,
            'source',
            'related_products',
            'source_id',
            'target_id'
        )->wherePivot('target_type', Product::class);
    }

    // Category → categories (optional, if you also want category-category)
    public function relatedCategories(): MorphToMany
    {
        return $this->morphToMany(
            Category::class,
            'source',
            'related_products',
            'source_id',
            'target_id'
        )->wherePivot('target_type', Category::class);
    }


    public function relatedProductsDirect(): MorphToMany
    {
        return $this->morphToMany(
            Product::class,
            'source',
            'related_products',
            'source_id',
            'target_id'
        )->wherePivot('target_type', Product::class);
    }
    
    /**
     * Get related products with fallback to parent category if no manual related products exist
     * If parent also has no manual related products, fallback to products from this category itself
     */
    public function getRelatedProductsWithParentFallback()
    {
        // Check if this category has manual related products or categories
        $manualRelatedProducts = $this->relatedProductsDirect()->get();
        $manualRelatedCategories = $this->relatedCategories()->get();
        
        // If category has manual related products or categories, return them
        if ($manualRelatedProducts->isNotEmpty() || $manualRelatedCategories->isNotEmpty()) {
            $related = collect();
            
            // Add direct related products
            $related = $related->concat($manualRelatedProducts);
            
            // Add products from related categories
            $related = $related->concat(
                $manualRelatedCategories->flatMap(function ($category) {
                    // Load products if not already loaded
                    if (!$category->relationLoaded('products')) {
                        $category->load('products');
                    }
                    return $category->products;
                })
            );
            
            return $related->unique('id')->values();
        }
        
        // If no manual related products, check parent category
        if ($this->parent_id && $this->parent) {
            // Load parent if not already loaded
            if (!$this->relationLoaded('parent')) {
                $this->load('parent');
            }
            
            // Check if parent has MANUAL related products (not from fallback)
            $parentManualRelatedProducts = $this->parent->relatedProductsDirect()->get();
            $parentManualRelatedCategories = $this->parent->relatedCategories()->get();
            
            // If parent has manual related products, return them
            if ($parentManualRelatedProducts->isNotEmpty() || $parentManualRelatedCategories->isNotEmpty()) {
                $related = collect();
                $related = $related->concat($parentManualRelatedProducts);
                $related = $related->concat(
                    $parentManualRelatedCategories->flatMap(function ($category) {
                        if (!$category->relationLoaded('products')) {
                            $category->load('products');
                        }
                        return $category->products;
                    })
                );
                return $related->unique('id')->values();
            }
        }
        
        // If no parent or parent has no manual related products, fallback to products from this category itself
        // Load products relationship if not already loaded
        if (!$this->relationLoaded('products')) {
            $this->load('products');
        }
        
        // Return all products from this category (filtering will be done in Product model or API controller)
        return $this->products->unique('id')->values();
    }

    // Related products via direct product → product links
    public function complementaryProductsDirect(): MorphToMany
    {
        return $this->morphToMany(
            Product::class,
            'source',
            'complementary_products',
            'source_id',
            'target_id'
        )->wherePivot('target_type', Product::class);
    }

    // Related products via category links
    public function complementaryProductsViaCategories()
    {
        return $this->morphToMany(
            Category::class,
            'source',
            'complementary_products',
            'source_id',
            'target_id'
        )->wherePivot('target_type', Category::class)
            ->with('products'); // Eager load category → products
    }


}
