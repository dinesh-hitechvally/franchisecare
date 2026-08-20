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
        Schema::table('xero_invoices', function (Blueprint $table) {
            $table->dropForeign(['xero_connection_id']);
            $table->dropColumn('xero_connection_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('xero_invoices', function (Blueprint $table) {
            $table->foreignId('xero_connection_id')->nullable()->after('company_id')->constrained('xero_connections')->onDelete('cascade');
        });
    }
};
