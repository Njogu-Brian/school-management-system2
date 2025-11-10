<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('student_academic_history', function (Blueprint $table) {
            if (!Schema::hasColumn('student_academic_history', 'next_classroom_id')) {
                $table->unsignedBigInteger('next_classroom_id')->nullable()->after('classroom_id');
                $table->foreign('next_classroom_id')->references('id')->on('classrooms')->onDelete('set null');
            }
            if (!Schema::hasColumn('student_academic_history', 'next_stream_id')) {
                $table->unsignedBigInteger('next_stream_id')->nullable()->after('stream_id');
                $table->foreign('next_stream_id')->references('id')->on('streams')->onDelete('set null');
            }
            if (!Schema::hasColumn('student_academic_history', 'term_id')) {
                $table->unsignedBigInteger('term_id')->nullable()->after('academic_year_id');
                $table->foreign('term_id')->references('id')->on('terms')->onDelete('set null');
            }
            if (!Schema::hasColumn('student_academic_history', 'promotion_date')) {
                $table->date('promotion_date')->nullable()->after('promotion_status');
            }
            if (!Schema::hasColumn('student_academic_history', 'notes')) {
                $table->text('notes')->nullable()->after('teacher_comments');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_academic_history', function (Blueprint $table) {
            if (Schema::hasColumn('student_academic_history', 'next_classroom_id')) {
                $table->dropForeign(['next_classroom_id']);
                $table->dropColumn('next_classroom_id');
            }
            if (Schema::hasColumn('student_academic_history', 'next_stream_id')) {
                $table->dropForeign(['next_stream_id']);
                $table->dropColumn('next_stream_id');
            }
            if (Schema::hasColumn('student_academic_history', 'term_id')) {
                $table->dropForeign(['term_id']);
                $table->dropColumn('term_id');
            }
            if (Schema::hasColumn('student_academic_history', 'promotion_date')) {
                $table->dropColumn('promotion_date');
            }
            if (Schema::hasColumn('student_academic_history', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
