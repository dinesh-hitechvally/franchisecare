<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->integer('balance')->default(0);
            $table->integer('total_purchased')->default(0);
            $table->integer('total_used')->default(0);
            $table->timestamps();
            
            $table->unique('company_id');
        });

        Schema::create('sms_credit_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('package_id');
            $table->integer('quantity');
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->timestamp('purchased_at')->nullable();
            $table->timestamps();
            
            $table->index(['company_id', 'purchased_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_credit_purchases');
        Schema::dropIfExists('sms_credits');
    }
};
