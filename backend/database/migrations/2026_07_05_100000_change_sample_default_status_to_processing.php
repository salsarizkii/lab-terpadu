<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('samples')->where('status', 'pending')->update(['status' => 'processing']);
        Schema::table('samples', function (Blueprint $table) {
            $table->enum('status', ['pending', 'processing', 'ready', 'taken'])->default('processing')->change();
        });
    }

    public function down(): void
    {
        Schema::table('samples', function (Blueprint $table) {
            $table->enum('status', ['pending', 'processing', 'ready', 'taken'])->default('pending')->change();
        });
    }
};
