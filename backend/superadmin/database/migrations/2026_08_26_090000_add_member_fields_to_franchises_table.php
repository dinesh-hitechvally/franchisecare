<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('franchises', function (Blueprint $table) {
            $table->enum('franchisee_type', ['MASTER_FRANCHISEE', 'FRANCHISEE', 'FRANCHISOR'])->nullable()->after('status');
            $table->boolean('has_ipad')->default(false)->after('franchisee_type');
            $table->boolean('tscs_accepted')->default(false)->after('has_ipad');
            $table->timestamp('tscs_accepted_at')->nullable()->after('tscs_accepted');
        });
    }

    public function down(): void
    {
        Schema::table('franchises', function (Blueprint $table) {
            $table->dropColumn(['franchisee_type', 'has_ipad', 'tscs_accepted', 'tscs_accepted_at']);
        });
    }
};
