<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_plans', function (Blueprint $table) {
            $table->string('submission_status')->default('draft')->after('created_by');
            $table->timestamp('submitted_at')->nullable()->after('submission_status');
            $table->boolean('is_late')->default(false)->after('submitted_at');

            $table->foreignId('timetable_id')->nullable()->after('classroom_id')->constrained('timetables')->nullOnDelete();

            $table->foreignId('rejected_by')->nullable()->after('approved_at')->constrained('staff')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            $table->text('rejection_notes')->nullable()->after('rejected_at');

            $table->index(['submission_status', 'planned_date'], 'lesson_plans_submission_status_date_idx');
            $table->index(['created_by', 'planned_date'], 'lesson_plans_teacher_date_idx');
            $table->index(['timetable_id', 'planned_date'], 'lesson_plans_timetable_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('lesson_plans', function (Blueprint $table) {
            $table->dropIndex('lesson_plans_submission_status_date_idx');
            $table->dropIndex('lesson_plans_teacher_date_idx');
            $table->dropIndex('lesson_plans_timetable_date_idx');

            $table->dropForeign(['timetable_id']);
            $table->dropForeign(['rejected_by']);

            $table->dropColumn([
                'submission_status',
                'submitted_at',
                'is_late',
                'timetable_id',
                'rejected_by',
                'rejected_at',
                'rejection_notes',
            ]);
        });
    }
};

