<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_templates', function (Blueprint $table) {
            $table->enum('type', ['berita_acara', 'rekap_analis', 'perhitungan_biaya', 'kelola_harga_instrumen'])->change();
        });
    }

    public function down(): void
    {
        Schema::table('form_templates', function (Blueprint $table) {
            $table->enum('type', ['berita_acara', 'rekap_analis'])->change();
        });
    }
};
