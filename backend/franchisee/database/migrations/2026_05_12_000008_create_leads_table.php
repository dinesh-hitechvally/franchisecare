<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('customer_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone');
            $table->string('alternate_phone')->nullable();
            $table->string('interested_services')->nullable();
            $table->text('address')->nullable();
            $table->string('suburb')->nullable();
            $table->string('postcode')->nullable();
            $table->string('pet_breed')->nullable();
            $table->string('referred_by')->nullable();
            $table->text('additional_note')->nullable();
            $table->text('notes')->nullable();
            $table->enum('source', ['PHONE', 'INTERNET', 'WALK-IN', 'REFERRAL'])->default('INTERNET');
            $table->enum('leads_from', ['PHONE', 'INTERNET'])->default('INTERNET');
            $table->enum('status', ['NEW', 'CONTACTED', 'QUALIFIED', 'CONVERTED', 'LOST', 'SNOOZED', 'COMPLETED', 'CANCELLATION_REQUEST', 'MESSAGE_FOR_OPERATOR'])->default('NEW');
            $table->timestamp('snoozed_until')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('company_id');
            $table->index('status');
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
