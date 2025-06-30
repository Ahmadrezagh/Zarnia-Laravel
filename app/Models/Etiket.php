<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Etiket extends Model
{
    protected $fillable = [
        'code',
        'name',
        'weight',
        'price',
        'product_id',
        'ojrat'
    ];
}
