<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('alert_subscriptions', function (Blueprint $table) {
            $table->string('webhook_url', 500)->nullable()->after('sources');
        });
    }

    public function down(): void
    {
        Schema::table('alert_subscriptions', function (Blueprint $table) {
            $table->dropColumn('webhook_url');
        });
    }
};
