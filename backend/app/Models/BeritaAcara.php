<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BeritaAcara extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'rejection_reason',
        'nomor_agenda',
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
        'keterangan',
        'pengukuran_per_sampel',
        'sisa_sampel',
        'upload_akses',
        'masa_berlaku_akses',
        'status_pelanggan',
        'status_berkas',
        'biaya',
        'diskon_diizinkan',
        'form_data',
        'form_template_id',
        'rekap_analis_data',
        'rekap_analis_template_id',
        'formula_id',
        'custom_items',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
            'diskon_diizinkan' => 'boolean',
            'form_data' => 'array',
            'rekap_analis_data' => 'array',
            'custom_items' => 'array',
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

    public function analysts()
    {
        return $this->belongsToMany(User::class, 'berita_acara_analysts', 'berita_acara_id', 'user_id');
    }

    public function penyelias()
    {
        return $this->belongsToMany(User::class, 'berita_acara_penyelia', 'berita_acara_id', 'user_id');
    }

    public function managerTeknis()
    {
        return $this->belongsToMany(User::class, 'berita_acara_manager_teknis', 'berita_acara_id', 'user_id');
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

    public function pengujians()
    {
        return $this->hasMany(Pengujian::class);
    }

    public function formula()
    {
        return $this->belongsTo(Formula::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class)->latest('created_at');
    }
}
