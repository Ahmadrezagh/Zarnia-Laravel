<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComprehensiveProduct extends Model
{
    protected $fillable = [
        'comprehensive_product_id',
        'product_id'
    ];
    
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    
    public function comprehensiveProduct()
    {
        return $this->belongsTo(Product::class, 'comprehensive_product_id');
    }
}
