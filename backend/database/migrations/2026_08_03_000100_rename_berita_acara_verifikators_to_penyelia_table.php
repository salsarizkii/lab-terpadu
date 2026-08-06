<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('berita_acara_verifikators', 'berita_acara_penyelia');
    }

    public function down(): void
    {
        Schema::rename('berita_acara_penyelia', 'berita_acara_verifikators');
    }
};
