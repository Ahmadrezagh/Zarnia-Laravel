<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttributeValue extends Model
{
    protected $fillable = ['attribute_id','product_id', 'value'];
}
