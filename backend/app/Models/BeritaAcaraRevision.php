<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BeritaAcaraRevision extends Model
{
    protected $fillable = [
        'berita_acara_id',
        'note',
        'created_by',
    ];

    public function beritaAcara()
    {
        return $this->belongsTo(BeritaAcara::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
