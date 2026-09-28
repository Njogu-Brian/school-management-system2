<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Collapse exam lifecycle to: draft → marking → published → locked.
 * Maps legacy statuses: open/moderation → marking, approved → published.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('exams')) {
            return;
        }

        DB::table('exams')->whereIn('status', ['open', 'moderation'])->update(['status' => 'marking']);
        DB::table('exams')->where('status', 'approved')->update(['status' => 'published']);

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE exams MODIFY COLUMN status ENUM('draft','marking','published','locked') NOT NULL DEFAULT 'draft'");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('exams')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE exams MODIFY COLUMN status ENUM('draft','open','marking','moderation','approved','published','locked') NOT NULL DEFAULT 'draft'");
        }
    }
};
