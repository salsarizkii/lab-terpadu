<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstrumentPrice extends Model
{
    protected $fillable = [
        'name',
        'unit',
        'price_regular',
        'price_uns',
        'note',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_regular' => 'integer',
            'price_uns' => 'integer',
            'sort_order' => 'integer',
        ];
    }
}
