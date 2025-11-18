<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('exams')) {
            Schema::table('exams', function (Blueprint $table) {
                // Add exam type foreign key if exam_types table exists and column doesn't exist
                if (Schema::hasTable('exam_types') && !Schema::hasColumn('exams', 'exam_type_id')) {
                    $table->foreignId('exam_type_id')->nullable()->after('id')->constrained('exam_types')->nullOnDelete();
                }

                // Enhanced exam type classification (only if not already added by previous migration)
                if (!Schema::hasColumn('exams', 'exam_category')) {
                    $table->enum('exam_category', ['formative', 'summative', 'diagnostic', 'standardized', 'national', 'mock'])->nullable()->after('type');
                }

                // Flexible mark weightings
                if (!Schema::hasColumn('exams', 'component_weights')) {
                    $table->json('component_weights')->nullable()->after('weight'); // e.g., {"theory": 70, "practical": 30}
                }

                // Grade/rating mapping (descriptor mapping)
                if (!Schema::hasColumn('exams', 'grade_mapping')) {
                    $table->json('grade_mapping')->nullable()->after('component_weights'); // e.g., {"A": "Excellent", "B": "Good"}
                }

                // Descriptor mapping for CBC
                if (!Schema::hasColumn('exams', 'descriptor_mapping')) {
                    $table->json('descriptor_mapping')->nullable()->after('grade_mapping'); // e.g., {"80-100": "Exceeds Expectations", "60-79": "Meets Expectations"}
                }

                // Assessment method (check if it was already added by previous CBC migration)
                // The previous migration adds it as enum with different values, so we need to be careful
                if (!Schema::hasColumn('exams', 'assessment_method')) {
                    $table->enum('assessment_method', ['written', 'oral', 'practical', 'portfolio', 'project', 'continuous', 'mixed'])->nullable()->after('descriptor_mapping');
                }

                // Allow bulk import
                if (!Schema::hasColumn('exams', 'import_template_id')) {
                    $table->string('import_template_id')->nullable()->after('assessment_method');
                }
            });
        }

        // Enhance exam_marks table for advanced features
        if (Schema::hasTable('exam_marks')) {
            Schema::table('exam_marks', function (Blueprint $table) {
                // Component scores (for weighted exams)
                if (!Schema::hasColumn('exam_marks', 'component_scores')) {
                    $table->json('component_scores')->nullable()->after('score_raw'); // e.g., {"theory": 45, "practical": 25}
                }

                // Descriptor/rating
                if (!Schema::hasColumn('exam_marks', 'descriptor')) {
                    $table->string('descriptor')->nullable()->after('grade_label'); // e.g., "Exceeds Expectations"
                }

                // Competency assessment (for CBC) - check if it was already added by previous migration
                // The previous migration adds competency_scores, so we only add if it doesn't exist
                if (!Schema::hasColumn('exam_marks', 'competency_scores')) {
                    $table->json('competency_scores')->nullable()->after('descriptor'); // e.g., {"C1": "accomplished", "C2": "developing"}
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $columns = ['exam_type_id', 'exam_category', 'component_weights', 'grade_mapping', 'descriptor_mapping', 'assessment_method', 'import_template_id'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('exams', $column)) {
                    if ($column === 'exam_type_id') {
                        $table->dropForeign(['exams_exam_type_id_foreign']);
                    }
                    $table->dropColumn($column);
                }
            }
        });

        if (Schema::hasTable('exam_marks')) {
            Schema::table('exam_marks', function (Blueprint $table) {
                $columns = ['component_scores', 'descriptor', 'competency_scores'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('exam_marks', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};

