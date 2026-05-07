<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndexButton extends Model
{
    protected $fillable = [
        'title',
        'query'
    ];
}
