<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('student_skill_grades')) {
            Schema::create('student_skill_grades', function (Blueprint $t) {
                $t->id();
                $t->foreignId('student_id')->constrained();
                $t->foreignId('term_id')->constrained();
                $t->foreignId('academic_year_id')->constrained('academic_years');
                $t->foreignId('report_card_skill_id')->constrained('report_card_skills');
                $t->string('grade', 5)->nullable(); // EE/ME/AE/BE or 1-5
                $t->text('comment')->nullable();
                $t->unsignedBigInteger('entered_by')->nullable()->index();
                $t->unsignedBigInteger('updated_by')->nullable()->index();
                $t->timestamps();
                $t->unique(['student_id','term_id','report_card_skill_id'], 'ssk_unique');
            });
        } else {
            // Table exists: just ensure the unique index exists
            if (!$this->indexExists('student_skill_grades', 'ssk_unique')) {
                Schema::table('student_skill_grades', function (Blueprint $t) {
                    $t->unique(['student_id','term_id','report_card_skill_id'], 'ssk_unique');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('student_skill_grades')) {
            if ($this->indexExists('student_skill_grades', 'ssk_unique')) {
                Schema::table('student_skill_grades', function (Blueprint $t) {
                    $t->dropUnique('ssk_unique');
                });
            }
            Schema::dropIfExists('student_skill_grades');
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $db = DB::getDatabaseName();
        $row = DB::selectOne(
            'SELECT COUNT(1) AS c
             FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$db, $table, $indexName]
        );
        return (int)($row->c ?? 0) > 0;
    }
};
