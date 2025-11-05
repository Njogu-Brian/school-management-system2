<?php

namespace App\Services;

use App\Models\Academics\Exam;
use App\Models\Academics\ExamMark;
use App\Models\Student;
use Illuminate\Support\Collection;

class TermAssessmentService
{
    public static function build(int $yearId, int $termId, int $classId, ?int $subjectId = null): array
    {
        $exams = Exam::where('academic_year_id', $yearId)
            ->where('term_id', $termId)
            ->orderBy('starts_on')->get();

        $students = Student::with(['classroom','stream'])
            ->where('classroom_id', $classId)
            ->orderBy('last_name')->get();

        $marks = ExamMark::with(['exam','subject'])
            ->whereIn('exam_id', $exams->pluck('id'))
            ->when($subjectId, fn($q) => $q->where('subject_id', $subjectId))
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->groupBy(['student_id','subject_id']);

        $rows = [];
        foreach ($students as $st) {
            $bySubject = $marks->get($st->id) ?? collect();

            foreach ($bySubject as $subId => $subMarks) {
                $avg = self::avgAcrossExams($subMarks, $exams);
                $rows[] = [
                    'student'    => $st,
                    'subject_id' => $subId,
                    'avg'        => $avg,
                    'marks'      => $subMarks, // list of ExamMark with exam relation
                ];
            }
        }

        return [
            'exams'    => $exams,
            'students' => $students,
            'rows'     => $rows,
        ];
    }

    public static function avgAcrossExams(Collection $subMarks, Collection $exams): ?float
    {
        $byExam = $subMarks->keyBy('exam_id');
        $total = 0; $weightSum = 0;

        foreach ($exams as $exam) {
            $w = (float)($exam->weight ?? 1);
            $m = $byExam->get($exam->id);

            if (!$m || is_null($m->score_raw)) {
                if ($exam->must_sit) { $weightSum += $w; /* contributes 0 */ }
                continue;
            }

            $score = $m->score_raw;
            if (($exam->max_marks ?? 100) != 100) {
                $score = round(($m->score_raw / max(1,$exam->max_marks)) * 100, 2);
            }

            $total += $score * $w;
            $weightSum += $w;
        }

        return $weightSum ? round($total / $weightSum, 2) : null;
    }
}
