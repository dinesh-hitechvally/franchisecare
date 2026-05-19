<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waitlist_details', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('waitlist_id');
            $table->unsignedBigInteger('service_id')->nullable();
            $table->unsignedBigInteger('item_id')->nullable(); // customer item (pet)
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('duration')->default(0);
            $table->timestamps();

            $table->foreign('waitlist_id')->references('id')->on('waitlists')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('set null');
            $table->foreign('item_id')->references('id')->on('customer_items')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waitlist_details');
    }
};
