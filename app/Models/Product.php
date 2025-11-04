<?php

namespace App\Models;

use App\Traits\HasComplementaryProducts;
use App\Traits\HasRelatedProducts;
use App\Traits\Scopes\HasDiscount;
use App\Traits\Scopes\MaxPrice;
use App\Traits\Scopes\MinPrice;
use App\Traits\Scopes\PriceRange;
use App\Traits\Scopes\Search;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\DB;
use Pishran\LaravelPersianSlug\HasPersianSlug;
use Spatie\Image\Enums\CropPosition;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Sluggable\SlugOptions;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
class Product extends Model implements HasMedia
{
    use InteractsWithMedia;
    use HasPersianSlug;
    use Search,HasDiscount,MaxPrice,MinPrice,PriceRange;
    use HasComplementaryProducts,HasRelatedProducts;
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
        'darsad_kharid',
        'is_comprehensive',
        'mazaneh',
        'darsad_vazn_foroosh'
    ];

    public function setNameAttribute($value)
    {
        // Arabic Ye characters → Persian Ye (U+06CC)
        $value = str_replace(['ي', 'ی'], 'ی', $value);

        // (Optional) also handle Arabic Kaf → Persian Kaf
        $value = str_replace('ك', 'ک', $value);

        $this->attributes['name'] = $value;
    }

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

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
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
    
    public function getFrontendUrlAttribute()
    {
        $frontendUrl = setting('url');
        return $frontendUrl . '/products/' . $this->slug;
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

    public function products()
    {
        return $this->belongsToMany(Product::class,'comprehensive_products','comprehensive_product_id','product_id');
    }
    
    public function favorites()
    {
        return $this->belongsToMany(\App\Models\User::class, 'favorites', 'product_id', 'user_id');
    }
    
    public function etikets()
    {
        return $this->hasMany(Etiket::class);
    }

    public function getAllEtiketsAttribute()
    {
        // Collect current product's etikets
        $etikets = $this->etikets;

        // Collect children's etikets
        $this->children->each(function ($child) use ($etikets) {
            $etikets->push(...$child->etikets);
        });

        if($this->is_comprehensive == 1){
            // Collect children's etikets
            $this->products->each(function ($child) use ($etikets) {
                $etikets->push(...$child->etikets);
            });
        }

        // Return unique etikets
        return $etikets->unique('id');
    }

    public function getEtiketsCodeAsArrayAttribute()
    {
        $codes = "";

        // Normalize current product name for comparison
        $currentProductName = $this->normalizeName($this->name);
        $currentProductWeight = $this->weight;

        foreach ($this->AllEtikets as $etiket) {
            // Normalize etiket name for comparison
            $etiketName = $this->normalizeName($etiket->name);
            
            // Check if etiket matches this product (same name AND same weight)
            $matchesThisProduct = ($etiketName === $currentProductName) && 
                                  ($etiket->weight == $currentProductWeight);
            
            // Build the style for this etiket code
            $style = '';
            
            if ($matchesThisProduct) {
                // Light blue background for matching etikets
                $style = 'background-color: lightblue; padding: 2px 5px; border-radius: 3px;';
            }
            
            if ($etiket->is_mojood == 0) {
                // Red color for unavailable etikets (can combine with blue background)
                $style .= ' color: red;';
            }
            
            // Create tooltip content
            $tooltipContent = e($etiket->name) . ' - ' . e($etiket->weight) . 'g';
            if ($etiket->is_mojood == 0) {
                $tooltipContent .= ' (ناموجود)';
            }
            
            // Add cursor pointer for better UX
            $style .= ' cursor: help;';
            
            // Add Bootstrap tooltip attributes
            $tooltip = 'data-toggle="tooltip" data-placement="top" data-html="true" title="' . $tooltipContent . '"';
            
            if ($style) {
                $codes .= '<span class="etiket-code-item" style="' . $style . '" ' . $tooltip . '>' . e($etiket->code) . '</span>, ';
            } else {
                $codes .= '<span class="etiket-code-item" ' . $tooltip . '>' . e($etiket->code) . '</span>, ';
            }
        }

        // Remove trailing comma and space
        return rtrim($codes, ', ');
    }

    /**
     * Normalize name for comparison (handle Arabic/Persian character differences)
     */
    private function normalizeName($name): string
    {
        // Arabic Ye → Persian Ye
        $name = str_replace(['ي', 'ی'], 'ی', $name);
        
        // Arabic Kaf → Persian Kaf
        $name = str_replace('ك', 'ک', $name);
        
        // Trim whitespace
        $name = trim($name);
        
        return $name;
    }
    public function getSingleCountAttribute()
    {
        // If this is a comprehensive product, return minimum single_count of constituent products
        if ($this->is_comprehensive == 1) {
            // Load products relationship if not already loaded
            if (!$this->relationLoaded('products')) {
                $this->load('products');
            }
            
            // Get all constituent products
            $constituentProducts = $this->products;
            
            // If no constituent products, return 0
            if ($constituentProducts->isEmpty()) {
                return 0;
            }
            
            // Get single_count for each constituent product
            $singleCounts = $constituentProducts->map(function ($product) {
                return $product->single_count;
            })->filter(function ($count) {
                return $count >= 0; // Include zero counts as well
            });
            
            // If no available products (all have count < 0), return 0
            if ($singleCounts->isEmpty()) {
                return 0;
            }
            
            // Return minimum single_count
            return $singleCounts->min();
        }
        
        // For non-comprehensive products, return etiket count
        return $this->etikets()->where('is_mojood', 1)->count();
    }

    public function getCountAttribute()
    {
        // If this is a comprehensive product, return minimum single_count of constituent products
        if ($this->is_comprehensive == 1) {
            // For comprehensive products, count = single_count (minimum of constituent products)
            return $this->single_count;
        }
        
        // Count from this product's etikets
        $ownCount = $this->etikets()->where('is_mojood', 1)->count();

        // Recursive count from children
        $childrenCount = $this->children->sum(function ($child) {
            return $child->count; // This will call getCountAttribute() recursively
        });

        return $ownCount + $childrenCount;
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
        $this->addMediaConversion('xlarge')
            ->crop(505, 505, CropPosition::Center)

            ->performOnCollections('cover_image', 'gallery');

        $this->addMediaConversion('large')
            ->crop(505, 505, CropPosition::Center)
            ->width(344)
            ->height(344)

            ->performOnCollections('cover_image', 'gallery');

        $this->addMediaConversion('medium')
            ->crop(505, 505, CropPosition::Center)
            ->width(200)
            ->height(200)

            ->performOnCollections('cover_image', 'gallery');

        $this->addMediaConversion('small')
            ->crop(505, 505, CropPosition::Center)
            ->width(108)
            ->height(108)

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
                'xlarge' => $coverImage->getUrl('xlarge') ?? null,
                'large' => $coverImage->getUrl('large') ?? null,
                'medium' => $coverImage->getUrl('medium') ?? null,
                'small' => $coverImage->getUrl('small') ?? null,
                ];
        }
        return [
            'xlarge' => asset('img/no_image.jpg'),
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

    public function scopeAvailable($query)
    {
        return $query->whereExists(function ($q) {
            $q->selectRaw(1)
                ->from('etikets')
                ->whereColumn('etikets.product_id', 'products.id')
                ->where('etikets.is_mojood', 1);
        });
    }

    public function scopeNotAvailable($query)
    {
        return $query->whereNotExists(function ($q) {
            $q->selectRaw(1)
                ->from('etikets')
                ->whereColumn('etikets.product_id', 'products.id')
                ->where('etikets.is_mojood', 1);
        });
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

    public function getPriceRangeTitleAttribute()
    {
        // Load children if not already loaded (to avoid N+1 queries)
        if (!$this->relationLoaded('children')) {
            $this->load('children');
        }
        
        // Collect available products (this product + children with single_count >= 1)
        $availableProducts = collect();
        
        // Check if this product is available
        if ($this->single_count >= 1) {
            $availableProducts->push($this);
        }
        
        // Add available children
        $this->children->each(function ($child) use ($availableProducts) {
            if ($child->single_count >= 1) {
                $availableProducts->push($child);
            }
        });
        
        // If no available products, return null or empty
        if ($availableProducts->isEmpty()) {
            return null;
        }
        
        // Collect prices from available products only
        $prices = $availableProducts->map(function ($product) {
            return $product->price; // Uses the price accessor which handles discounted_price
        });
        
        $minPrice = $prices->min();
        $maxPrice = $prices->max();
        
        // If all prices are the same, return single price format
        if ($minPrice == $maxPrice) {
            return number_format($minPrice) . ' تومان';
        }
        
        // Return range format
        return 'از ' . number_format($minPrice) . ' تومان تا ' . number_format($maxPrice) . ' تومان';
    }

    public function getMinimumAvailablePriceAttribute()
    {
        // Load children if not already loaded (to avoid N+1 queries)
        if (!$this->relationLoaded('children')) {
            $this->load('children');
        }
        
        // Collect available products (this product + children with single_count >= 1)
        $availableProducts = collect();
        
        // Check if this product is available
        if ($this->single_count >= 1) {
            $availableProducts->push($this);
        }
        
        // Add available children
        $this->children->each(function ($child) use ($availableProducts) {
            if ($child->single_count >= 1) {
                $availableProducts->push($child);
            }
        });
        
        // If no available products, return null
        if ($availableProducts->isEmpty()) {
            return null;
        }
        
        // Get the minimum price among available products
        $minPrice = $availableProducts->map(function ($product) {
            return $product->price; // Uses the price accessor which handles discounted_price
        })->min();
        
        return $minPrice;
    }

    public function scopeHasCountAndImage(Builder $query): Builder
    {
        return $query
            ->whereHas('media', function ($q) {
                $q->where('collection_name', 'cover_image');
            })
            ->where(function ($q) {
                // For comprehensive products: check if ALL constituent products have single_count >= 1
                // (This ensures comprehensive product's single_count >= 1 since it's the minimum)
                $q->where(function ($comprehensiveQuery) {
                    $comprehensiveQuery->where('is_comprehensive', 1)
                        ->whereHas('products', function ($productsQuery) {
                            // Check if constituent product has at least one available etiket (single_count >= 1)
                            $productsQuery->whereHas('etikets', function ($etiketQuery) {
                                $etiketQuery->where('is_mojood', 1);
                            });
                        })
                        // Ensure ALL constituent products have available etikets (not just one)
                        // This is done by checking that there are no constituent products without available etikets
                        ->whereDoesntHave('products', function ($productsQuery) {
                            $productsQuery->whereDoesntHave('etikets', function ($etiketQuery) {
                                $etiketQuery->where('is_mojood', 1);
                            });
                        });
                })
                // For regular products: check etiket count directly (single_count >= 1)
                ->orWhere(function ($regularQuery) {
                    $regularQuery->where(function ($subQ) {
                        $subQ->whereNull('is_comprehensive')
                            ->orWhere('is_comprehensive', 0);
                    })
                    ->whereHas('etikets', function ($etiketQuery) {
                        $etiketQuery->where('is_mojood', 1);
                    });
                });
            });
    }

    public function scopeMain(Builder $query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeWihtoutCategory(Builder $query)
    {
        return $query->whereDoesntHave('categories');
    }

    public function scopeComprehensive(Builder $query)
    {
        return $query->where('is_comprehensive','=',1);
    }

    public function scopeMostFavorite(Builder $query): Builder
    {
        return $query
            ->withCount('favorites as favorites_count')
            ->orderBy('favorites_count', 'desc')
            ->orderBy('created_at', 'desc'); // Secondary sort by creation date
    }

    public function scopeApplyDefaultSort(Builder $query, $sortType = null)
    {
        // If no sort type provided, get it from settings
        if (!$sortType) {
            $sortType = setting('default_shop_display') ?? 'latest';
        }

        switch ($sortType) {
            case 'latest':
                return $query->orderBy('created_at', 'desc');
            case 'oldest':
                return $query->orderBy('created_at', 'asc');
            case 'price_asc':
                return $query->orderByRaw("
                    CASE
                        WHEN discounted_price > 0 THEN discounted_price
                        ELSE price
                    END asc
                ");
            case 'price_desc':
                return $query->orderByRaw("
                    CASE
                        WHEN discounted_price > 0 THEN discounted_price
                        ELSE price
                    END desc
                ");
            case 'name_asc':
                return $query->orderBy('name', 'asc');
            case 'name_desc':
                return $query->orderBy('name', 'desc');
            case 'random':
                return $query->inRandomOrder();
            case 'most_favorite':
            case 'most_favorites':
                return $query->mostFavorite();
            default:
                return $query->orderBy('created_at', 'desc');
        }
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

    // Combined accessor: products regardless of direct/indirect
    public function complementaryProducts()
    {
        $related = collect();

        // 1️⃣ Direct complementary products
        $related = $related->concat($this->complementaryProductsDirect()->get());

        // 2️⃣ Products from complementary categories
        $related = $related->concat(
            $this->complementaryProductsViaCategories()
                ->get()
                ->flatMap(fn ($category) => $category->products)
        );

        // 3️⃣ Products from this product's categories → direct complementary products
        $related = $related->concat(
            $this->categories()
                ->with('complementaryProductsDirect')
                ->get()
                ->flatMap(fn ($category) => $category->complementaryProductsDirect)
        );

        // 4️⃣ Products from this product's categories → complementary categories → products
        $related = $related->concat(
            $this->categories()
                ->with('complementaryProductsViaCategories.products')
                ->get()
                ->flatMap(fn ($category) =>
                $category->complementaryProductsViaCategories->flatMap(fn ($cat) => $cat->products)
                )
        );

        // Remove duplicates & keep order
        return $related->unique('id')->values();
    }



    // Direct product → product relations
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

    // Related categories
    public function relatedCategories(): MorphToMany
    {
        return $this->morphToMany(
            Category::class,
            'source',
            'related_products',
            'source_id',
            'target_id'
        )->wherePivot('target_type', Category::class)
            ->with('products');
    }

    // Main method to get related products with fallbacks
    public function relatedProducts()
    {
        $related = collect();

        // 1️⃣ Direct products
        $related = $related->concat($this->relatedProductsDirect()->get());

        // 2️⃣ Products from related categories
        $related = $related->concat(
            $this->relatedCategories()
                ->get()
                ->flatMap(fn ($category) => $category->products)
        );

        // 3️⃣ Products from this product's categories → direct related products (with parent fallback)
        $related = $related->concat(
            $this->categories()
                ->get()
                ->flatMap(function ($category) {
                    // Use the new method that checks parent category if no manual related products
                    return $category->getRelatedProductsWithParentFallback();
                })
        );

        // Note: Step 4 (related categories) is now handled in getRelatedProductsWithParentFallback
        // So we don't need to check it separately here

        // 5️⃣ Fallback: products from same categories as this product
        if ($related->isEmpty()) {
            $related = $related->concat(
                $this->categories()
                    ->with('products')
                    ->get()
                    ->flatMap(fn ($category) => $category->products)
                    ->where('id', '!=', $this->id) // exclude self
            );
        }

        // Remove duplicates & keep order
        return $related->unique('id')->values();
    }

    public static function syncChildren()
    {
        DB::transaction(function () {
            // Get all products grouped by name
            $groups = Product::select('name')
                ->groupBy('name')
                ->get();

            foreach ($groups as $group) {
                // Get all products with the same name
                $products = Product::where('name', $group->name)->get();

                if ($products->count() > 1) {
                    // Choose the first one as parent
                    $parent = $products->first();

                    foreach ($products as $product) {
                        // Only update if it has no parent yet and it's not the parent itself
                        if (is_null($product->parent_id) && $product->id !== $parent->id) {
                            $product->parent_id = $parent->id;
                            $product->save();
                        }
                    }
                }
            }
        });
    }

    public function getNameUrlAttribute()
    {
        if($this->parent_id == null && $this->children()->count() > 0 && ($this->is_comprehensive == 0)) {
            $url = route('products.products_children_of',$this->slug);
            return "<a href='$url' target='_blank' >$this->name</a>";
        }
        return $this->name;
    }

    public function scopeChildrenOf(Builder $query, $product_id)
    {
        return $query->where('parent_id', $product_id);
    }

    public function getViewCountAttribute()
    {
        $encodedSlug = urlencode($this->slug);

        return DB::table('visits')
            ->where('url', 'like', "%$encodedSlug")
            ->count();
    }

    public function options()
    {
        return $this->belongsToMany(Attribute::class,'attribute_values')->withPivot('value');
    }
}
