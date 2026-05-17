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
        Schema::create('calendar_settings', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->boolean('show_booking_total')->default(true);
            $table->boolean('show_customer_name')->default(true);
            $table->boolean('show_customer_address')->default(true);
            $table->boolean('show_pet_name')->default(true);
            $table->boolean('show_pet_breed')->default(true);
            $table->boolean('show_services_name')->default(true);
            $table->timestamps();

            $table->unique('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_settings');
    }
};
