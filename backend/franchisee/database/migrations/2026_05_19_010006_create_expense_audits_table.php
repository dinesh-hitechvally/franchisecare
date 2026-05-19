<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_audits', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('expense_id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('action_type', 50);
            $table->timestamp('action_at')->nullable()->useCurrent();
            $table->unsignedBigInteger('expense_category_id')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->date('expense_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('recurring_expense_id')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->timestamps();

            $table->index('expense_id', 'exp_aud_expense_idx');
            $table->index('company_id', 'exp_aud_company_idx');
            $table->index('performed_by', 'exp_aud_actor_idx');
            $table->index(['expense_id', 'created_at'], 'exp_aud_expense_created_idx');

            $table->foreign('expense_id', 'fk_expense_audits_expense')->references('id')->on('expenses')->onDelete('cascade');
            $table->foreign('company_id', 'fk_expense_audits_company')->references('id')->on('companies')->onDelete('set null');
            $table->foreign('performed_by', 'fk_expense_audits_user')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_audits');
    }
};
