<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add all foreign key constraints after tables are created.
     * Ordered by migration filename.
     */
    public function up(): void
    {
        // 0001_01_01_000000_create_users_table.php
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
        });

        // 0001_01_01_000002_create_sessions_table.php
        Schema::table('sessions', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // 2026_04_06_091121_create_customers_table.php
        Schema::table('customers', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
        });

        // 2026_04_06_091121_create_services_table.php
        Schema::table('services', function (Blueprint $table) {
            $table->foreign('category_id')->references('id')->on('service_categories')->onDelete('set null');
        });

        // 2026_04_06_091122_create_bookings_table.php
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('recurring_id')->references('id')->on('booking_recurrings')->onDelete('set null');
        });

        // 2026_04_06_091122_create_customer_items_table.php
        Schema::table('customer_items', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });

        // 2026_04_10_033919_create_customer_item_waivers_table.php
        Schema::table('customer_item_waivers', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('customer_items')->onDelete('cascade');
        });

        // 2026_04_10_044242_create_customer_item_waiver_audits_table.php
        Schema::table('customer_item_waiver_audits', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('customer_items')->onDelete('cascade');
            $table->foreign('waiver_id')->references('id')->on('customer_item_waivers')->onDelete('cascade');
        });

        // 2026_04_10_081650_create_customer_item_audits_table.php
        Schema::table('customer_item_audits', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('customer_items')->onDelete('cascade');
        });

        // 2026_04_10_084418_create_customer_audits_table.php
        Schema::table('customer_audits', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });

        // 2026_04_12_053422_create_booking_details_table.php
        Schema::table('booking_details', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('customer_items')->onDelete('cascade');
        });

        // 2026_04_27_000001_create_booking_recurrings_table.php
        Schema::table('booking_recurrings', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });

        // 2026_04_27_000002_create_booking_recurring_details_table.php
        Schema::table('booking_recurring_details', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('recurring_id')->references('id')->on('booking_recurrings')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('customer_items')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
        });

        // 2026_04_27_000005_create_user_services_table.php
        Schema::table('user_services', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
        });

        // 2026_05_12_000001_create_income_categories_table.php
        Schema::table('income_categories', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // 2026_05_12_000002_create_expense_categories_table.php
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // 2026_05_12_000003_create_incomes_table.php
        Schema::table('incomes', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('income_category_id')->references('id')->on('income_categories')->onDelete('set null');
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('set null');
        });

        // 2026_05_12_000004_create_expenses_table.php
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('expense_category_id')->references('id')->on('expense_categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //No need to delete because it will be handled by the migration itself
    }
};
