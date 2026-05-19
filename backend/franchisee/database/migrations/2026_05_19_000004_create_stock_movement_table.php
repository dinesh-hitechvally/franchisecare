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
        if (!Schema::hasTable('stock_movement')) {
            Schema::create('stock_movement', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('category_id')->nullable()->index();
                $table->unsignedBigInteger('inventory_id')->nullable()->index();
                $table->unsignedBigInteger('batch_id')->nullable()->index();
                $table->enum('movement_type', ['stock_take', 'booking_usage', 'adjustment', 'inward', 'write_off'])->default('stock_take');
                $table->integer('quantity_change')->default(0);
                $table->decimal('percentage_change', 5, 2)->default(0);
                $table->unsignedBigInteger('quantity_before')->default(0);
                $table->unsignedBigInteger('quantity_after')->default(0);
                $table->decimal('percentage_before', 5, 2)->default(0);
                $table->decimal('percentage_after', 5, 2)->default(0);
                $table->string('reference_type')->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('performed_by')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movement');
    }
};
