<?php
namespace App\Traits\Scopes;
use Illuminate\Database\Eloquent\Builder;

trait Search{
    public function scopeSearch(Builder $query,$search = null)
    {
        if($search){
            return $query->where('name','like','%'.$search.'%');
        }
        return $query;
    }
}