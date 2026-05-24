<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('country')->default('Pakistan')->after('phone');
            $table->string('stripe_customer_id')->nullable()->after('subscription_expires_at');
            $table->unsignedInteger('api_calls_today')->default(0)->after('stripe_customer_id');
        });

        // Extend subscription_plan enum to include enterprise
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE users MODIFY subscription_plan ENUM('free','basic','pro','enterprise') NOT NULL DEFAULT 'free'");
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['country', 'stripe_customer_id', 'api_calls_today']);
        });

        DB::statement("ALTER TABLE users MODIFY subscription_plan ENUM('free','basic','pro') NOT NULL DEFAULT 'free'");
    }
};
