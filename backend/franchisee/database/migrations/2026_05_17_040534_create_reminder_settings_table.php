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
        Schema::create('reminder_settings', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->enum('reminder_method', [
                'NO-SEND',
                'EMAIL-SMS',
                'EMAIL-ONLY',
                'SMS-ONLY',
                'EMAIL-IF-NO-MOBILE',
                'SMS-IF-NO-EMAIL'
            ])->default('EMAIL-ONLY');
            $table->integer('send_before_hours')->default(24);
            $table->timestamps();

            $table->unique('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminder_settings');
    }
};
