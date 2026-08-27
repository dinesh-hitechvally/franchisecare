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
        Schema::table('calendar_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('calendar_settings', 'show_time')) {
                $table->boolean('show_time')->default(true)->after('show_pet_name');
            }
            if (! Schema::hasColumn('calendar_settings', 'display_order')) {
                $table->json('display_order')->nullable()->after('show_cancellation_policy');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calendar_settings', function (Blueprint $table) {
            $table->dropColumn(['show_time', 'display_order']);
        });
    }
};
