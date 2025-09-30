<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('exam_marks', function (Blueprint $table) {
            if (!Schema::hasColumn('exam_marks', 'opener_score')) {
                $table->decimal('opener_score', 7, 2)->unsigned()->nullable()->after('score_moderated');
            }
            if (!Schema::hasColumn('exam_marks', 'midterm_score')) {
                $table->decimal('midterm_score', 7, 2)->unsigned()->nullable()->after('opener_score');
            }
            if (!Schema::hasColumn('exam_marks', 'endterm_score')) {
                $table->decimal('endterm_score', 7, 2)->unsigned()->nullable()->after('midterm_score');
            }
            if (!Schema::hasColumn('exam_marks', 'rubrics')) {
                $table->json('rubrics')->nullable()->after('endterm_score'); // e.g., task-by-task
            }
            if (!Schema::hasColumn('exam_marks', 'subject_remark')) {
                $table->string('subject_remark', 500)->nullable()->after('pl_level');
            }
        });
    }
    public function down(): void {
        Schema::table('exam_marks', function (Blueprint $table) {
            foreach (['opener_score','midterm_score','endterm_score','rubrics','subject_remark'] as $c) {
                if (Schema::hasColumn('exam_marks', $c)) $table->dropColumn($c);
            }
        });
    }
};
