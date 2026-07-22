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
        Schema::create('attachments', function (Blueprint $table) {

            // Primary key
            $table->bigIncrements('id');

            // File metadata
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('file_name', 255);
            $table->text('module');
            $table->string('file_path', 255);

            $table->string('origional_name', 255);           
            $table->unsignedBigInteger('file_size'); // size in bytes
            $table->string('mime_type', 127);
            $table->string('extension', 20);

            // Ownership & authorization
            $table->unsignedBigInteger('uploaded_by');

            // Additional metadata
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // JSONB in PostgreSQL, JSON in MySQL

            // Soft delete & timestamps
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes(); // adds deleted_at column

        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }

};