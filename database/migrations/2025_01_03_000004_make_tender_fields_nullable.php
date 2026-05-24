<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->string('detail_url')->nullable()->change();
            $table->string('source_url')->nullable()->change();
            $table->string('organization_name')->nullable()->change();
            $table->string('tender_type')->nullable()->change();
            $table->string('country')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->string('detail_url')->nullable(false)->change();
            $table->string('source_url')->nullable(false)->change();
            $table->string('organization_name')->nullable(false)->change();
            $table->string('tender_type')->nullable(false)->change();
            $table->string('country')->nullable(false)->default('Pakistan')->change();
        });
    }
};
