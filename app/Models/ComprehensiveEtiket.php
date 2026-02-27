<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComprehensiveEtiket extends Model
{
    protected $fillable = [
        'etiket_id',
        'related_etiket_id',
    ];

    public function etiket()
    {
        return $this->belongsTo(Etiket::class);
    }

    public function relatedEtiket()
    {
        return $this->belongsTo(Etiket::class, 'related_etiket_id');
    }
}

