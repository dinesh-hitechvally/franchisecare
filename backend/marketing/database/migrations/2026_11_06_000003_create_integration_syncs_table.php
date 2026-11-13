<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per import run of a connection: what it fetched and what it did with it.
        Schema::create('integration_syncs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('integration_id')->constrained()->cascadeOnDelete();
            $table->enum('trigger', ['MANUAL', 'SCHEDULED', 'CONNECT'])->default('MANUAL');   // manual | scheduled | connect
            $table->boolean('full')->default(false);            // true: every customer, not only the ones changed since the last sync
            $table->enum('status', ['RUNNING', 'SUCCESS', 'FAILED'])->default('RUNNING');   // running | success | failed
            $table->dateTime('since')->nullable();              // only customers changed since this moment (null = all)
            $table->string('cursor', 500)->nullable();          // where the next chunk of the import continues
            $table->unsignedInteger('fetched')->default(0);
            $table->unsignedInteger('created')->default(0);
            $table->unsignedInteger('updated')->default(0);
            $table->unsignedInteger('skipped')->default(0);
            $table->text('error')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->index(['integration_id', 'id']);
            $table->index(['company_id', 'status']);
            $table->unsignedInteger('exported')->default(0);      // two-way connections: people sent to the platform
            $table->unsignedInteger('export_failed')->default(0); // ... and how many the platform refused
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_syncs');
    }
};
