<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengujians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('berita_acara_id')->constrained()->cascadeOnDelete();
            $table->string('nama');
            $table->string('agenda')->nullable();
            $table->string('jumlah_pengujian')->nullable();
            $table->string('nama_pengujian')->nullable();
            $table->date('tanggal_permohonan')->nullable();
            $table->date('tanggal_pengujian')->nullable();
            $table->string('analis')->nullable();
            $table->text('pengambilan_hasil')->nullable();
            $table->string('nama_file_pengujian')->nullable();
            $table->text('keterangan_pengujian')->nullable();
            $table->string('verifikator')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengujians');
    }
};
