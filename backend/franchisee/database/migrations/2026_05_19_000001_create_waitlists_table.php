<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waitlists', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('start_time');
            $table->string('end_time')->nullable();
            $table->enum('status', ['ACTIVE', 'CANCELLED', 'COMPLETED', 'EXPIRED'])->default('ACTIVE');
            $table->decimal('total', 10, 2)->default(0);
            $table->integer('duration')->default(0); // in minutes
            $table->string('calendar_color')->nullable();
            $table->boolean('send_sms')->default(false);
            $table->boolean('send_email')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waitlists');
    }
};
