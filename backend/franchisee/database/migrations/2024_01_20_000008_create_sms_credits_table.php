<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_credits', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->integer('balance')->default(0);
            $table->integer('total_purchased')->default(0);
            $table->integer('total_used')->default(0);
            $table->timestamps();

            $table->unique('company_id');
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_credits');
    }
};
