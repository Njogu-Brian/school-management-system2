<?php

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

echo "classroom_ids count: ".$classroomIds->count()."\n";

$rows = DB::table('classroom_subjects as cs')
    ->join('classrooms as c', 'c.id', '=', 'cs.classroom_id')
    ->join('subjects as s', 's.id', '=', 'cs.subject_id')
    ->leftJoin('streams as st', 'st.id', '=', 'cs.stream_id')
    ->leftJoin('staff as sf', 'sf.id', '=', 'cs.staff_id')
    ->leftJoin('users as u', 'u.id', '=', 'sf.user_id')
    ->whereIn('cs.classroom_id', $classroomIds)
    ->orderBy('c.name')
    ->orderBy('s.code')
    ->get([
        'cs.id',
        'c.name as classroom_name',
        'c.level as classroom_level',
        'st.name as stream_name',
        's.code as subject_code',
        's.name as subject_name',
        'sf.id as staff_id',
        'u.email as staff_email',
        DB::raw("CONCAT(COALESCE(sf.first_name,''),' ',COALESCE(sf.last_name,'')) as staff_name"),
        'cs.academic_year_id',
        'cs.term_id',
        'cs.is_compulsory',
        'cs.lessons_per_week',
    ]);

echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

$st = DB::table('subject_teacher as st')
    ->join('subjects as s', 's.id', '=', 'st.subject_id')
    ->join('users as u', 'u.id', '=', 'st.teacher_id')
    ->whereIn('s.level', $grades)
    ->get(['st.subject_id', 's.code as subject_code', 'st.teacher_id', 'u.email as teacher_email']);

echo "\n--- subject_teacher ---\n";
echo json_encode($st, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
