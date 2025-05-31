<?php
namespace App\Traits\Scopes;
use Illuminate\Database\Eloquent\Builder;

trait FilterByUserId{
    public function scopeFilterByUserId(Builder $query, $userId = null)
    {
        if($userId) {
            return $query->where('user_id', $userId);
        }
        return $query;
    }
}