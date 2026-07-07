<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::getSchemaBuilder()->hasColumn('payments', 'discount_status');
        if (!$exists) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('discount_status', 20)->nullable()->after('discount_reason');
            });
        }
    }

    public function down(): void
    {
        $exists = DB::getSchemaBuilder()->hasColumn('payments', 'discount_status');
        if ($exists) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn('discount_status');
            });
        }
    }
};
