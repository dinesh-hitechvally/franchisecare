<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('income_audits', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('income_id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('action_type', 50);
            $table->timestamp('action_at')->nullable()->useCurrent();
            $table->unsignedBigInteger('income_category_id')->nullable();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->date('income_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('recurring_income_id')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->timestamps();

            $table->index('income_id', 'inc_aud_income_idx');
            $table->index('company_id', 'inc_aud_company_idx');
            $table->index('performed_by', 'inc_aud_actor_idx');
            $table->index(['income_id', 'created_at'], 'inc_aud_income_created_idx');

            $table->foreign('income_id', 'fk_income_audits_income')->references('id')->on('incomes')->onDelete('cascade');
            $table->foreign('company_id', 'fk_income_audits_company')->references('id')->on('companies')->onDelete('set null');
            $table->foreign('performed_by', 'fk_income_audits_user')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('income_audits');
    }
};
