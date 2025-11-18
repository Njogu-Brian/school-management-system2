<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Enhance existing homework table if it exists, or create new structure
        if (!Schema::hasTable('homework_diary')) {
            Schema::create('homework_diary', function (Blueprint $table) {
                $table->id();
                $table->foreignId('homework_id')->constrained('homework')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->foreignId('lesson_plan_id')->nullable()->constrained('lesson_plans')->nullOnDelete();
                $table->enum('status', ['pending', 'in_progress', 'completed', 'submitted', 'marked'])->default('pending');
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->text('student_notes')->nullable();
                $table->text('teacher_feedback')->nullable();
                $table->integer('score')->nullable();
                $table->integer('max_score')->nullable();
                $table->json('attachments')->nullable(); // Student submitted files
                $table->timestamps();

                $table->index(['homework_id', 'student_id']);
                $table->index('status');
            });
        }

        // Add columns to existing homework table if needed
        if (Schema::hasTable('homework')) {
            Schema::table('homework', function (Blueprint $table) {
                if (!Schema::hasColumn('homework', 'lesson_plan_id')) {
                    $table->foreignId('lesson_plan_id')->nullable()->after('id')->constrained('lesson_plans')->nullOnDelete();
                }
                if (!Schema::hasColumn('homework', 'scheme_of_work_id')) {
                    $table->foreignId('scheme_of_work_id')->nullable()->after('lesson_plan_id')->constrained('schemes_of_work')->nullOnDelete();
                }
                if (!Schema::hasColumn('homework', 'attachment_paths')) {
                    // Check if 'instructions' column exists (it should based on original migration)
                    $afterColumn = Schema::hasColumn('homework', 'instructions') ? 'instructions' : 'due_date';
                    $table->json('attachment_paths')->nullable()->after($afterColumn);
                }
                if (!Schema::hasColumn('homework', 'allow_late_submission')) {
                    $table->boolean('allow_late_submission')->default(true)->after('due_date');
                }
                if (!Schema::hasColumn('homework', 'max_score')) {
                    $table->integer('max_score')->nullable()->after('allow_late_submission');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('homework_diary')) {
            Schema::dropIfExists('homework_diary');
        }

        if (Schema::hasTable('homework')) {
            Schema::table('homework', function (Blueprint $table) {
                $columns = ['lesson_plan_id', 'scheme_of_work_id', 'attachment_paths', 'allow_late_submission', 'max_score'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('homework', $column)) {
                        if (in_array($column, ['lesson_plan_id', 'scheme_of_work_id'])) {
                            $table->dropForeign(['homework_' . $column . '_foreign']);
                        }
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};

