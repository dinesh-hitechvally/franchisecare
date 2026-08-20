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
        Schema::create('xero_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('xero_connection_id')->constrained('xero_connections')->onDelete('cascade');
            $table->string('reference_type'); // booking, inventory_order
            $table->unsignedBigInteger('reference_id');
            $table->string('xero_invoice_id')->nullable(); // Xero's InvoiceID
            $table->string('xero_invoice_number')->nullable();
            $table->string('invoice_type'); // ACCREC, ACCPAY
            $table->string('status')->default('pending'); // pending, synced, failed
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('AUD');
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
        Schema::dropIfExists('xero_invoices');
    }
};
