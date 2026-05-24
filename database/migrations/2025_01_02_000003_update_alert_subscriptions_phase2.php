<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alert_subscriptions', function (Blueprint $table) {
            $table->string('name')->nullable()->after('user_id');
            $table->json('countries')->nullable()->after('cities');
            $table->json('sources')->nullable()->after('countries');
            $table->enum('frequency', ['instant', 'daily', 'weekly'])->default('instant')->after('is_active');
            $table->unsignedInteger('match_count')->default(0)->after('last_triggered_at');
        });
    }

    public function down(): void
    {
        Schema::table('alert_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['name', 'countries', 'sources', 'frequency', 'match_count']);
        });
    }
};
