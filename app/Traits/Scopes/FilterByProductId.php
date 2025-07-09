<?php
namespace App\Traits\Scopes;
use Illuminate\Database\Eloquent\Builder;

trait FilterByProductId{
    public function scopeFilterByProductId(Builder $query, $productId = null)
    {
        if($productId) {
            return $query->where('product_id', $productId);
        }
        return $query;
    }
}