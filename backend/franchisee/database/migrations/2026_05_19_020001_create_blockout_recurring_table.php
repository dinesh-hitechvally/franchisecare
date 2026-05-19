<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blockout_recurrings', function (Blueprint $table) {

            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('title');
            $table->string('location')->nullable();
            $table->date('start_date');
            $table->string('start_time');
            $table->date('end_date');
            $table->string('end_time');
            $table->string('repeat_every')->nullable();
            $table->string('repeat_on')->nullable();
            $table->date('repeat_until')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('company_id');
            $table->index(['company_id', 'repeat_until']);

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blockout_recurrings');
    }
};
