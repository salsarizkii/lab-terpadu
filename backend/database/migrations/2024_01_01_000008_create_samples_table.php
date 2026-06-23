<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('samples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('berita_acara_id')->constrained();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('quantity')->nullable();
            $table->string('unit')->nullable();
            $table->text('test_result')->nullable();
            $table->enum('status', ['pending', 'processing', 'ready', 'taken'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('samples');
    }
};
