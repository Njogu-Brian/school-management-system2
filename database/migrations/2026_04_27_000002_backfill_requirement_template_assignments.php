<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('requirement_templates') || !Schema::hasTable('requirement_template_assignments')) {
            return;
        }

        $now = now();

        // 1) Backfill one assignment per template (using template's existing scope + values)
        $templateRows = DB::table('requirement_templates')
            ->select(
                'id',
                'academic_year_id',
                'term_id',
                'classroom_id',
                'student_type',
                'brand',
                'quantity_per_student',
                'unit',
                'notes',
                'leave_with_teacher',
                'is_verification_only',
                'is_active'
            )
            ->get();

        foreach ($templateRows as $t) {
            DB::table('requirement_template_assignments')->updateOrInsert(
                [
                    'requirement_template_id' => $t->id,
                    'academic_year_id' => $t->academic_year_id,
                    'term_id' => $t->term_id,
                    'classroom_id' => $t->classroom_id,
                    'student_type' => $t->student_type ?? 'both',
                ],
                [
                    'brand' => $t->brand,
                    'quantity_per_student' => $t->quantity_per_student ?? 1,
                    'unit' => $t->unit ?? 'piece',
                    'notes' => $t->notes,
                    'leave_with_teacher' => (bool) $t->leave_with_teacher,
                    'is_verification_only' => (bool) $t->is_verification_only,
                    'is_active' => (bool) $t->is_active,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        // 2) If multi-class pivot exists, create per-class assignments mirroring the template values
        if (Schema::hasTable('requirement_template_classrooms')) {
            $pivotRows = DB::table('requirement_template_classrooms')
                ->select('requirement_template_id', 'classroom_id')
                ->get();

            foreach ($pivotRows as $p) {
                $t = $templateRows->firstWhere('id', $p->requirement_template_id);
                if (!$t) {
                    continue;
                }

                DB::table('requirement_template_assignments')->updateOrInsert(
                    [
                        'requirement_template_id' => $t->id,
                        'academic_year_id' => $t->academic_year_id,
                        'term_id' => $t->term_id,
                        'classroom_id' => $p->classroom_id,
                        'student_type' => $t->student_type ?? 'both',
                    ],
                    [
                        'brand' => $t->brand,
                        'quantity_per_student' => $t->quantity_per_student ?? 1,
                        'unit' => $t->unit ?? 'piece',
                        'notes' => $t->notes,
                        'leave_with_teacher' => (bool) $t->leave_with_teacher,
                        'is_verification_only' => (bool) $t->is_verification_only,
                        'is_active' => (bool) $t->is_active,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive: do not delete assignments on rollback.
    }
};

