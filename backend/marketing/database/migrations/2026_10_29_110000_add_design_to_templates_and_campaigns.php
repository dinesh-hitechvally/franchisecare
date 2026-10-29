<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Visual-designer JSON (blocks + settings) kept next to the rendered HTML body. */
    public function up(): void
    {
        foreach (['templates', 'campaigns'] as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->longText('design')->nullable()->after('body'));
        }
    }

    public function down(): void
    {
        foreach (['templates', 'campaigns'] as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropColumn('design'));
        }
    }
};
