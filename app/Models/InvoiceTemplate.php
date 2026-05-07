<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceTemplate extends Model
{
    protected $fillable = [
        'name',
        'background_path'
    ];

    public function positions() {
        return $this->hasMany(InvoicePosition::class,'template_id','id');
    }
}
