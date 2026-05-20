<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waitlist_audits', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('waitlist_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('action_type', 100); // created, updated, status_changed, cancelled, completed, converted_to_booking, email_sent, sms_sent
            $table->timestamp('action_at')->nullable()->useCurrent();
            $table->string('previous_status', 50)->nullable();
            $table->string('status', 50)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('start_time', 50)->nullable();
            $table->string('end_time', 50)->nullable();
            $table->decimal('total', 10, 2)->nullable();
            $table->integer('duration')->nullable();
            $table->string('calendar_color', 50)->nullable();
            $table->boolean('send_sms')->default(false);
            $table->boolean('send_email')->default(false);
            $table->text('notes')->nullable();
            $table->json('details_summary')->nullable(); // snapshot of waitlist details (services/items)
            $table->json('meta')->nullable(); // additional metadata (e.g., booking_id for conversions)
            $table->timestamps();

            $table->index('waitlist_id');
            $table->index('customer_id');
            $table->index('company_id');
            $table->index(['waitlist_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waitlist_audits');
    }
};
