<?php

namespace App\Models;

use App\Traits\Scopes\FilterByProductId;
use App\Traits\Scopes\FilterByUserId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ShoppingCartItem extends Model
{
    use FilterByUserId,FilterByProductId;
    protected $fillable = [
        'user_id',
        'product_id',
        'etiket_id',
        'count'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function etiket()
    {
        return $this->belongsTo(Etiket::class);
    }
}
