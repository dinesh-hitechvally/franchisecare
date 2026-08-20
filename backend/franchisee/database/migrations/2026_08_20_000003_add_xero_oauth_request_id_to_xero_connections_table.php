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
        Schema::table('xero_connections', function (Blueprint $table) {
            $table->foreignId('xero_oauth_request_id')
                ->nullable()
                ->after('tenant_type')
                ->constrained('xero_oauth_requests')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('xero_connections', function (Blueprint $table) {
            $table->dropForeign(['xero_oauth_request_id']);
            $table->dropColumn('xero_oauth_request_id');
        });
    }
};
