<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Pishran\LaravelPersianSlug\HasPersianSlug;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Support\Facades\File;
use Spatie\Sluggable\SlugOptions;

class Category extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

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
        $media = $this->getFirstMedia('categories');
        return $media ? $media->getUrl() : null;
    }
}
