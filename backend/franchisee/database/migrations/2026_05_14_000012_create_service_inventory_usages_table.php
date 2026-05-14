<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_inventory_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->string('inventory_name');
            $table->decimal('quantity_per_booking', 10, 2)->default(0);
            $table->string('unit');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_inventory_usages');
    }
};