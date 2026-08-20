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
        Schema::create('xero_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('reference_type'); // customer, default_supplier
            $table->unsignedBigInteger('reference_id')->nullable(); // null for the single default_supplier contact
            $table->string('xero_contact_id')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('status')->default('pending'); // pending, synced, failed
            $table->timestamp('synced_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('xero_contacts');
    }
};
