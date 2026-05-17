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
        Schema::create('preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->boolean('display_customer_notes')->default(true);
            $table->boolean('hide_expired_bookings')->default(true);
            $table->boolean('hide_booking_cash_notifications')->default(true);
            $table->boolean('hide_past_bookings')->default(false);
            $table->boolean('filter_services_by_pet_size')->default(false);
            $table->boolean('display_booking_end_time')->default(true);
            $table->boolean('show_address_in_invoice')->default(true);
            $table->boolean('show_personal_phone')->default(true);
            $table->boolean('time_format_24hr')->default(false);
            $table->timestamps();

            $table->unique('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preferences');
    }
};
