<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people_list_members', function (Blueprint $table) {
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->foreignId('people_list_id')->constrained()->cascadeOnDelete();
            $table->primary(['person_id', 'people_list_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('people_list_members');
    }
};
