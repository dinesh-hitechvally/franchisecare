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
        if (Schema::hasTable('policy_attach_options') && !Schema::hasColumn('policy_attach_options', 'cancel_cutoff_time')) {
            Schema::table('policy_attach_options', function (Blueprint $table) {
                $table->time('cancel_cutoff_time')->nullable()->after('cancel_before_value');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('policy_attach_options') && Schema::hasColumn('policy_attach_options', 'cancel_cutoff_time')) {
            Schema::table('policy_attach_options', function (Blueprint $table) {
                $table->dropColumn('cancel_cutoff_time');
            });
        }
    }
};
