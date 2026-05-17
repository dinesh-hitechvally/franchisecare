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
        Schema::create('app_calendar_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->boolean('show_customer_name')->default(true);
            $table->boolean('show_customer_address')->default(true);
            $table->boolean('show_booking_total')->default(true);
            $table->boolean('show_time')->default(true);
            $table->boolean('show_pet_name')->default(true);
            $table->boolean('show_services_name')->default(true);
            $table->boolean('show_pet_breed')->default(true);
            $table->json('display_order')->nullable();
            $table->timestamps();

            $table->unique('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_calendar_settings');
    }
};
