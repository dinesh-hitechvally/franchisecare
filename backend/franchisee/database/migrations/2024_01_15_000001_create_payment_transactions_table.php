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
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('user_id');
            $table->string('transaction_id')->nullable()->unique();
            $table->string('type'); // credit_purchase, order, booking
            $table->string('reference_type')->nullable(); // sms_credit, inventory_order, booking
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('AUD');
            $table->string('status')->default('pending'); // pending, completed, failed, refunded, voided
            $table->string('payment_method')->default('credit_card');
            $table->string('card_last_four', 4)->nullable();
            $table->string('card_brand')->nullable();
            $table->string('authorization_code')->nullable();
            $table->string('response_code')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
