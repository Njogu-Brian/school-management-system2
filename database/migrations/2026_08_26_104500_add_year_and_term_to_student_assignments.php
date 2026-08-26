<?php

use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('student_assignments')) {
            return;
        }

        Schema::table('student_assignments', function (Blueprint $table) {
            if (! Schema::hasColumn('student_assignments', 'year')) {
                $table->unsignedSmallInteger('year')->nullable()->after('student_id');
            }
            if (! Schema::hasColumn('student_assignments', 'term')) {
                $table->unsignedTinyInteger('term')->nullable()->after('year');
            }
            if (! Schema::hasColumn('student_assignments', 'academic_year_id')) {
                $table->foreignId('academic_year_id')
                    ->nullable()
                    ->after('term')
                    ->constrained('academic_years')
                    ->nullOnDelete();
            }
        });

        $academicYear = AcademicYear::where('is_active', true)->first();
        $year = (int) ($academicYear?->year ?? date('Y'));
        $termModel = Term::where('is_current', true)->first();
        $term = $termModel ? ((int) preg_replace('/[^0-9]/', '', (string) $termModel->name) ?: 1) : 1;
        $academicYearId = $academicYear?->id;

        DB::table('student_assignments')
            ->where(function ($q) {
                $q->whereNull('year')->orWhereNull('term');
            })
            ->update([
                'year' => $year,
                'term' => $term,
                'academic_year_id' => $academicYearId,
            ]);

        $dupes = DB::table('student_assignments')
            ->select('student_id', 'year', 'term', DB::raw('MAX(id) as keep_id'), DB::raw('COUNT(*) as c'))
            ->groupBy('student_id', 'year', 'term')
            ->having('c', '>', 1)
            ->get();

        foreach ($dupes as $dupe) {
            DB::table('student_assignments')
                ->where('student_id', $dupe->student_id)
                ->where('year', $dupe->year)
                ->where('term', $dupe->term)
                ->where('id', '!=', $dupe->keep_id)
                ->delete();
        }

        $indexExists = collect(DB::select('SHOW INDEX FROM student_assignments'))
            ->contains(fn ($row) => ($row->Key_name ?? '') === 'student_assignments_student_year_term_unique');

        if (! $indexExists) {
            Schema::table('student_assignments', function (Blueprint $table) {
                $table->unique(['student_id', 'year', 'term'], 'student_assignments_student_year_term_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('student_assignments')) {
            return;
        }

        $indexExists = collect(DB::select('SHOW INDEX FROM student_assignments'))
            ->contains(fn ($row) => ($row->Key_name ?? '') === 'student_assignments_student_year_term_unique');

        Schema::table('student_assignments', function (Blueprint $table) use ($indexExists) {
            if ($indexExists) {
                $table->dropUnique('student_assignments_student_year_term_unique');
            }
            if (Schema::hasColumn('student_assignments', 'academic_year_id')) {
                $table->dropForeign(['academic_year_id']);
                $table->dropColumn('academic_year_id');
            }
            $drops = [];
            if (Schema::hasColumn('student_assignments', 'term')) {
                $drops[] = 'term';
            }
            if (Schema::hasColumn('student_assignments', 'year')) {
                $drops[] = 'year';
            }
            if ($drops) {
                $table->dropColumn($drops);
            }
        });
    }
};
