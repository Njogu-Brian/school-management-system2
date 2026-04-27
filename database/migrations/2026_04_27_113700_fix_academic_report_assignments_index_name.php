<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('academic_report_assignments')) {
            return;
        }

        $tableName = 'academic_report_assignments';
        $longName = 'academic_report_assignments_classroom_id_stream_id_subject_id_index';
        $shortName = 'ara_cls_stream_subj_idx';

        $hasIndex = function (string $indexName) use ($tableName): bool {
            $rows = DB::select(
                "SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1",
                [$tableName, $indexName]
            );
            return !empty($rows);
        };

        // Drop the long index name if it exists (or if it was created by a different environment).
        if ($hasIndex($longName)) {
            DB::statement("DROP INDEX `{$longName}` ON `{$tableName}`");
        }

        // Ensure the short index exists.
        if (!$hasIndex($shortName)) {
            Schema::table($tableName, function (Blueprint $table) use ($shortName) {
                $table->index(['classroom_id', 'stream_id', 'subject_id'], $shortName);
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('academic_report_assignments')) {
            return;
        }

        $tableName = 'academic_report_assignments';
        $shortName = 'ara_cls_stream_subj_idx';

        $rows = DB::select(
            "SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1",
            [$tableName, $shortName]
        );

        if (!empty($rows)) {
            DB::statement("DROP INDEX `{$shortName}` ON `{$tableName}`");
        }
    }
};

