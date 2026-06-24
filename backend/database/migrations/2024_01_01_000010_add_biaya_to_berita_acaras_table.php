<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('berita_acaras', function (Blueprint $table) {
            $table->decimal('biaya', 15, 2)->nullable()->after('status_berkas');
            $table->boolean('diskon_diizinkan')->default(false)->after('biaya');
        });
    }

    public function down(): void
    {
        Schema::table('berita_acaras', function (Blueprint $table) {
            $table->dropColumn(['biaya', 'diskon_diizinkan']);
        });
    }
};
