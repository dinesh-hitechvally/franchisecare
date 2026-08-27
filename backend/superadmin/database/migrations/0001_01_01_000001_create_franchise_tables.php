<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('franchises', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->string('owner_name');
            $table->string('email')->unique();
            $table->string('phone', 20)->nullable();
            $table->string('mobile', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('suburb', 100)->nullable();
            $table->string('state', 50)->nullable();
            $table->string('postcode', 10)->nullable();
            $table->string('abn', 20)->nullable();
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'SUSPENDED', 'TERMINATED'])->default('ACTIVE');
            $table->string('logo')->nullable();
            $table->decimal('franchise_fee', 10, 2)->nullable();
            $table->decimal('royalty_percentage', 5, 2)->nullable();
            $table->decimal('marketing_fee', 10, 2)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('contract_length')->nullable();
            $table->text('territory')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('franchise_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('franchise_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone', 20)->nullable();
            $table->enum('role', ['OWNER', 'MANAGER', 'STAFF'])->default('STAFF');
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->string('avatar')->nullable();
            $table->timestamps();
        });

        Schema::create('franchise_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('franchise_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('service_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('duration');
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->timestamps();
        });

        Schema::create('franchise_suburbs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('franchise_id')->constrained()->onDelete('cascade');
            $table->string('suburb_name', 100);
            $table->string('postcode', 10);
            $table->string('state', 50);
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->timestamps();
        });

        Schema::create('franchise_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('franchise_id')->constrained()->onDelete('cascade');
            $table->enum('payment_type', ['ROYALTY', 'MARKETING', 'FEE', 'OTHER']);
            $table->decimal('amount', 10, 2);
            $table->date('payment_date')->nullable();
            $table->date('due_date')->nullable();
            $table->enum('status', ['PENDING', 'PAID', 'OVERDUE', 'CANCELLED'])->default('PENDING');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('franchise_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('franchise_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('action');
            $table->json('changes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('franchise_audits');
        Schema::dropIfExists('franchise_payments');
        Schema::dropIfExists('franchise_suburbs');
        Schema::dropIfExists('franchise_services');
        Schema::dropIfExists('franchise_users');
        Schema::dropIfExists('franchises');
    }
};
