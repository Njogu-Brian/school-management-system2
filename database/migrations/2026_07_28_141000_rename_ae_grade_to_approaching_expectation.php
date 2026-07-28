<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cbc_performance_levels')) {
            DB::table('cbc_performance_levels')
                ->where('code', 'AE')
                ->update([
                    'name' => 'Approaching Expectation',
                    'description' => 'Learner demonstrates competencies approaching the expected level.',
                ]);
        }

        if (Schema::hasTable('grading_bands')) {
            DB::table('grading_bands')
                ->where('descriptor', 'Above Expectation')
                ->update(['descriptor' => 'Approaching Expectation']);
        }

        if (Schema::hasTable('exam_grades')) {
            DB::table('exam_grades')
                ->where('grade_name', 'AE')
                ->where('description', 'Above Expectation')
                ->update(['description' => 'Approaching Expectation']);
        }

        if (Schema::hasTable('exam_marks')) {
            DB::table('exam_marks')
                ->where('descriptor', 'Above Expectation')
                ->update(['descriptor' => 'Approaching Expectation']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cbc_performance_levels')) {
            DB::table('cbc_performance_levels')
                ->where('code', 'AE')
                ->update([
                    'name' => 'Above Expectation',
                    'description' => 'Learner demonstrates competencies above basic but below meeting level.',
                ]);
        }

        if (Schema::hasTable('grading_bands')) {
            DB::table('grading_bands')
                ->where('descriptor', 'Approaching Expectation')
                ->update(['descriptor' => 'Above Expectation']);
        }

        if (Schema::hasTable('exam_grades')) {
            DB::table('exam_grades')
                ->where('grade_name', 'AE')
                ->where('description', 'Approaching Expectation')
                ->update(['description' => 'Above Expectation']);
        }

        if (Schema::hasTable('exam_marks')) {
            DB::table('exam_marks')
                ->where('descriptor', 'Approaching Expectation')
                ->update(['descriptor' => 'Above Expectation']);
        }
    }
};
