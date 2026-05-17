<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_expenses', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('expense_category_id')->nullable();
            
            $table->date('start_date');
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'yearly'])->default('weekly');
            $table->enum('status', ['active', 'cancelled', 'completed'])->default('active');
            $table->boolean('auto_extend')->default(false);
            $table->decimal('total', 10, 2);
            $table->text('notes')->nullable();
            $table->date('cancelled_date')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->date('repeat_until')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('company_id');
            $table->index('status');
            $table->index(['status', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_expenses');
    }
};
