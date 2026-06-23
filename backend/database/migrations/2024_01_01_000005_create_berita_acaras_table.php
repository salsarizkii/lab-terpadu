<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('berita_acaras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->enum('status', ['draft', 'pending', 'revision', 'approved', 'rejected'])->default('pending');
            $table->foreignId('analyst_id')->nullable()->constrained('users');
            $table->text('nama_pemohon')->nullable();
            $table->text('nim')->nullable();
            $table->text('prodi_fakultas_instansi')->nullable();
            $table->text('dosen_pembimbing')->nullable();
            $table->text('telp_hp')->nullable();
            $table->text('email')->nullable();
            $table->text('alamat')->nullable();
            $table->text('judul_penelitian')->nullable();
            $table->text('akses_lab')->nullable();
            $table->text('bahan_contoh_uji')->nullable();
            $table->text('kemasan_packing')->nullable();
            $table->text('permintaan_analisa')->nullable();
            $table->text('jumlah_sampel')->nullable();
            $table->text('kode_nama_sampel')->nullable();
            $table->text('keterangan_sampel')->nullable();
            $table->text('keterangan_tambahan')->nullable();
            $table->text('pengukuran_per_sampel')->nullable();
            $table->text('sisa_sampel')->nullable();
            $table->text('upload_akses')->nullable();
            $table->text('masa_berlaku_akses')->nullable();
            $table->text('status_pelanggan')->nullable();
            $table->text('status_berkas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('berita_acaras');
    }
};
