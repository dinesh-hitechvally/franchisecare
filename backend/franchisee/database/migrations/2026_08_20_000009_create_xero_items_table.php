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
        Schema::create('xero_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('reference_type')->default('inventory_item');
            $table->unsignedBigInteger('reference_id');
            $table->string('xero_item_id')->nullable();
            $table->string('code');
            $table->string('name');
            $table->boolean('is_tracked_as_inventory')->default(true);
            $table->string('inventory_asset_account_code')->nullable();
            $table->string('purchase_account_code')->nullable();
            $table->decimal('purchase_unit_price', 10, 2)->nullable();
            $table->string('purchase_tax_type')->nullable();
            $table->string('sales_account_code')->nullable();
            $table->decimal('sales_unit_price', 10, 2)->nullable();
            $table->string('sales_tax_type')->nullable();
            $table->decimal('quantity_on_hand', 10, 2)->nullable();
            $table->string('status')->default('pending'); // pending, synced, failed
            $table->timestamp('synced_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('xero_items');
    }
};
