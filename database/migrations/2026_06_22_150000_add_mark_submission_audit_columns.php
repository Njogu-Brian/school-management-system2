<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_marks', function (Blueprint $table) {
            if (! Schema::hasColumn('exam_marks', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('exam_marks', 'submitted_by')) {
                $table->unsignedBigInteger('submitted_by')->nullable()->index()->after('submitted_at');
            }
        });

        Schema::table('exams', function (Blueprint $table) {
            if (! Schema::hasColumn('exams', 'marking_submitted_at')) {
                $table->timestamp('marking_submitted_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('exams', 'marking_submitted_by')) {
                $table->unsignedBigInteger('marking_submitted_by')->nullable()->index()->after('marking_submitted_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('exam_marks', function (Blueprint $table) {
            if (Schema::hasColumn('exam_marks', 'submitted_by')) {
                $table->dropColumn('submitted_by');
            }
            if (Schema::hasColumn('exam_marks', 'submitted_at')) {
                $table->dropColumn('submitted_at');
            }
        });

        Schema::table('exams', function (Blueprint $table) {
            if (Schema::hasColumn('exams', 'marking_submitted_by')) {
                $table->dropColumn('marking_submitted_by');
            }
            if (Schema::hasColumn('exams', 'marking_submitted_at')) {
                $table->dropColumn('marking_submitted_at');
            }
        });
    }
};
