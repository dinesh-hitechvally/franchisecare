<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create support_departments table
        Schema::create('support_departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->timestamps();
        });

        // 2. Insert default departments
        DB::table('support_departments')->insert([
            ['name' => 'Bugs', 'code' => 'bugs', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Enhancement Requests', 'code' => 'enhancement', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Admin Tickets', 'code' => 'admin', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Urgent Tickets', 'code' => 'urgent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 3. Recreate department column on support_tickets as string
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropColumn('department');
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->string('department')->default('bugs')->after('subject');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropColumn('department');
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->enum('department', ['bugs', 'enhancement', 'admin', 'urgent'])->default('bugs')->after('subject');
        });

        Schema::dropIfExists('support_departments');
    }
};
