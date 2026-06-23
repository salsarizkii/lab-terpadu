<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sample extends Model
{
    protected $fillable = [
        'berita_acara_id',
        'name',
        'description',
        'quantity',
        'unit',
        'test_result',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }

    public function beritaAcara()
    {
        return $this->belongsTo(BeritaAcara::class);
    }
}
