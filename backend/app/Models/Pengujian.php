<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengujian extends Model
{
    protected $fillable = [
        'berita_acara_id',
        'nama',
        'agenda',
        'jumlah_pengujian',
        'nama_pengujian',
        'tanggal_permohonan',
        'tanggal_pengujian',
        'analis',
        'pengambilan_hasil',
        'nama_file_pengujian',
        'keterangan_pengujian',
        'verifikator',
    ];

    public function beritaAcara()
    {
        return $this->belongsTo(BeritaAcara::class);
    }
}
