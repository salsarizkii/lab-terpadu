<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdf_templates', function (Blueprint $table) {
            $table->id();
            $table->string('type')->unique(); // berita_acara, rekap_analis, tagihan, sertifikat
            $table->string('name');
            $table->longText('html_content')->nullable();
            $table->string('docx_path')->nullable();
            $table->string('paper_size')->default('a4'); // a4, letter, legal
            $table->string('orientation')->default('portrait'); // portrait, landscape
            $table->integer('margin_top')->default(15);
            $table->integer('margin_bottom')->default(15);
            $table->integer('margin_left')->default(15);
            $table->integer('margin_right')->default(15);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdf_templates');
    }
};
