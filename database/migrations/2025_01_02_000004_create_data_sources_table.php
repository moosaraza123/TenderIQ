<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->char('country_code', 2)->index();
            $table->enum('tier', ['free', 'paid', 'premium', 'enterprise'])->default('free');
            $table->string('url');
            $table->string('scraper_class');
            $table->enum('scraper_type', ['html', 'api', 'rss', 'headless'])->default('html');
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_proxy')->default(false);
            $table->unsignedInteger('scrape_frequency_hours')->default(6);
            $table->unsignedInteger('rate_limit_delay_seconds')->default(2);
            $table->timestamp('last_scraped_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->unsignedInteger('total_tenders_scraped')->default(0);
            $table->decimal('success_rate_7d', 5, 2)->default(100.00);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_sources');
    }
};
