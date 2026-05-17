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

        // 2026_04_27_000005_create_company_services_table.php
        Schema::table('company_services', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
        });

        // 2026_05_11_000001_create_sms_history_table.php
        Schema::table('sms_history', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
        });

        // 2026_05_11_000002_create_email_history_table.php
        Schema::table('email_history', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        // 2026_05_12_000010_ensure_documents_table_exists.php
        Schema::table('documents', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
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

        // 2026_05_14_000010_create_booking_audits_table.php
        Schema::table('booking_audits', function (Blueprint $table) {
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
        });

        // 2026_05_14_000011_create_booking_inventory_audits_table.php
        Schema::table('booking_inventory_audits', function (Blueprint $table) {
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
        });

        // 2026_05_17_040458_create_preferences_table.php
        Schema::table('preferences', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // 2026_05_17_040508_create_income_templates_table.php
        Schema::table('income_templates', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // 2026_05_17_040517_create_calendar_settings_table.php
        Schema::table('calendar_settings', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // 2026_05_17_040526_create_cancellation_policies_table.php
        Schema::table('cancellation_policies', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // 2026_05_17_040534_create_reminder_settings_table.php
        Schema::table('reminder_settings', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // 2026_05_17_040543_create_app_calendar_settings_table.php
        Schema::table('app_calendar_settings', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // 2026_05_17_044317_create_service_prices_table.php
        Schema::table('service_prices', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // 2026_05_14_000013_create_inventory_items_table.php
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');
        });

        // 2026_05_17_031131_recreate_inventory_unit_conversions_table_with_ids.php
        Schema::table('inventory_unit_conversions', function (Blueprint $table) {
            $table->foreign('from_unit_id')->references('id')->on('units')->onDelete('cascade');
            $table->foreign('to_unit_id')->references('id')->on('units')->onDelete('cascade');
            $table->foreign('inventory_id')->references('id')->on('inventory_items')->onDelete('set null');
        });

        // 2026_05_14_000012_create_service_inventory_usages_table.php
        Schema::table('service_inventory_usages', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');
        });

        // 2026_05_14_084217_create_inventory_orders_table.php
        Schema::table('inventory_orders', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        // 2026_05_14_084225_create_inventory_order_items_table.php
        Schema::table('inventory_order_items', function (Blueprint $table) {
            $table->foreign('inventory_order_id')->references('id')->on('inventory_orders')->onDelete('cascade');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('set null');
        });

        // 2024_01_20_000008_create_sms_credits_table.php
        Schema::table('sms_credits', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // 2024_01_20_000009_create_sms_credit_purchases_table.php
        Schema::table('sms_credit_purchases', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // 2024_01_20_000007_create_website_settings_table.php
        Schema::table('website_settings', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // 2026_05_04_000003_create_forum_threads_table.php
        Schema::table('forum_threads', function (Blueprint $table) {
            $table->foreign('author_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('group_id')->references('id')->on('forum_groups')->onDelete('set null');
        });

        // 2026_05_04_000002_create_news_table.php
        Schema::table('news', function (Blueprint $table) {
            $table->foreign('author_id')->references('id')->on('users')->onDelete('set null');
        });

        // 2026_05_04_000004_create_blockouts_table.php
        Schema::table('blockouts', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // 2026_05_04_000005_create_forum_comments_table.php
        Schema::table('forum_comments', function (Blueprint $table) {
            $table->foreign('thread_id')->references('id')->on('forum_threads')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('forum_comments')->onDelete('cascade');
            $table->foreign('author_id')->references('id')->on('users')->onDelete('cascade');
        });

        // 2026_05_07_061056_create_calendar_events_table.php
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
            $table->foreign('blockout_id')->references('id')->on('blockouts')->onDelete('cascade');
        });

        // 2026_05_07_082146_create_forum_groups_table.php
        Schema::table('forum_groups', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        // 2026_05_07_082147_create_forum_group_members_table.php
        Schema::table('forum_group_members', function (Blueprint $table) {
            $table->foreign('group_id')->references('id')->on('forum_groups')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // 2026_05_08_000001_create_forum_thread_likes_table.php
        Schema::table('forum_thread_likes', function (Blueprint $table) {
            $table->foreign('forum_thread_id')->references('id')->on('forum_threads')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // 2026_05_08_000002_create_forum_notifications_table.php
        Schema::table('forum_notifications', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('actor_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('group_id')->references('id')->on('forum_groups')->onDelete('set null');
            $table->foreign('thread_id')->references('id')->on('forum_threads')->onDelete('cascade');
            $table->foreign('comment_id')->references('id')->on('forum_comments')->onDelete('cascade');
        });

        // 2026_05_08_000003_create_forum_comment_likes_table.php
        Schema::table('forum_comment_likes', function (Blueprint $table) {
            $table->foreign('forum_comment_id')->references('id')->on('forum_comments')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
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
