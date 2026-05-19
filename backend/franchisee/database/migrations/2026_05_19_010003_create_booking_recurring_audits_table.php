<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_recurring_audits', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('booking_recurring_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('action_type', 50);
            $table->timestamp('action_at')->nullable()->useCurrent();
            $table->string('previous_status', 50)->nullable();
            $table->string('status', 50)->nullable();
            $table->date('start_date')->nullable();
            $table->string('repeat_time')->nullable();
            $table->integer('frequency')->nullable();
            $table->string('repeat_day')->nullable();
            $table->boolean('auto_extend')->default(false);
            $table->decimal('total', 10, 2)->nullable();
            $table->integer('duration')->nullable();
            $table->string('color')->nullable();
            $table->text('notes')->nullable();
            $table->date('cancelled_date')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->date('repeat_until')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->timestamps();

            $table->index('booking_recurring_id', 'bkr_aud_recurring_idx');
            $table->index('customer_id', 'bkr_aud_customer_idx');
            $table->index('company_id', 'bkr_aud_company_idx');
            $table->index('performed_by', 'bkr_aud_actor_idx');
            $table->index(['booking_recurring_id', 'created_at'], 'bkr_aud_recurring_created_idx');

            $table->foreign('booking_recurring_id', 'fk_booking_recurring_audits_recurring')->references('id')->on('booking_recurrings')->onDelete('cascade');
            $table->foreign('customer_id', 'fk_booking_recurring_audits_customer')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('company_id', 'fk_booking_recurring_audits_company')->references('id')->on('companies')->onDelete('set null');
            $table->foreign('performed_by', 'fk_booking_recurring_audits_user')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_recurring_audits');
    }
};
