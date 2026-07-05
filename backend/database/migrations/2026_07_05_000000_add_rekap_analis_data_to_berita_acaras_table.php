<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('berita_acaras', function (Blueprint $table) {
            $table->json('rekap_analis_data')->nullable()->after('form_template_id');
            $table->foreignId('rekap_analis_template_id')->nullable()->after('rekap_analis_data')->constrained('form_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('berita_acaras', function (Blueprint $table) {
            $table->dropForeign(['rekap_analis_template_id']);
            $table->dropColumn(['rekap_analis_data', 'rekap_analis_template_id']);
        });
    }
};
