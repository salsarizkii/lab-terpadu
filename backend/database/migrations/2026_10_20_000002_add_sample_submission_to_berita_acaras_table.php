<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('berita_acaras', function (Blueprint $table) {
            $table->boolean('sample_submitted')->default(false)->after('berkas_ulang');
            $table->timestamp('sample_submitted_at')->nullable()->after('sample_submitted');
        });
    }

    public function down(): void
    {
        Schema::table('berita_acaras', function (Blueprint $table) {
            $table->dropColumn(['sample_submitted', 'sample_submitted_at']);
        });
    }
};