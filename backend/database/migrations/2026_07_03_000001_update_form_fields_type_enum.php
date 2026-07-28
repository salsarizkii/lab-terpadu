<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }
        DB::statement("ALTER TABLE `form_fields` MODIFY COLUMN `type` ENUM('text', 'textarea', 'number', 'select', 'checkbox', 'radio', 'date', 'time', 'email', 'phone', 'file', 'heading', 'paragraph', 'divider') NOT NULL DEFAULT 'text'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }
        DB::statement("ALTER TABLE `form_fields` MODIFY COLUMN `type` ENUM('text', 'textarea', 'number', 'select', 'checkbox', 'date', 'file') NOT NULL DEFAULT 'text'");
    }
};
