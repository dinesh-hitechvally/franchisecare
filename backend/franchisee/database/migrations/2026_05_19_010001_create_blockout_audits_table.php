<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blockout_audits', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('blockout_id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('action_type', 50);
            $table->timestamp('action_at')->nullable()->useCurrent();
            $table->string('title')->nullable();
            $table->string('location')->nullable();
            $table->date('start_date')->nullable();
            $table->string('start_time')->nullable();
            $table->date('end_date')->nullable();
            $table->string('end_time')->nullable();
            $table->unsignedBigInteger('recurring_id')->nullable();
            $table->string('repeat_every')->nullable();
            $table->string('repeat_on')->nullable();
            $table->date('repeat_until')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->timestamps();

            $table->index('blockout_id', 'blk_aud_blockout_idx');
            $table->index('company_id', 'blk_aud_company_idx');
            $table->index('recurring_id', 'blk_aud_recurring_idx');
            $table->index('performed_by', 'blk_aud_actor_idx');
            $table->index(['blockout_id', 'created_at'], 'blk_aud_blockout_created_idx');

            $table->foreign('blockout_id', 'fk_blockout_audits_blockout')->references('id')->on('blockouts')->onDelete('cascade');
            $table->foreign('company_id', 'fk_blockout_audits_company')->references('id')->on('companies')->onDelete('set null');
            $table->foreign('performed_by', 'fk_blockout_audits_user')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blockout_audits');
    }
};
