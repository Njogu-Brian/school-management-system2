<?php

namespace App\Services;

use App\Models\Academics\ReportCard;
use App\Models\Academics\ExamMark;
use App\Models\Academics\ExamGrade;
use App\Models\Attendance;
use App\Models\Academics\StudentBehaviour;
use App\Models\Setting; // if you store branding here; otherwise adjust.

class ReportCardBatchService
{
    /**
     * Generate/update report cards for a whole class & term
     * by averaging all exams in the term (your existing logic).
     */
    public function generateForClass($academicYearId, $termId, $classroomId, $streamId = null): void
    {
        $students = \App\Models\Student::where('classroom_id', $classroomId)
            ->when($streamId, fn ($q) => $q->where('stream_id', $streamId))
            ->get();

        foreach ($students as $student) {
            $marks = ExamMark::with('exam','subject')
                ->where('student_id', $student->id)
                ->whereHas('exam', fn ($q) => $q
                    ->where('academic_year_id', $academicYearId)
                    ->where('term_id', $termId))
                ->get();

            if ($marks->isEmpty()) {
                continue;
            }

            $total   = $marks->sum('score_raw');
            $average = $marks->avg('score_raw') ?? 0;

            $gradeData = ExamGrade::where('exam_type','TERM')
                ->where('percent_from','<=',$average)
                ->where('percent_upto','>=',$average)
                ->first();

            $summary = [
                'subjects' => $marks->map(fn ($m) => [
                    'subject' => $m->subject?->name,
                    'score'   => $m->score_raw,
                    'grade'   => $m->grade_label,
                    'remark'  => $m->remark,
                ]),
                'total'   => $total,
                'average' => $average,
                'grade'   => $gradeData?->grade_name ?? 'N/A',
            ];

            ReportCard::updateOrCreate(
                [
                    'student_id'       => $student->id,
                    'academic_year_id' => $academicYearId,
                    'term_id'          => $termId,
                ],
                [
                    'classroom_id'     => $classroomId,
                    'stream_id'        => $streamId,
                    'summary'          => $summary,
                ]
            );
        }
    }

    /**
     * Build a single ReportCard DTO for PDF rendering.
     * Collects: student + class info, per-subject term marks across exams,
     * skills, attendance, behaviour and school branding.
     */
    public static function build(int $reportCardId): array
    {
        $report = ReportCard::with([
            'student.classroom','student.stream',
            'academicYear','term','classroom','stream',
            'skills',
        ])->findOrFail($reportCardId);

        $student = $report->student;
        $yearId  = $report->academic_year_id;
        $termId  = $report->term_id;

        // All marks for this student within term/year grouped by subject
        $marks = ExamMark::with(['exam','subject'])
            ->where('student_id', $student->id)
            ->whereHas('exam', fn ($q) => $q
                ->where('academic_year_id', $yearId)
                ->where('term_id', $termId))
            ->get()
            ->groupBy(fn ($m) => $m->subject?->name ?? 'Unknown');

        // Build list of distinct exams (order by exam start if you like)
        $examNames = ExamMark::with('exam')
            ->where('student_id', $student->id)
            ->whereHas('exam', fn ($q) => $q
                ->where('academic_year_id', $yearId)
                ->where('term_id', $termId))
            ->get()
            ->pluck('exam.name')
            ->unique()
            ->values()
            ->all();

        $subjectsRows = [];
        foreach ($marks as $subjectName => $rows) {
            // map exam name => score
            $byExam = [];
            foreach ($examNames as $examName) {
                $m = $rows->first(fn ($r) => $r->exam?->name === $examName);
                $byExam[$examName] = $m?->score_raw;
            }

            // average across the available exams for this subject
            $scoresOnly = collect($byExam)->filter(fn ($v) => $v !== null)->values();
            $avg = $scoresOnly->count() ? round($scoresOnly->avg(), 2) : null;

            // grade from any band that matches the average (fallback to any subject row's labels)
            $gradeLabel = $rows->first()?->grade_label ?? null;

            $subjectsRows[] = [
                'subject_name'  => $subjectName,
                'exams'         => collect($byExam)->map(fn ($score, $examName) => [
                    'exam_name' => $examName,
                    'score'     => $score,
                ])->values()->all(),
                'term_avg'      => $avg,
                'grade_label'   => $gradeLabel,
                'teacher_remark'=> $rows->first()?->subject_remark,
            ];
        }

        // Skills (per-report skills)
        $skills = $report->skills->map(fn ($s) => [
            'skill'   => $s->skill_name,
            'grade'   => $s->rating,
            'comment' => $s->comment ?? null,
        ])->values()->all();

        // Attendance for the term (simple range: use created_at month/term if you have dates on terms)
        $attendanceQuery = Attendance::where('student_id', $student->id);
        // If you store term date ranges, filter here. For now, just count totals in term’s months if available.

        $present = (clone $attendanceQuery)->where('status','present')->count();
        $late    = (clone $attendanceQuery)->where('status','late')->count();
        $absent  = (clone $attendanceQuery)->where('status','absent')->count();
        $total   = $present + $late + $absent;
        $percent = $total ? round($present / $total * 100, 1) : 0;

        // Behaviour in term/year
        $beh = StudentBehaviour::with('behaviour')
            ->where('student_id', $student->id)
            ->where('academic_year_id', $yearId)
            ->where('term_id', $termId)
            ->latest()
            ->get();

        $behavior = [
            'count'    => $beh->count(),
            'positive' => $beh->filter(fn ($r) => strtolower($r->behaviour?->type) === 'positive')->count(),
            'negative' => $beh->filter(fn ($r) => strtolower($r->behaviour?->type) === 'negative')->count(),
            'latest'   => $beh->take(5)->map(fn ($r) => [
                'date' => optional($r->created_at)->format('d M Y'),
                'name' => $r->behaviour?->name,
                'type' => $r->behaviour?->type,
                'notes'=> $r->notes,
            ])->values()->all(),
        ];

        // Branding (pull from your settings table/logic)
        $branding = [
            'school_name' => setting('school_name') ?? 'Your School',
            'logo_path'   => setting('logo_path') ? public_path(setting('logo_path')) : public_path('images/logo.png'),
            'address'     => setting('school_address') ?? '',
            'phone'       => setting('school_phone') ?? '',
        ];

        return [
            'student' => [
                'name'              => $student->full_name,
                'admission_number'  => $student->admission_number ?? '',
                'class'             => $report->classroom?->name ?? '',
                'stream'            => $report->stream?->name ?? '',
            ],
            'context' => [
                'year'  => $report->academicYear?->year ?? '',
                'term'  => $report->term?->name ?? '',
                'exams' => $examNames,
            ],
            'subjects'   => $subjectsRows,
            'skills'     => $skills,
            'attendance' => compact('present','late','absent','percent'),
            'behavior'   => $behavior,
            'comments'   => [
                'teacher_remark'   => (string) $report->teacher_remark,
                'headteacher_remark'=> (string) $report->headteacher_remark,
                'career_interest'  => (string) $report->career_interest,
                'talent_noticed'   => (string) $report->talent_noticed,
            ],
            'branding'   => $branding,
            'generated'  => [
                'by'   => auth()->user()?->name,
                'at'   => now()->format('d M Y H:i'),
            ],
        ];
    }
}
