<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate any 'requested' rows to 'active' before removing from enum
        DB::table('waitlists')->where('status', 'requested')->update(['status' => 'active']);

        DB::statement("ALTER TABLE waitlists MODIFY COLUMN status ENUM('active','cancelled','completed','expired') NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE waitlists MODIFY COLUMN status ENUM('active','cancelled','completed','expired','requested') NOT NULL DEFAULT 'active'");
    }
};
