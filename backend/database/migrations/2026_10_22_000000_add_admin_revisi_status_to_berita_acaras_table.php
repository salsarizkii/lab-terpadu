<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('berita_acaras', function (Blueprint $table) {
            $table->enum('status', [
                'draft', 'pending', 'revision', 'admin_verified', 'admin_revisi',
                'penyelia_verified', 'rekap_ready', 'completed', 'rejected',
            ])->default('pending')->change();
        });
    }

    public function down(): void
    {
        DB::table('berita_acaras')->where('status', 'admin_revisi')->update(['status' => 'admin_verified']);

        Schema::table('berita_acaras', function (Blueprint $table) {
            $table->enum('status', [
                'draft', 'pending', 'revision', 'admin_verified',
                'penyelia_verified', 'rekap_ready', 'completed', 'rejected',
            ])->default('pending')->change();
        });
    }
};