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
        Schema::table('booking_recurrings', function (Blueprint $table) {
            // Rename start_time to repeat_time
            $table->renameColumn('start_time', 'repeat_time');
            // Drop end_time column
            $table->dropColumn('end_time');
            // Change frequency from enum to integer (1-20)
            $table->integer('frequency')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_recurrings', function (Blueprint $table) {
            // Reverse changes
            $table->renameColumn('repeat_time', 'start_time');
            $table->string('end_time')->nullable();
            $table->enum('frequency', ['daily', 'weekly', 'fortnightly', 'monthly'])->default('weekly')->change();
        });
    }
};
