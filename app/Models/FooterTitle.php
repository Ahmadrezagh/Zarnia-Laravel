<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FooterTitle extends Model
{
    use HasFactory;
    protected $fillable = [
        'title'
    ];

    public function links()
    {
        return $this->hasMany(FooterTitleLink::class);
    }
}
