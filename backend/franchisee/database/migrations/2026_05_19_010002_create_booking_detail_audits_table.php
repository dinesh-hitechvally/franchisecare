<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_detail_audits', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('booking_detail_id');
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('action_type', 50);
            $table->timestamp('action_at')->nullable()->useCurrent();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->unsignedBigInteger('item_id')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->integer('duration')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->timestamps();

            $table->index('booking_detail_id', 'bkd_aud_detail_idx');
            $table->index('booking_id', 'bkd_aud_booking_idx');
            $table->index('company_id', 'bkd_aud_company_idx');
            $table->index('performed_by', 'bkd_aud_actor_idx');
            $table->index(['booking_detail_id', 'created_at'], 'bkd_aud_detail_created_idx');

            $table->foreign('booking_detail_id', 'fk_booking_detail_audits_detail')->references('id')->on('booking_details')->onDelete('cascade');
            $table->foreign('booking_id', 'fk_booking_detail_audits_booking')->references('id')->on('bookings')->onDelete('set null');
            $table->foreign('company_id', 'fk_booking_detail_audits_company')->references('id')->on('companies')->onDelete('set null');
            $table->foreign('performed_by', 'fk_booking_detail_audits_user')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_detail_audits');
    }
};
