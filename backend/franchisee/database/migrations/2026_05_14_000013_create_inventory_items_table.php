<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('sku')->nullable();
            $table->boolean('booking_usage')->default(false);
            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('min_stock', 10, 2)->default(0);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('company_id');
            $table->index('unit_id');
            $table->index(['company_id', 'category_id']);
            $table->index(['company_id', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};