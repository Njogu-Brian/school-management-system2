<?php

/**
 * Export classroom_subjects teacher assignments for GRADE 4–9 into JSON for the seeder.
 *
 * Usage (from project root): php database/scripts/export_grade4_9_assignments.php
 */

use App\Models\Academics\Classroom;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../vendor/autoload.php';
$app = require_once __DIR__.'/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$grades = ['Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9'];

$classroomIds = Classroom::query()
    ->where(function ($q) use ($grades) {
        $q->whereIn('level', $grades);
        foreach ($grades as $g) {
            $num = preg_replace('/\D/', '', $g);
            if ($num !== '') {
                $q->orWhere('name', 'like', "%Grade {$num}%");
            }
        }
    })
    ->pluck('id');

$rows = DB::table('classroom_subjects as cs')
    ->join('classrooms as c', 'c.id', '=', 'cs.classroom_id')
    ->join('subjects as s', 's.id', '=', 'cs.subject_id')
    ->leftJoin('streams as st', 'st.id', '=', 'cs.stream_id')
    ->leftJoin('staff as sf', 'sf.id', '=', 'cs.staff_id')
    ->leftJoin('users as u', 'u.id', '=', 'sf.user_id')
    ->whereIn('cs.classroom_id', $classroomIds)
    ->orderBy('c.name')
    ->orderBy('s.code')
    ->orderByRaw('cs.stream_id IS NULL')
    ->orderBy('cs.stream_id')
    ->get([
        'c.name as classroom_name',
        'st.name as stream_name',
        's.code as subject_code',
        'u.email as staff_email',
        'cs.academic_year_id',
        'cs.term_id',
        'cs.is_compulsory',
        'cs.lessons_per_week',
    ]);

$out = $rows->map(function ($r) {
    return [
        'classroom_name' => $r->classroom_name,
        'subject_code' => $r->subject_code,
        'stream_name' => $r->stream_name,
        'staff_email' => $r->staff_email,
        'academic_year_id' => $r->academic_year_id,
        'term_id' => $r->term_id,
        'is_compulsory' => (bool) $r->is_compulsory,
        'lessons_per_week' => $r->lessons_per_week !== null ? (int) $r->lessons_per_week : null,
    ];
})->values()->all();

$path = __DIR__.'/../data/grade4_9_classroom_subject_assignments.json';
file_put_contents($path, json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n");

echo "Wrote ".count($out)." row(s) to {$path}\n";
