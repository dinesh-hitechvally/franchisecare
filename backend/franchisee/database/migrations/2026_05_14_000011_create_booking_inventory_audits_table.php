<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_inventory_audits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('inventory_id')->nullable();
            $table->string('inventory_item_name')->nullable();
            $table->string('change_type', 100);
            $table->integer('quantity_before')->nullable();
            $table->integer('quantity_after')->nullable();
            $table->integer('quantity_change')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('booking_id');
            $table->index('company_id');
            $table->index(['booking_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_inventory_audits');
    }
};