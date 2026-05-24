<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenders', function (Blueprint $table) {
            $table->id();
            $table->string('tender_number')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('organization_name');
            $table->string('ministry')->nullable();
            $table->string('category')->nullable();
            $table->string('sector')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->default('Pakistan');
            $table->decimal('budget', 15, 2)->nullable();
            $table->string('tender_type');
            $table->string('status');
            $table->date('advertised_at');
            $table->dateTime('closing_at');
            $table->string('source_url');
            $table->string('detail_url');
            $table->json('pdf_urls')->nullable();
            $table->text('ai_summary')->nullable();
            $table->text('ai_eligibility')->nullable();
            $table->decimal('ai_budget_extracted', 15, 2)->nullable();
            $table->string('ai_recommendation')->nullable();
            $table->boolean('is_summarized')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->timestamp('scraped_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('category');
            $table->index('city');
            $table->index('closing_at');
            $table->index('advertised_at');
            $table->index('is_summarized');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenders');
    }
};
