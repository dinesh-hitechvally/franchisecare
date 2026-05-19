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
        if (!Schema::hasTable('current_soh')) {
            Schema::create('current_soh', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('category_id')->nullable()->index();
                $table->unsignedBigInteger('inventory_id')->nullable()->index();
                $table->unsignedBigInteger('current_quantity')->default(0);
                $table->decimal('current_percentage', 5, 2)->default(0);
                $table->timestamps();

                $table->unique(['company_id', 'inventory_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('current_soh');
    }
};
