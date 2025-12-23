<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_behaviours', function (Blueprint $table) {
            if (!Schema::hasColumn('student_behaviours', 'academic_year_id')) {
                $table->foreignId('academic_year_id')->nullable()->after('behaviour_id')->constrained('academic_years')->nullOnDelete();
            }
            if (!Schema::hasColumn('student_behaviours', 'term_id')) {
                $table->foreignId('term_id')->nullable()->after('academic_year_id')->constrained('terms')->nullOnDelete();
            }
            if (!Schema::hasColumn('student_behaviours', 'recorded_by')) {
                $table->foreignId('recorded_by')->nullable()->after('logged_by')->constrained('staff')->nullOnDelete();
            }
            if (!Schema::hasColumn('student_behaviours', 'notes')) {
                $table->text('notes')->nullable()->after('note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_behaviours', function (Blueprint $table) {
            if (Schema::hasColumn('student_behaviours', 'notes')) {
                $table->dropColumn('notes');
            }
            if (Schema::hasColumn('student_behaviours', 'recorded_by')) {
                $table->dropConstrainedForeignId('recorded_by');
            }
            if (Schema::hasColumn('student_behaviours', 'term_id')) {
                $table->dropConstrainedForeignId('term_id');
            }
            if (Schema::hasColumn('student_behaviours', 'academic_year_id')) {
                $table->dropConstrainedForeignId('academic_year_id');
            }
        });
    }
};

