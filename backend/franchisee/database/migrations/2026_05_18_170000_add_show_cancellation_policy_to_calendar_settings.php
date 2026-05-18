<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('calendar_settings') && !Schema::hasColumn('calendar_settings', 'show_cancellation_policy')) {
            Schema::table('calendar_settings', function (Blueprint $table) {
                $table->boolean('show_cancellation_policy')->default(true)->after('show_services_name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('calendar_settings') && Schema::hasColumn('calendar_settings', 'show_cancellation_policy')) {
            Schema::table('calendar_settings', function (Blueprint $table) {
                $table->dropColumn('show_cancellation_policy');
            });
        }
    }
};
