<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoicePosition extends Model
{
    protected $fillable = [
        'template_id',
        'key',
        'type',
        'value',
        'x',
        'y',
        'font_family',
        'font_size',
        'color'
    ];

}
