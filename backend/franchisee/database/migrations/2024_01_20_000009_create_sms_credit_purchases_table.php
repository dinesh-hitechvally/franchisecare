<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_credit_purchases', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('user_id');
            $table->string('package_id');
            $table->integer('quantity');
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->timestamp('purchased_at')->nullable();
            $table->timestamps();

            $table->index('company_id');
            $table->index('user_id');
            $table->index(['company_id', 'purchased_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_credit_purchases');
    }
};
