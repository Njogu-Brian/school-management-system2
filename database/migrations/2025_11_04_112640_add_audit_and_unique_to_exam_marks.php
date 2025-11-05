<?php

// database/migrations/xxxx_xx_xx_add_audit_and_unique_to_exam_marks.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Add columns only if they don't exist (in case the failed run partially added them)
        Schema::table('exam_marks', function (Blueprint $t) {
            if (!Schema::hasColumn('exam_marks', 'entered_by')) {
                $t->unsignedBigInteger('entered_by')->nullable()->index();
            }
            if (!Schema::hasColumn('exam_marks', 'updated_by')) {
                $t->unsignedBigInteger('updated_by')->nullable()->index();
            }
        });

        // Add unique index only if it doesn't already exist
        if (!$this->indexExists('exam_marks', 'uniq_exam_student_subject')) {
            Schema::table('exam_marks', function (Blueprint $t) {
                $t->unique(['exam_id', 'student_id', 'subject_id'], 'uniq_exam_student_subject');
            });
        }
    }

    public function down(): void
    {
        // Drop unique only if it exists
        if ($this->indexExists('exam_marks', 'uniq_exam_student_subject')) {
            Schema::table('exam_marks', function (Blueprint $t) {
                $t->dropUnique('uniq_exam_student_subject');
            });
        }

        // Optionally drop columns (guarded)
        Schema::table('exam_marks', function (Blueprint $t) {
            if (Schema::hasColumn('exam_marks', 'entered_by')) {
                $t->dropColumn('entered_by');
            }
            if (Schema::hasColumn('exam_marks', 'updated_by')) {
                $t->dropColumn('updated_by');
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $db = DB::getDatabaseName();
        $row = DB::selectOne(
            'SELECT COUNT(1) AS c FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$db, $table, $indexName]
        );
        return (int)($row->c ?? 0) > 0;
    }
};
