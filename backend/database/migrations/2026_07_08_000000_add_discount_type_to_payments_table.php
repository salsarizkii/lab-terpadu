<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('discount_type', 10)->nullable()->after('discount_reason');
            $table->decimal('discount_percent', 5, 2)->nullable()->after('discount_type');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_percent']);
        });
    }
};
