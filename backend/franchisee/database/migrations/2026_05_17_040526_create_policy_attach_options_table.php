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
        Schema::create('policy_attach_options', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->boolean('attach_policy')->default(false);
            $table->enum('cancel_before_unit', ['hours', 'days'])->default('hours');
            $table->integer('cancel_before_value')->default(24);
            $table->decimal('cancellation_fee_value', 10, 2)->default(0);
            $table->enum('penalty_type', ['percent', 'fixed'])->default('percent');
            $table->unsignedBigInteger('policy_id')->nullable();
            $table->foreign('policy_id')->references('id')->on('cancellation_policies')->onDelete('cascade');
            $table->text('policy_text')->nullable();
            $table->timestamps();

            $table->unique('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policy_attach_options');
    }
};
