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
        Schema::create('customer_item_waivers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('item_id')->nullable();
            $table->string('waiver_type')->default('intake');
            $table->text('cologne_decline_reason')->nullable();
            $table->text('shampoo_own_reason')->nullable();
            $table->string('delivery_date')->nullable();

            // Intake Form Fields
            $table->string('age')->nullable();
            $table->string('weight')->nullable();
            $table->boolean('is_vaccinated')->default(false);

            // Health
            $table->boolean('health_arthritis')->default(false);
            $table->boolean('health_epilepsy')->default(false);
            $table->boolean('health_collapsing_trachea')->default(false);
            $table->boolean('health_heart_disease')->default(false);
            $table->boolean('health_diabetes')->default(false);
            $table->boolean('health_chronic_skin')->default(false);
            $table->boolean('health_chronic_ear')->default(false);
            $table->boolean('health_allergies')->default(false);
            $table->text('other_health')->nullable();

            // Sensitivities
            $table->string('sensitivity_skin')->default('no');
            $table->string('sensitivity_products')->default('no');
            $table->string('sensitivity_vet_advice')->default('no');

            // Behavioural
            $table->boolean('behavioural_fearful')->default(false);
            $table->boolean('behavioural_aggressive')->default(false);
            $table->boolean('behavioural_anxious')->default(false);
            $table->boolean('behavioural_none_known')->default(false);
            $table->text('behavioural_others')->nullable();

            // Dislikes
            $table->boolean('dislike_head')->default(false);
            $table->boolean('dislike_paws')->default(false);
            $table->boolean('dislike_tail')->default(false);
            $table->boolean('dislike_other')->default(false);

            // Experience
            $table->string('grooming_prof_groomed')->default('no');
            $table->string('grooming_prev_issues')->default('no');

            $table->string('tick_prevention')->default('no');
            $table->boolean('accepted_expectations')->default(false);
            $table->string('signature_path')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_item_waivers');
    }
};
