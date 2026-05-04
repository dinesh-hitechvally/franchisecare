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
        Schema::create('booking_recurrings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            
            $table->date('start_date');
            $table->string('repeat_time');
            $table->integer('frequency'); // Number of weeks (1-20)
            $table->enum('status', ['active', 'cancelled', 'completed'])->default('active');
            $table->boolean('auto_extend')->default(false);
            $table->decimal('total', 10, 2);
            $table->integer('duration'); // in minutes
            $table->string('color')->nullable();
            $table->text('notes')->nullable();
            $table->date('cancelled_date')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->date('repeat_until')->nullable();
            $table->timestamps();

            $table->index('customer_id');
            $table->index('company_id');
            $table->index('status');
            $table->index(['status', 'company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_recurrings');
    }
};
