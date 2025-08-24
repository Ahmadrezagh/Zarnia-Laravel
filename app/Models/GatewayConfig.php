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

    public function gateway()
    {
        return $this->belongsTo(Gateway::class);
    }

    public static function getConfig($key)
    {
        return GatewayConfig::query()->where('key', $key)->first()->value ?? null;
    }
}
