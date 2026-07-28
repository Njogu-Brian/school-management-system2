<?php

namespace App\Services;

use App\Models\Academics\ReportCard;
use App\Models\Academics\ExamMark;
use App\Models\Academics\ExamGrade;
use App\Models\Academics\CBCPerformanceLevel;
use App\Models\Attendance;
use App\Models\Academics\StudentBehaviour;
use App\Models\Term;
use App\Models\Setting; // if you store branding here; otherwise adjust.
use App\Services\CBCAssessmentService;
use App\Services\Academics\ClassroomGradingService;
use App\Support\CbcGradePresentation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Services\AttendanceReportService;

class ReportCardBatchService
{
    /**
     * Generate/update report cards for a whole class & term
     * by averaging all exams in the term (your existing logic).
     */
    public function generateForClass($academicYearId, $termId, $classroomId, $streamId = null): void
    {
        // Build report cards for students who actually have marks in this academic year + term
        // for the selected classroom/stream (important when students moved classrooms/streams).
        $studentIds = ExamMark::query()
            ->select('student_id')
            ->whereHas('exam', function ($q) use ($academicYearId, $termId, $classroomId, $streamId) {
                $q->where('academic_year_id', $academicYearId)
                    ->where('term_id', $termId)
                    ->where('classroom_id', $classroomId);

                if ($streamId) {
                    $q->where('stream_id', $streamId);
                }
            })
            ->distinct()
            ->pluck('student_id');

        $students = \App\Models\Student::query()
            ->whereIn('id', $studentIds)
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->when($streamId, fn ($q) => $q->where('stream_id', $streamId))
            ->get();

        foreach ($students as $student) {
            $marks = ExamMark::with('exam','subject')
                ->where('student_id', $student->id)
                ->whereHas('exam', fn ($q) => $q
                    ->where('academic_year_id', $academicYearId)
                    ->where('term_id', $termId)
                    ->where('classroom_id', $classroomId)
                    ->when($streamId, fn ($sq) => $sq->where('stream_id', $streamId)))
                ->get();

            if ($marks->isEmpty()) {
                continue;
            }

            // Prefer moderated scores when available; fallback to raw scores.
            $scores = $marks
                ->map(fn ($m) => $m->score_moderated ?? $m->score_raw)
                ->filter(fn ($v) => $v !== null);

            $total = (float) $scores->sum();
            $average = $scores->count() ? (float) $scores->avg() : null;

            $gradeData = $average === null
                ? null
                : ExamGrade::where('exam_type','TERM')
                    ->where('percent_from','<=',$average)
                    ->where('percent_upto','>=',$average)
                    ->first();

            $summary = [
                'subjects' => $marks->map(fn ($m) => [
                    'subject' => $m->subject?->name,
                    'score'   => $m->score_moderated ?? $m->score_raw,
                    'grade'   => $m->grade_label,
                    'remark'  => $m->remark,
                ]),
                'total'   => $total,
                'average' => $average,
                'grade'   => $gradeData?->grade_name ?? null,
            ];

            // Generate CBC assessment data
            $cbcData = CBCAssessmentService::generateReportCardData(
                $student->id,
                $academicYearId,
                $termId
            );

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
                    'overall_performance_level_id' => $cbcData['overall_performance_level_id'],
                    'performance_summary' => $cbcData['performance_summary'],
                    'core_competencies' => $cbcData['core_competencies'],
                    'learning_areas_performance' => $cbcData['learning_areas_performance'],
                    'cat_breakdown' => $cbcData['cat_breakdown'],
                    'portfolio_summary' => $cbcData['portfolio_summary'],
                ]
            );

            $reportCard = ReportCard::query()
                ->where('student_id', $student->id)
                ->where('academic_year_id', $academicYearId)
                ->where('term_id', $termId)
                ->first();

            if ($reportCard && empty($reportCard->public_token)) {
                $reportCard->update(['public_token' => \Illuminate\Support\Str::random(40)]);
            }
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
            'overallPerformanceLevel', // Load CBC performance level
        ])->findOrFail($reportCardId);

        $student = $report->student;
        $yearId  = $report->academic_year_id;
        $termId  = $report->term_id;
        $term    = $report->term;
        $previousTerm = self::previousTermInAcademicYear($term);
        $previousReport = $previousTerm
            ? ReportCard::query()
                ->where('student_id', $student->id)
                ->where('academic_year_id', $yearId)
                ->where('term_id', $previousTerm->id)
                ->first()
            : null;

        // Marks for this student within term/year for the report classroom.
        // Exams are often created per subject (e.g. "TERM 1 MIDTERM — GRADE 9 — English"),
        // so we collapse them into sitting columns: Opener / Midterm / Endterm.
        $allMarks = ExamMark::with(['exam.examType', 'subject', 'performanceLevel'])
            ->where('student_id', $student->id)
            ->whereHas('exam', fn ($q) => $q
                ->where('academic_year_id', $yearId)
                ->where('term_id', $termId)
                ->where('classroom_id', $report->classroom_id)
                ->when($report->stream_id, fn ($sq) => $sq->where('stream_id', $report->stream_id)))
            ->get();

        $referenceTermMarks = collect();
        if ($previousTerm) {
            $referenceTermMarks = ExamMark::with(['exam.examType', 'subject', 'performanceLevel'])
                ->where('student_id', $student->id)
                ->whereHas('exam', fn ($q) => $q
                    ->where('academic_year_id', $yearId)
                    ->where('term_id', $previousTerm->id)
                    ->where('classroom_id', $report->classroom_id)
                    ->when($report->stream_id, fn ($sq) => $sq->where('stream_id', $report->stream_id)))
                ->get();
        }

        $sittingOrder = ['Opener' => 1, 'Midterm' => 2, 'Endterm' => 3, 'Other' => 4];
        $sittingHeaders = $allMarks
            ->map(fn ($m) => self::sittingLabel($m->exam?->name))
            ->unique()
            ->sortBy(fn ($label) => $sittingOrder[$label] ?? 99)
            ->values()
            ->all();

        $grading = app(ClassroomGradingService::class);
        $classroomId = (int) ($report->classroom_id ?? $student->classroom_id ?? 0);

        $currentSubjectGroups = $allMarks->groupBy(fn ($m) => $m->subject?->name ?? self::subjectFromExamName($m->exam?->name) ?? 'Unknown');
        $referenceSubjectGroups = $referenceTermMarks->groupBy(fn ($m) => $m->subject?->name ?? self::subjectFromExamName($m->exam?->name) ?? 'Unknown');
        $subjectNames = $currentSubjectGroups->keys()
            ->merge($referenceSubjectGroups->keys())
            ->unique()
            ->sort(fn ($a, $b) => strcasecmp((string) $a, (string) $b))
            ->values();

        $subjectsRows = [];
        foreach ($subjectNames as $subjectName) {
            $rows = $currentSubjectGroups->get($subjectName, collect());
            $referenceRows = $referenceSubjectGroups->get($subjectName, collect());
            $bySitting = [];
            foreach ($sittingHeaders as $sitting) {
                $m = $rows->first(fn ($r) => self::sittingLabel($r->exam?->name) === $sitting);
                $score = $m ? ($m->score_moderated ?? $m->score_raw) : null;
                $grade = $m?->grade_label;
                if ($m && ($grade === null || $grade === '') && $score !== null && $classroomId > 0) {
                    $max = (float) ($m->exam?->examType?->default_max_mark ?? $m->exam?->max_marks ?? 100);
                    $grade = $grading->gradeForRawScore((float) $score, $max, $classroomId)['label'] ?? null;
                }
                $bySitting[$sitting] = [
                    'exam_name' => $sitting,
                    'score' => $score !== null ? (float) $score : null,
                    'grade_label' => self::normalizeGradeLabel($grade),
                    'pl_level' => $m?->pl_level,
                    'performance_level' => self::normalizeGradeLabel($m?->performanceLevel?->code ?? $m?->pl_level),
                    'rubrics' => $m?->rubrics,
                ];
            }

            $scoresOnly = collect($bySitting)->pluck('score')->filter(fn ($v) => $v !== null)->values();
            $avg = $scoresOnly->count() ? round((float) $scoresOnly->avg(), 2) : null;

            $gradeLabel = collect($bySitting)->pluck('grade_label')->filter()->last();
            if (($gradeLabel === null || $gradeLabel === '') && $avg !== null && $classroomId > 0) {
                $gradeLabel = $grading->gradeForPercentage($avg, $classroomId)['label'] ?? null;
            }

            $remark = $rows->sortByDesc(fn ($r) => $r->updated_at)->first(fn ($r) => filled($r->subject_remark))?->subject_remark
                ?? $rows->first()?->subject_remark;

            $referenceScores = $referenceRows
                ->map(fn ($m) => $m->score_moderated ?? $m->score_raw)
                ->filter(fn ($v) => $v !== null)
                ->values();
            $referenceAverage = $referenceScores->count() ? round((float) $referenceScores->avg(), 2) : null;
            $referenceGrade = $referenceRows
                ->pluck('grade_label')
                ->filter()
                ->last();
            if (($referenceGrade === null || $referenceGrade === '') && $referenceAverage !== null && $classroomId > 0) {
                $referenceGrade = $grading->gradeForPercentage($referenceAverage, $classroomId)['label'] ?? null;
            }

            $subjectsRows[] = [
                'subject_name' => $subjectName,
                'exams' => array_values($bySitting),
                'reference_term_avg' => $referenceAverage,
                'reference_grade_label' => self::normalizeGradeLabel($referenceGrade),
                'term_avg' => $avg,
                'grade_label' => self::normalizeGradeLabel($gradeLabel),
                'teacher_remark' => $remark,
            ];
        }

        $examNames = $sittingHeaders;
        $termTrend = self::academicYearTrend(
            $student->id,
            $yearId,
            (int) ($report->classroom_id ?? 0),
            $report->stream_id ? (int) $report->stream_id : null
        );
        $currentOverviewAverage = data_get($report->summary, 'average');
        if ($currentOverviewAverage === null) {
            $currentOverviewAverage = collect($subjectsRows)->pluck('term_avg')->filter(fn ($v) => $v !== null)->avg();
        }
        $referenceOverviewAverage = data_get($previousReport?->summary ?? [], 'average');
        if ($referenceOverviewAverage === null) {
            $referenceOverviewAverage = collect($subjectsRows)->pluck('reference_term_avg')->filter(fn ($v) => $v !== null)->avg();
        }

        // Skills (per-report skills)
        $skills = $report->skills->map(fn ($s) => [
            'skill'   => $s->skill_name,
            'grade'   => $s->rating,
            'comment' => $s->comment ?? null,
        ])->values()->all();

        // Attendance for the term (present X out of X school days)
        $present = 0;
        $late = 0;
        $absent = 0;
        $expectedSchoolDays = 0;
        $percent = 0;

        if ($term && $term->opening_date && $term->closing_date) {
            $stats = app(AttendanceReportService::class)->studentStats(
                $student,
                $term->opening_date->toDateString(),
                $term->closing_date->toDateString()
            );
            $present = (int) ($stats['present'] ?? 0);
            $late = (int) ($stats['late'] ?? 0);
            $absent = (int) ($stats['absent'] ?? 0);
            $expectedSchoolDays = (int) ($stats['expected_school_days'] ?? 0);
            $percent = (float) ($stats['percent'] ?? 0);
        } else {
            // Fallback: count recorded attendance rows only
            $attendanceQuery = Attendance::where('student_id', $student->id);
            $present = (clone $attendanceQuery)->where('status', 'present')->count();
            $late    = (clone $attendanceQuery)->where('status', 'late')->count();
            $absent  = (clone $attendanceQuery)->where('status', 'absent')->count();
            $expectedSchoolDays = $present + $late + $absent;
            $attending = $present + $late;
            $percent = $expectedSchoolDays ? round(($attending / $expectedSchoolDays) * 100, 1) : 0;
        }

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
            'logo_path'   => (function () {
                $logo = setting('school_logo');
                if ($logo && storage_public()->exists($logo)) {
                    return storage_path('app/public/' . $logo);
                }
                if ($logo && file_exists(public_path('images/' . $logo))) {
                    return public_path('images/' . $logo);
                }
                return null;
            })(),
            'address'     => setting('school_address') ?? '',
            'phone'       => setting('school_phone') ?? '',
            'email'       => setting('school_email') ?? '',
            'website'     => setting('school_website') ?? '',
            'header_html' => setting('pdf_header_html') ?? '',
            'footer_html' => setting('pdf_footer_html') ?? '',
        ];

        // CBC Data from report card
        $cbcData = [
            'overall_performance_level' => self::normalizeGradeLabel($report->overallPerformanceLevel?->code ?? null),
            'overall_performance_level_name' => CbcGradePresentation::nameFromShortCode($report->overallPerformanceLevel?->code)
                ?? $report->overallPerformanceLevel?->name
                ?? null,
            'performance_summary' => $report->performance_summary ?? [],
            'core_competencies' => $report->core_competencies ?? [],
            'learning_areas_performance' => collect($report->learning_areas_performance ?? [])
                ->map(function ($performance) {
                    if (! is_array($performance)) {
                        return $performance;
                    }
                    $code = self::normalizeGradeLabel($performance['performance_level'] ?? null);

                    return array_merge($performance, [
                        'performance_level' => $code,
                        'performance_level_name' => CbcGradePresentation::nameFromShortCode($code)
                            ?? ($performance['performance_level_name'] ?? null),
                    ]);
                })
                ->all(),
            'cat_breakdown' => $report->cat_breakdown ?? [],
            'portfolio_summary' => $report->portfolio_summary ?? [],
            'co_curricular' => $report->co_curricular ?? [],
            'personal_social_dev' => $report->personal_social_dev ?? [],
        ];

        // If CBC data is missing, generate it
        if (empty($report->core_competencies) || empty($report->learning_areas_performance)) {
            try {
                $generatedCBC = CBCAssessmentService::generateReportCardData(
                    $student->id,
                    $yearId,
                    $termId
                );
                
                // Merge generated data if report card doesn't have it
                if (empty($report->core_competencies) && !empty($generatedCBC['core_competencies'])) {
                    $cbcData['core_competencies'] = $generatedCBC['core_competencies'];
                }
                if (empty($report->learning_areas_performance) && !empty($generatedCBC['learning_areas_performance'])) {
                    $cbcData['learning_areas_performance'] = $generatedCBC['learning_areas_performance'];
                }
                if (empty($report->cat_breakdown) && !empty($generatedCBC['cat_breakdown'])) {
                    $cbcData['cat_breakdown'] = $generatedCBC['cat_breakdown'];
                }
                if (empty($report->portfolio_summary) && !empty($generatedCBC['portfolio_summary'])) {
                    $cbcData['portfolio_summary'] = $generatedCBC['portfolio_summary'];
                }
                if (!$report->overall_performance_level_id && !empty($generatedCBC['overall_performance_level_id'])) {
                    $performanceLevel = CBCPerformanceLevel::find($generatedCBC['overall_performance_level_id']);
                    if ($performanceLevel) {
                        $cbcData['overall_performance_level'] = self::normalizeGradeLabel($performanceLevel->code);
                        $cbcData['overall_performance_level_name'] = CbcGradePresentation::nameFromShortCode($performanceLevel->code)
                            ?? $performanceLevel->name;
                    }
                }
            } catch (\Exception $e) {
                // Log error but continue without CBC data
                Log::warning('Failed to generate CBC data for report card: ' . $e->getMessage());
            }
        }

        return [
            'student' => [
                'name'              => $student->name ?? trim($student->first_name . ' ' . ($student->middle_name ? $student->middle_name . ' ' : '') . $student->last_name),
                'admission_number'  => $student->admission_number ?? '',
                'class'             => $report->classroom?->name ?? '',
                'stream'            => $report->stream?->name ?? '',
            ],
            'context' => [
                'year'  => $report->academicYear?->year ?? '',
                'term'  => $report->term?->name ?? '',
                'exams' => $examNames,
                'reference_term' => $previousTerm?->name,
            ],
            'overview' => [
                'current_term' => [
                    'name' => $report->term?->name ?? '',
                    'average' => $currentOverviewAverage !== null ? round((float) $currentOverviewAverage, 2) : null,
                    'grade' => self::resolveOverviewGrade(
                        $currentOverviewAverage,
                        data_get($report->summary, 'grade'),
                        $classroomId
                    ),
                ],
                'reference_term' => [
                    'name' => $previousTerm?->name,
                    'average' => $referenceOverviewAverage !== null ? round((float) $referenceOverviewAverage, 2) : null,
                    'grade' => self::resolveOverviewGrade(
                        $referenceOverviewAverage,
                        data_get($previousReport?->summary ?? [], 'grade'),
                        $classroomId
                    ),
                ],
            ],
            'subjects'   => $subjectsRows,
            'skills'     => $skills,
            'attendance' => [
                'present' => $present,
                'late' => $late,
                'absent' => $absent,
                'total' => $expectedSchoolDays,
                'expected_school_days' => $expectedSchoolDays,
                'attending' => $present + $late,
                'percent' => $percent,
            ],
            'behavior'   => $behavior,
            'comments'   => [
                'teacher_remark'   => (string) $report->teacher_remark,
                'headteacher_remark'=> (string) $report->headteacher_remark,
                'career_interest'  => (string) $report->career_interest,
                'talent_noticed'   => (string) $report->talent_noticed,
            ],
            'cbc'        => $cbcData,
            'year_trend' => $termTrend,
            'branding'   => $branding,
            'generated'  => [
                'by'   => auth()->user()?->name ?? 'System',
                'at'   => now()->format('d M Y, H:i'),
            ],
        ];
    }

    /**
     * Collapse per-subject exam titles into a sitting label for report-card columns.
     * e.g. "TERM 1 MIDTERM — GRADE 9 — English" => "Midterm"
     */
    protected static function sittingLabel(?string $examName): string
    {
        $name = strtolower((string) $examName);

        if (str_contains($name, 'opener')) {
            return 'Opener';
        }
        if (str_contains($name, 'mid')) {
            return 'Midterm';
        }
        if (str_contains($name, 'end')) {
            return 'Endterm';
        }

        return 'Other';
    }

    /**
     * Best-effort subject name from exam title when subject relation is missing.
     */
    protected static function subjectFromExamName(?string $examName): ?string
    {
        if (! $examName) {
            return null;
        }

        // Common pattern: "TERM 1 MIDTERM — GRADE 9 — English"
        if (preg_match('/(?:—|-)\s*([^—\-]+)$/u', $examName, $m)) {
            $subject = trim($m[1]);

            return $subject !== '' ? $subject : null;
        }

        return null;
    }

    protected static function normalizeGradeLabel(mixed $label): ?string
    {
        if ($label === null || $label === '') {
            return null;
        }

        return CbcGradePresentation::normalizeShortCode((string) $label);
    }

    protected static function resolveOverviewGrade(mixed $average, mixed $storedGrade, int $classroomId): ?string
    {
        $normalizedStored = self::normalizeGradeLabel($storedGrade);
        if ($normalizedStored !== null && $normalizedStored !== '') {
            return $normalizedStored;
        }

        if ($average === null || $classroomId <= 0) {
            return null;
        }

        $graded = app(ClassroomGradingService::class)->gradeForPercentage((float) $average, $classroomId);

        return self::normalizeGradeLabel($graded['label'] ?? null);
    }

    protected static function previousTermInAcademicYear(?Term $term): ?Term
    {
        if (! $term) {
            return null;
        }

        $terms = Term::query()
            ->where('academic_year_id', $term->academic_year_id)
            ->orderByRaw('CASE WHEN opening_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('opening_date')
            ->orderBy('id')
            ->get();

        $index = $terms->search(fn (Term $item) => (int) $item->id === (int) $term->id);
        if ($index === false || $index === 0) {
            return null;
        }

        return $terms->get($index - 1);
    }

    protected static function academicYearTrend(int $studentId, int $yearId, int $classroomId, ?int $streamId = null): array
    {
        $marks = ExamMark::with(['exam.term', 'exam.examType'])
            ->where('student_id', $studentId)
            ->whereHas('exam', fn ($q) => $q
                ->where('academic_year_id', $yearId)
                ->where('classroom_id', $classroomId)
                ->when($streamId, fn ($sq) => $sq->where('stream_id', $streamId)))
            ->get();

        if ($marks->isEmpty()) {
            return [
                'labels' => [],
                'values' => [],
                'points' => [],
                'min' => 0,
                'max' => 100,
            ];
        }

        $terms = Term::query()
            ->where('academic_year_id', $yearId)
            ->orderByRaw('CASE WHEN opening_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('opening_date')
            ->orderBy('id')
            ->get()
            ->values();

        $termOrder = $terms->pluck('id')->flip();
        $sittingOrder = ['Opener' => 1, 'Midterm' => 2, 'Endterm' => 3, 'Other' => 4];

        $points = $marks
            ->groupBy(function ($mark) {
                $termId = $mark->exam?->term_id ?? 0;
                $sitting = self::sittingLabel($mark->exam?->name);

                return $termId.'|'.$sitting;
            })
            ->map(function (Collection $group, string $key) use ($termOrder, $sittingOrder) {
                [$termId, $sitting] = explode('|', $key, 2);
                $first = $group->first();
                $term = $first?->exam?->term;
                $scores = $group
                    ->map(fn ($m) => $m->score_moderated ?? $m->score_raw)
                    ->filter(fn ($v) => $v !== null)
                    ->values();

                return [
                    'term_id' => (int) $termId,
                    'term_name' => $term?->name ?? ('Term '.$termId),
                    'sitting' => $sitting,
                    'label' => trim(($term?->name ?? ('Term '.$termId)).' '.$sitting),
                    'value' => $scores->count() ? round((float) $scores->avg(), 2) : null,
                    'sort_term' => $termOrder->get((int) $termId, 999),
                    'sort_sitting' => $sittingOrder[$sitting] ?? 99,
                ];
            })
            ->filter(fn ($row) => $row['value'] !== null)
            ->sortBy([
                ['sort_term', 'asc'],
                ['sort_sitting', 'asc'],
            ])
            ->values();

        $values = $points->pluck('value')->values()->all();

        return [
            'labels' => $points->pluck('label')->all(),
            'values' => $values,
            'points' => $points->map(fn ($point) => [
                'label' => $point['label'],
                'value' => $point['value'],
            ])->all(),
            'min' => 0,
            'max' => 100,
        ];
    }

    public static function pdfFilename(array $dto): string
    {
        $termSlug = strtoupper(preg_replace('/\s+/', '', (string) ($dto['context']['term'] ?? 'Term')));
        $year = preg_replace('/\D/', '', (string) ($dto['context']['year'] ?? '')) ?: date('Y');
        $adm = strtoupper(preg_replace('/\s+/', '', (string) ($dto['student']['admission_number'] ?? 'STUDENT')));

        return "{$termSlug}-{$year}-{$adm} Report Card.pdf";
    }
}
