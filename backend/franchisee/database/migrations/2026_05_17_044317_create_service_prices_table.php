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
        Schema::create('service_prices', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->decimal('my_price', 10, 2)->default(0);
            $table->decimal('default_price', 10, 2)->default(0);
            $table->string('color')->default('#4169E1');
            $table->integer('my_time')->default(0);
            $table->integer('default_time')->default(0);
            $table->timestamps();

            $table->index('company_id');
            $table->unique(['company_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_prices');
    }
};
