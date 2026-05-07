<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttributeGroupCategory extends Model
{
    protected $fillable = [
        'attribute_group_id','category_id'
    ];
}
