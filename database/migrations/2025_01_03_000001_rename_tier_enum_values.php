<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update tenders table
        DB::statement("ALTER TABLE tenders MODIFY COLUMN tier ENUM('free','starter','professional','enterprise') NOT NULL DEFAULT 'free'");
        DB::statement("UPDATE tenders SET tier='starter' WHERE tier='paid'");
        DB::statement("UPDATE tenders SET tier='professional' WHERE tier='premium'");

        // Update data_sources table — update values first, then change enum definition
        DB::statement("UPDATE data_sources SET tier='enterprise' WHERE tier='premium'");
        DB::statement("UPDATE data_sources SET tier='free' WHERE tier='paid'");
        DB::statement("ALTER TABLE data_sources MODIFY COLUMN tier ENUM('free','starter','professional','enterprise') NOT NULL DEFAULT 'free'");
        // Now set proper values (were temporarily mapped to safe enum values above)
        // The seeder will set correct tiers when re-run
    }

    public function down(): void
    {
        DB::statement("UPDATE tenders SET tier='paid' WHERE tier='starter'");
        DB::statement("UPDATE tenders SET tier='premium' WHERE tier='professional'");
        DB::statement("ALTER TABLE tenders MODIFY COLUMN tier ENUM('free','paid','premium','enterprise') NOT NULL DEFAULT 'free'");

        DB::statement("UPDATE data_sources SET tier='paid' WHERE tier='starter'");
        DB::statement("UPDATE data_sources SET tier='premium' WHERE tier='professional'");
        DB::statement("ALTER TABLE data_sources MODIFY COLUMN tier ENUM('free','paid','premium','enterprise') NOT NULL DEFAULT 'free'");
    }
};
