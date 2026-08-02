<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pdf_templates', function (Blueprint $table) {
            $table->boolean('show_kop')->default(true)->after('docx_path');
            $table->boolean('show_ttd')->default(true)->after('show_kop');
            $table->boolean('show_rincian')->default(true)->after('show_ttd');
            $table->text('catatan')->nullable()->after('show_rincian');
        });
    }

    public function down(): void
    {
        Schema::table('pdf_templates', function (Blueprint $table) {
            $table->dropColumn(['show_kop', 'show_ttd', 'show_rincian', 'catatan']);
        });
    }
};
