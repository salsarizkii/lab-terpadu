<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_template_id');
            $table->string('label');
            $table->enum('type', ['text', 'textarea', 'number', 'select', 'checkbox', 'radio', 'date', 'time', 'email', 'phone', 'file', 'heading', 'paragraph', 'divider']);
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->integer('sort_order')->default(0);
            $table->string('placeholder')->nullable();
            $table->timestamps();

            $table->foreign('form_template_id')->references('id')->on('form_templates')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_fields');
    }
};
