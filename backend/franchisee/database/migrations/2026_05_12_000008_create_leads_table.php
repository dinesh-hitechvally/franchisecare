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
            $table->enum('source', ['phone', 'internet', 'walk-in', 'referral'])->default('internet');
            $table->enum('leads_from', ['phone', 'internet'])->default('internet');
            $table->enum('status', ['new', 'contacted', 'qualified', 'converted', 'lost', 'snoozed', 'completed', 'cancellation_request', 'message_for_operator'])->default('new');
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
