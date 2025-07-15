<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Gateway extends Model implements HasMedia
{
    use InteractsWithMedia;

    use InteractsWithMedia;
    protected $fillable = [
        'title',
        'sub_title'
    ];

    public function getImageAttribute()
    {
        $image = $this->getFirstMediaUrl('image');
        return $image != "" ? $image : asset('img/no_image.jpg');
    }
}
