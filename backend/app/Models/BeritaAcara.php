<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BeritaAcara extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'analyst_id',
        'nama_pemohon',
        'nim',
        'prodi_fakultas_instansi',
        'dosen_pembimbing',
        'telp_hp',
        'email',
        'alamat',
        'judul_penelitian',
        'akses_lab',
        'bahan_contoh_uji',
        'kemasan_packing',
        'permintaan_analisa',
        'jumlah_sampel',
        'kode_nama_sampel',
        'keterangan_sampel',
        'keterangan_tambahan',
        'pengukuran_per_sampel',
        'sisa_sampel',
        'upload_akses',
        'masa_berlaku_akses',
        'status_pelanggan',
        'status_berkas',
        'biaya',
        'diskon_diizinkan',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
            'diskon_diizinkan' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function analyst()
    {
        return $this->belongsTo(User::class, 'analyst_id');
    }

    public function revisions()
    {
        return $this->hasMany(BeritaAcaraRevision::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function samples()
    {
        return $this->hasMany(Sample::class);
    }

    public function certificate()
    {
        return $this->hasOne(Certificate::class);
    }
}
