<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'berita_acara_id',
        'revision_id',
        'action',
        'description',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function beritaAcara()
    {
        return $this->belongsTo(BeritaAcara::class);
    }

    public function revision()
    {
        return $this->belongsTo(BeritaAcaraRevision::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
