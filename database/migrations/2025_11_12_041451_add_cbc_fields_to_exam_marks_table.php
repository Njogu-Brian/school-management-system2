<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_marks', function (Blueprint $table) {
            // Assessment method
            if (!Schema::hasColumn('exam_marks', 'assessment_method')) {
                $table->enum('assessment_method', ['written', 'oral', 'practical', 'portfolio', 'mixed'])->default('written')->after('score_moderated');
            }

            // CAT number for continuous assessments
            if (!Schema::hasColumn('exam_marks', 'cat_number')) {
                $table->integer('cat_number')->nullable()->after('assessment_method');
            }

            // Performance level reference
            if (!Schema::hasColumn('exam_marks', 'performance_level_id')) {
                $table->foreignId('performance_level_id')->nullable()->after('pl_level')->constrained('cbc_performance_levels')->nullOnDelete();
            }

            // Competency scores (JSON)
            if (!Schema::hasColumn('exam_marks', 'competency_scores')) {
                $table->json('competency_scores')->nullable()->after('rubrics');
            }

            // Portfolio reference
            if (!Schema::hasColumn('exam_marks', 'portfolio_assessment_id')) {
                $table->foreignId('portfolio_assessment_id')->nullable()->after('competency_scores')->constrained('portfolio_assessments')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('exam_marks', function (Blueprint $table) {
            $table->dropForeign(['performance_level_id']);
            $table->dropForeign(['portfolio_assessment_id']);
            $table->dropColumn([
                'assessment_method',
                'cat_number',
                'performance_level_id',
                'competency_scores',
                'portfolio_assessment_id'
            ]);
        });
    }
};
