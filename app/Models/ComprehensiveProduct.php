<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComprehensiveProduct extends Model
{
    protected $fillable = [
        'comprehensive_product_id',
        'product_id'
    ];
}
