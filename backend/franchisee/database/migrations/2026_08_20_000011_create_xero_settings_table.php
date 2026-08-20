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
        Schema::create('xero_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->onDelete('cascade');
            $table->string('default_supplier_name')->nullable();
            $table->string('bank_account_code')->nullable();
            $table->string('inventory_asset_account_code')->nullable();
            $table->string('inventory_cogs_account_code')->nullable();
            $table->string('inventory_sales_account_code')->nullable();
            $table->string('service_sales_account_code')->nullable();
            $table->string('default_tax_type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('xero_settings');
    }
};
