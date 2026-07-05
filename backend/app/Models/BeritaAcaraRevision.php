<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BeritaAcaraRevision extends Model
{
    protected $fillable = [
        'berita_acara_id',
        'note',
        'previous_form_data',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'previous_form_data' => 'array',
        ];
    }

    public function beritaAcara()
    {
        return $this->belongsTo(BeritaAcara::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
