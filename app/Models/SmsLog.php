<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    protected $fillable = [
        'success',
        'receiver',
        'message',
        'result'
    ];
}
