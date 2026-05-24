<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->text('title_arabic')->nullable()->after('title');
            $table->text('description_arabic')->nullable()->after('description');
            $table->string('organization_name_arabic')->nullable()->after('organization_name');
            $table->char('country_code', 2)->default('PK')->after('country')->index();
            $table->char('currency', 3)->default('PKR')->after('country_code');
            $table->string('region')->nullable()->after('city');
            $table->enum('tier', ['free', 'paid', 'premium', 'enterprise'])->default('free')->after('is_featured')->index();
            $table->string('source')->default('ppra_federal')->after('source_url')->index();
            $table->string('source_id')->nullable()->after('source');
            $table->unsignedTinyInteger('quality_score')->default(0)->after('is_summarized');
            $table->unsignedInteger('view_count')->default(0)->after('quality_score');
            $table->unsignedBigInteger('duplicate_of')->nullable()->after('view_count');
            $table->text('ai_summary_arabic')->nullable()->after('ai_summary');
            $table->json('ai_key_requirements')->nullable()->after('ai_recommendation');

            $table->foreign('duplicate_of')->references('id')->on('tenders')->nullOnDelete();

            $table->index(['country_code', 'status', 'closing_at']);
            $table->index(['tier', 'closing_at']);
            $table->index(['is_summarized', 'closing_at']);
        });

        // Backfill existing Pakistan tenders
        DB::statement("UPDATE tenders SET country_code='PK', currency='PKR', tier='free', source='ppra_federal' WHERE source='ppra_federal' OR source='' OR source IS NULL");
    }

    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->dropForeign(['duplicate_of']);
            $table->dropIndex(['country_code', 'status', 'closing_at']);
            $table->dropIndex(['tier', 'closing_at']);
            $table->dropIndex(['is_summarized', 'closing_at']);
            $table->dropColumn([
                'title_arabic', 'description_arabic', 'organization_name_arabic',
                'country_code', 'currency', 'region', 'tier', 'source', 'source_id',
                'quality_score', 'view_count', 'duplicate_of',
                'ai_summary_arabic', 'ai_key_requirements',
            ]);
        });
    }
};
