<?php
namespace App\Traits\Scopes;
use Illuminate\Database\Eloquent\Builder;

trait HasDiscount{
    public function scopeHasDiscount(Builder $query, $hasDiscount = null)
    {
        if($hasDiscount){
            return $query->whereNotNull('discounted_price');
        }
        return $query;
    }
}