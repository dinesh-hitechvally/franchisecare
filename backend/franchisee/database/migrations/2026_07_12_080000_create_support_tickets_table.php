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
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_id', 50)->unique();
            $table->string('subject');
            $table->enum('department', ['BUGS', 'ENHANCEMENT', 'ADMIN', 'URGENT'])->default('BUGS');
            $table->unsignedBigInteger('user_id');
            $table->string('created_by_name');
            $table->string('last_updated_by_name');
            $table->enum('status', ['OPEN', 'IN-PROGRESS', 'CLOSED'])->default('OPEN');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
