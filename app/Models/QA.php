<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QA extends Model
{
    protected $fillable = [
        'question',
        'answer',
    ];
}
