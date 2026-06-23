<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    protected $fillable = [
        'berita_acara_id',
        'file_path',
        'downloaded_at',
    ];

    protected function casts(): array
    {
        return [
            'downloaded_at' => 'datetime',
        ];
    }

    public function beritaAcara()
    {
        return $this->belongsTo(BeritaAcara::class);
    }
}
