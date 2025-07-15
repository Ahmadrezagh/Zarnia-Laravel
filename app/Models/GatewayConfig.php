<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GatewayConfig extends Model
{
    protected $fillable = [
        'gateway_id',
        'key',
        'value'
    ];
}
