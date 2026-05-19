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
        if (!Schema::hasTable('stock_take_items')) {

            Schema::create('stock_take_items', function (Blueprint $table) {

                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('category_id')->nullable()->index();
                $table->unsignedBigInteger('batch_id')->nullable()->index();
                $table->unsignedBigInteger('inventory_id')->nullable()->index();
                $table->unsignedBigInteger('available_quantity');
                $table->decimal('available_percentage', 5, 2);
                $table->timestamps();

            });
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_take_items');
    }
};
