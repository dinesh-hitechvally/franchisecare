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
        Schema::table('blockouts', function (Blueprint $table) {
            $table->dropColumn(['repeat_every', 'repeat_on', 'repeat_until']);
        });

        Schema::table('blockout_audits', function (Blueprint $table) {
            $table->dropColumn(['repeat_every', 'repeat_on', 'repeat_until']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blockouts', function (Blueprint $table) {
            $table->string('repeat_every')->nullable()->after('recurring_id');
            $table->string('repeat_on')->nullable()->after('repeat_every');
            $table->date('repeat_until')->nullable()->after('repeat_on');
        });

        Schema::table('blockout_audits', function (Blueprint $table) {
            $table->string('repeat_every')->nullable()->after('recurring_id');
            $table->string('repeat_on')->nullable()->after('repeat_every');
            $table->date('repeat_until')->nullable()->after('repeat_on');
        });
    }
};
