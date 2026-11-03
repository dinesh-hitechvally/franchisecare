<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The media library: files a company uploads once and then picks from (company logo, …).
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');                       // the label shown in the library (renamable)
            $table->string('file_name');                  // original file name, kept for downloads and the public link
            $table->string('path', 500);                  // where the file lives on disk (private storage)
            $table->string('token', 40)->unique();        // the unguessable part of the public link /api/m/{token}/{file_name}
            $table->string('mime', 100);
            $table->string('kind', 12)->default('image'); // image | document
            $table->unsignedBigInteger('size');           // bytes
            $table->unsignedInteger('width')->nullable(); // images only
            $table->unsignedInteger('height')->nullable();
            $table->string('alt')->nullable();            // describes an image for people who cannot see it
            $table->text('description')->nullable();      // free notes about the file, shown in the details window
            $table->string('source', 20)->default('upload'); // upload, or the free source it was added from: openverse | wikimedia | unsplash | pexels | pixabay
            $table->string('credit')->nullable();         // photographer, for images from a free source
            $table->string('credit_url', 500)->nullable();
            $table->string('license', 100)->nullable();
            $table->string('source_url', 500)->nullable(); // the photo's page at the source
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();

            $table->index(['company_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
