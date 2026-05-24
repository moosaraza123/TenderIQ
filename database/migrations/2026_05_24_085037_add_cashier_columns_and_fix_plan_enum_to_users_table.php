<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the old column (Cashier's migration already added stripe_id)
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('stripe_customer_id');
        });

        // Fix plan enum: basic→starter, pro→professional
        DB::statement("UPDATE users SET subscription_plan='starter' WHERE subscription_plan='basic'");
        DB::statement("UPDATE users SET subscription_plan='professional' WHERE subscription_plan='pro'");
        DB::statement("ALTER TABLE users MODIFY COLUMN subscription_plan ENUM('free','starter','professional','enterprise') NOT NULL DEFAULT 'free'");
    }

    public function down(): void
    {
        DB::statement("UPDATE users SET subscription_plan='basic' WHERE subscription_plan='starter'");
        DB::statement("UPDATE users SET subscription_plan='pro' WHERE subscription_plan='professional'");
        DB::statement("ALTER TABLE users MODIFY COLUMN subscription_plan ENUM('free','basic','pro','enterprise') NOT NULL DEFAULT 'free'");

        Schema::table('users', function (Blueprint $table) {
            $table->string('stripe_customer_id')->nullable()->after('subscription_expires_at');
        });
    }
};
