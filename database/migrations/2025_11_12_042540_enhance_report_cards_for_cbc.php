<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_cards', function (Blueprint $table) {
            // CBC Performance Summary
            if (!Schema::hasColumn('report_cards', 'performance_summary')) {
                $table->json('performance_summary')->nullable()->after('summary');
            }

            // Core Competencies Assessment
            if (!Schema::hasColumn('report_cards', 'core_competencies')) {
                $table->json('core_competencies')->nullable()->after('performance_summary');
            }

            // Learning Areas Performance (instead of just subjects)
            if (!Schema::hasColumn('report_cards', 'learning_areas_performance')) {
                $table->json('learning_areas_performance')->nullable()->after('core_competencies');
            }

            // CAT Breakdown
            if (!Schema::hasColumn('report_cards', 'cat_breakdown')) {
                $table->json('cat_breakdown')->nullable()->after('learning_areas_performance');
            }

            // Portfolio Evidence Summary
            if (!Schema::hasColumn('report_cards', 'portfolio_summary')) {
                $table->json('portfolio_summary')->nullable()->after('cat_breakdown');
            }

            // Co-curricular Activities
            if (!Schema::hasColumn('report_cards', 'co_curricular')) {
                $table->json('co_curricular')->nullable()->after('portfolio_summary');
            }

            // Personal and Social Development
            if (!Schema::hasColumn('report_cards', 'personal_social_dev')) {
                $table->json('personal_social_dev')->nullable()->after('co_curricular');
            }

            // Attendance Summary
            if (!Schema::hasColumn('report_cards', 'attendance_summary')) {
                $table->json('attendance_summary')->nullable()->after('personal_social_dev');
            }

            // Overall Performance Level
            if (!Schema::hasColumn('report_cards', 'overall_performance_level_id')) {
                $table->foreignId('overall_performance_level_id')->nullable()->after('attendance_summary')
                    ->constrained('cbc_performance_levels')->nullOnDelete();
            }

            // Student Self-Assessment
            if (!Schema::hasColumn('report_cards', 'student_self_assessment')) {
                $table->text('student_self_assessment')->nullable()->after('overall_performance_level_id');
            }

            // Goals for Next Term
            if (!Schema::hasColumn('report_cards', 'next_term_goals')) {
                $table->text('next_term_goals')->nullable()->after('student_self_assessment');
            }

            // Parent/Guardian Feedback
            if (!Schema::hasColumn('report_cards', 'parent_feedback')) {
                $table->text('parent_feedback')->nullable()->after('next_term_goals');
            }

            // UPI (Unique Personal Identifier)
            if (!Schema::hasColumn('report_cards', 'upi')) {
                $table->string('upi', 50)->nullable()->after('parent_feedback');
            }
        });
    }

    public function down(): void
    {
        Schema::table('report_cards', function (Blueprint $table) {
            $table->dropForeign(['overall_performance_level_id']);
            $table->dropColumn([
                'performance_summary',
                'core_competencies',
                'learning_areas_performance',
                'cat_breakdown',
                'portfolio_summary',
                'co_curricular',
                'personal_social_dev',
                'attendance_summary',
                'overall_performance_level_id',
                'student_self_assessment',
                'next_term_goals',
                'parent_feedback',
                'upi'
            ]);
        });
    }
};
