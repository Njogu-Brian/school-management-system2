<?php

use App\Services\Academics\ExamPaperDeduplicator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Merge existing duplicates before enforcing uniqueness.
        app(ExamPaperDeduplicator::class)->dedupe(dryRun: false);

        if (! Schema::hasColumn('exams', 'stream_key')) {
            DB::statement('ALTER TABLE exams ADD COLUMN stream_key INT GENERATED ALWAYS AS (IFNULL(stream_id, 0)) STORED');
        }

        $indexExists = collect(DB::select('SHOW INDEX FROM exams WHERE Key_name = ?', ['exams_unique_paper_scope']))
            ->isNotEmpty();

        if (! $indexExists) {
            // One paper per exam type + year + term + class + subject + stream.
            DB::statement('CREATE UNIQUE INDEX exams_unique_paper_scope ON exams (exam_type_id, academic_year_id, term_id, classroom_id, subject_id, stream_key)');
        }
    }

    public function down(): void
    {
        $indexExists = collect(DB::select('SHOW INDEX FROM exams WHERE Key_name = ?', ['exams_unique_paper_scope']))
            ->isNotEmpty();
        if ($indexExists) {
            DB::statement('DROP INDEX exams_unique_paper_scope ON exams');
        }

        if (Schema::hasColumn('exams', 'stream_key')) {
            DB::statement('ALTER TABLE exams DROP COLUMN stream_key');
        }
    }
};
