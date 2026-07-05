<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('berita_acara_revisions', function (Blueprint $table) {
            $table->json('previous_form_data')->nullable()->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('berita_acara_revisions', function (Blueprint $table) {
            $table->dropColumn('previous_form_data');
        });
    }
};
