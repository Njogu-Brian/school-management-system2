<?php

namespace App\Services;

use App\Models\Academics\ReportCard;
use App\Models\Academics\ExamMark;
use App\Models\Academics\ExamGrade;

class ReportGenerationService
{
    public function generateForClass($academicYearId, $termId, $classroomId, $streamId=null)
    {
        $students = \App\Models\Student::where('classroom_id',$classroomId)
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->when($streamId, fn($q)=>$q->where('stream_id',$streamId))
            ->get();

        foreach ($students as $student) {
            $marks = ExamMark::with('exam','subject')
                ->where('student_id',$student->id)
                ->whereHas('exam', fn($q)=>$q
                    ->where('academic_year_id',$academicYearId)
                    ->where('term_id',$termId))
                ->get();

            if ($marks->isEmpty()) continue;

            $total   = $marks->sum('score_raw');
            $average = $marks->avg('score_raw') ?? 0;

            $gradeData = ExamGrade::where('exam_type','TERM')
                ->where('percent_from','<=',$average)
                ->where('percent_upto','>=',$average)
                ->first();

            $summary = [
                'subjects'=>$marks->map(fn($m)=>[
                    'subject'=>$m->subject->name,
                    'score'=>$m->score_raw,
                    'grade'=>$m->grade_label,
                    'remark'=>$m->remark,
                ]),
                'total'=>$total,
                'average'=>$average,
                'grade'=>$gradeData?->grade_name ?? 'N/A',
            ];

            ReportCard::updateOrCreate(
                [
                    'student_id'=>$student->id,
                    'academic_year_id'=>$academicYearId,
                    'term_id'=>$termId,
                ],
                [
                    'classroom_id'=>$classroomId,
                    'stream_id'=>$streamId,
                    'summary'=>$summary,
                ]
            );
        }
    }
}
