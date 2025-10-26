<?php
namespace App\Traits\Scopes;
use Illuminate\Database\Eloquent\Builder;

trait HasDiscount{
    public function scopeHasDiscount(Builder $query, $hasDiscount = null)
    {
        if($hasDiscount){
            return $query
                ->where('discounted_price','!=','0')
                ->whereNotNull('discounted_price')
                ->orWhere('discount_percentage','!=','0');
        }
        return $query;
    }
}