<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttributeGroup extends Model
{
    protected $fillable = [
        'name'
    ];

    public function attributes()
    {
        return $this->belongsToMany(Attribute::class,'attribute_group_attributes','attribute_group_id','attribute_id');
    }
}
