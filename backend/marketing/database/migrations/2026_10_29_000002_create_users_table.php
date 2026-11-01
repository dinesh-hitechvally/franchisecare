<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete(); // the company this user belongs to
            $table->string('name');
            $table->string('email');
            $table->string('phone', 40)->nullable();
            $table->string('job_title', 120)->nullable();
            $table->dateTime('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
            // unique only among rows that are not deleted (MySQL ignores NULLs in a unique index)
            $table->string('email_active')->nullable()->virtualAs('CASE WHEN deleted_at IS NULL THEN `email` END');
            $table->unique('email_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
