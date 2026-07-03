<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('berita_acaras', function (Blueprint $table) {
            $table->json('form_data')->nullable()->after('sisa_sampel');
            $table->foreignId('form_template_id')->nullable()->after('form_data')->constrained('form_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('berita_acaras', function (Blueprint $table) {
            $table->dropColumn('form_data');
            $table->dropForeign(['form_template_id']);
            $table->dropColumn('form_template_id');
        });
    }
};
