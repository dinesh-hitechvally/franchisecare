<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_service_inventory_usages', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('service_id');
            $table->unsignedBigInteger('inventory_id');
            $table->decimal('quantity_per_booking', 10, 2)->default(0);
            $table->unsignedBigInteger('unit_id');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('company_id');
            $table->index('service_id');
            $table->index('inventory_id');
            $table->index('unit_id');
            $table->index(['company_id', 'service_id']);
            $table->index(['company_id', 'inventory_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_service_inventory_usages');
    }
};
