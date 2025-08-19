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
        'ojrat',
        'is_mojood',
        'darsad_kharid'
    ];

    public function setNameAttribute($value)
    {
        // Arabic Ye characters → Persian Ye (U+06CC)
        $value = str_replace(['ي', 'ی'], 'ی', $value);

        // (Optional) also handle Arabic Kaf → Persian Kaf
        $value = str_replace('ك', 'ک', $value);

        $this->attributes['name'] = $value;
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
