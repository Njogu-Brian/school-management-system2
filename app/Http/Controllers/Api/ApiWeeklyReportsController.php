<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reports\ClassReport;
use App\Models\Reports\OperationsFacility;
use App\Models\Reports\StaffWeekly;
use App\Models\Reports\StudentFollowup;
use App\Models\Reports\SubjectReport;
use Illuminate\Http\Request;

class ApiWeeklyReportsController extends Controller
{
    public function index(Request $request)
    {
        $weekEnding = $request->input('week_ending');
        $limit = min((int) $request->input('limit', 50), 200);

        $staffWeekly = $this->queryRecent(StaffWeekly::with('staff'), $weekEnding, $limit)
            ->map(fn ($r) => [
                'type' => 'staff_weekly',
                'id' => $r->id,
                'week_ending' => $r->week_ending?->format('Y-m-d'),
                'title' => $r->staff?->full_name ?? 'Staff weekly',
                'subtitle' => $r->general_performance,
                'campus' => $r->campus,
            ]);

        $classReports = $this->queryRecent(ClassReport::with(['classroom', 'classTeacher']), $weekEnding, $limit)
            ->map(fn ($r) => [
                'type' => 'class_report',
                'id' => $r->id,
                'week_ending' => $r->week_ending?->format('Y-m-d'),
                'title' => $r->classroom?->name ?? 'Class report',
                'subtitle' => $r->classTeacher?->full_name,
                'campus' => $r->campus,
            ]);

        $subjectReports = $this->queryRecent(SubjectReport::with(['subject', 'staff', 'classroom']), $weekEnding, $limit)
            ->map(fn ($r) => [
                'type' => 'subject_report',
                'id' => $r->id,
                'week_ending' => $r->week_ending?->format('Y-m-d'),
                'title' => $r->subject?->name ?? 'Subject report',
                'subtitle' => $r->staff?->full_name,
                'campus' => $r->campus,
            ]);

        $followups = $this->queryRecent(StudentFollowup::with(['student', 'classroom']), $weekEnding, $limit)
            ->map(fn ($r) => [
                'type' => 'student_followup',
                'id' => $r->id,
                'week_ending' => $r->week_ending?->format('Y-m-d'),
                'title' => $r->student?->full_name ?? 'Student follow-up',
                'subtitle' => $r->classroom?->name,
                'campus' => $r->campus,
            ]);

        $facilities = $this->queryRecent(OperationsFacility::query(), $weekEnding, $limit)
            ->map(fn ($r) => [
                'type' => 'operations_facility',
                'id' => $r->id,
                'week_ending' => $r->week_ending?->format('Y-m-d'),
                'title' => $r->area ?? 'Facility report',
                'subtitle' => $r->status,
                'campus' => $r->campus,
                'resolved' => (bool) $r->resolved,
            ]);

        $items = collect()
            ->merge($staffWeekly)
            ->merge($classReports)
            ->merge($subjectReports)
            ->merge($followups)
            ->merge($facilities)
            ->sortByDesc('week_ending')
            ->take($limit)
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $items,
                'counts' => [
                    'staff_weekly' => $staffWeekly->count(),
                    'class_report' => $classReports->count(),
                    'subject_report' => $subjectReports->count(),
                    'student_followup' => $followups->count(),
                    'operations_facility' => $facilities->count(),
                ],
            ],
        ]);
    }

    public function show(string $type, int $id)
    {
        $payload = match ($type) {
            'staff_weekly' => $this->staffWeeklyDetail($id),
            'class_report' => $this->classReportDetail($id),
            'subject_report' => $this->subjectReportDetail($id),
            'student_followup' => $this->studentFollowupDetail($id),
            'operations_facility' => $this->facilityDetail($id),
            default => null,
        };

        if ($payload === null) {
            return response()->json(['success' => false, 'message' => 'Unknown report type.'], 404);
        }

        return response()->json(['success' => true, 'data' => $payload]);
    }

    protected function staffWeeklyDetail(int $id): array
    {
        $r = StaffWeekly::with('staff')->findOrFail($id);

        return [
            'type' => 'staff_weekly',
            'id' => $r->id,
            'week_ending' => $r->week_ending?->format('Y-m-d'),
            'campus' => $r->campus,
            'title' => $r->staff?->full_name ?? 'Staff weekly',
            'subtitle' => $r->general_performance,
            'fields' => [
                ['label' => 'Staff', 'value' => $r->staff?->full_name],
                ['label' => 'On time all week', 'value' => $r->on_time_all_week ? 'Yes' : 'No'],
                ['label' => 'Lessons missed', 'value' => (string) ($r->lessons_missed ?? 0)],
                ['label' => 'Books marked', 'value' => $r->books_marked ? 'Yes' : 'No'],
                ['label' => 'Schemes updated', 'value' => $r->schemes_updated ? 'Yes' : 'No'],
                ['label' => 'Class control', 'value' => $r->class_control],
                ['label' => 'General performance', 'value' => $r->general_performance],
            ],
            'notes' => $r->notes,
        ];
    }

    protected function classReportDetail(int $id): array
    {
        $r = ClassReport::with(['classroom', 'classTeacher'])->findOrFail($id);

        return [
            'type' => 'class_report',
            'id' => $r->id,
            'week_ending' => $r->week_ending?->format('Y-m-d'),
            'campus' => $r->campus,
            'title' => $r->classroom?->name ?? 'Class report',
            'subtitle' => $r->classTeacher?->full_name,
            'fields' => [
                ['label' => 'Class teacher', 'value' => $r->classTeacher?->full_name],
                ['label' => 'Total learners', 'value' => (string) ($r->total_learners ?? 0)],
                ['label' => 'Frequent absentees', 'value' => $r->frequent_absentees],
                ['label' => 'Discipline level', 'value' => $r->discipline_level],
                ['label' => 'Homework completion', 'value' => $r->homework_completion],
                ['label' => 'Learners struggling', 'value' => $r->learners_struggling],
                ['label' => 'Learners improved', 'value' => $r->learners_improved],
                ['label' => 'Parents to contact', 'value' => $r->parents_to_contact],
                ['label' => 'Classroom condition', 'value' => $r->classroom_condition],
            ],
            'notes' => $r->notes,
        ];
    }

    protected function subjectReportDetail(int $id): array
    {
        $r = SubjectReport::with(['subject', 'staff', 'classroom'])->findOrFail($id);

        return [
            'type' => 'subject_report',
            'id' => $r->id,
            'week_ending' => $r->week_ending?->format('Y-m-d'),
            'campus' => $r->campus,
            'title' => $r->subject?->name ?? 'Subject report',
            'subtitle' => $r->staff?->full_name,
            'fields' => [
                ['label' => 'Teacher', 'value' => $r->staff?->full_name],
                ['label' => 'Class', 'value' => $r->classroom?->name],
                ['label' => 'Topics covered', 'value' => $r->topics_covered],
                ['label' => 'Syllabus status', 'value' => $r->syllabus_status],
                ['label' => 'Strong learners %', 'value' => $r->strong_percent !== null ? $r->strong_percent.'%' : null],
                ['label' => 'Average learners %', 'value' => $r->average_percent !== null ? $r->average_percent.'%' : null],
                ['label' => 'Struggling learners %', 'value' => $r->struggling_percent !== null ? $r->struggling_percent.'%' : null],
                ['label' => 'Homework given', 'value' => $r->homework_given ? 'Yes' : 'No'],
                ['label' => 'Test done', 'value' => $r->test_done ? 'Yes' : 'No'],
                ['label' => 'Marking done', 'value' => $r->marking_done ? 'Yes' : 'No'],
                ['label' => 'Main challenge', 'value' => $r->main_challenge],
                ['label' => 'Support needed', 'value' => $r->support_needed],
            ],
            'notes' => null,
        ];
    }

    protected function studentFollowupDetail(int $id): array
    {
        $r = StudentFollowup::with(['student', 'classroom'])->findOrFail($id);

        return [
            'type' => 'student_followup',
            'id' => $r->id,
            'week_ending' => $r->week_ending?->format('Y-m-d'),
            'campus' => $r->campus,
            'title' => $r->student?->full_name ?? 'Student follow-up',
            'subtitle' => $r->classroom?->name,
            'fields' => [
                ['label' => 'Student', 'value' => $r->student?->full_name],
                ['label' => 'Class', 'value' => $r->classroom?->name],
                ['label' => 'Academic concern', 'value' => $r->academic_concern ? 'Yes' : 'No'],
                ['label' => 'Behavior concern', 'value' => $r->behavior_concern ? 'Yes' : 'No'],
                ['label' => 'Action taken', 'value' => $r->action_taken],
                ['label' => 'Parent contacted', 'value' => $r->parent_contacted ? 'Yes' : 'No'],
                ['label' => 'Progress status', 'value' => $r->progress_status],
            ],
            'notes' => $r->notes,
        ];
    }

    protected function facilityDetail(int $id): array
    {
        $r = OperationsFacility::findOrFail($id);

        return [
            'type' => 'operations_facility',
            'id' => $r->id,
            'week_ending' => $r->week_ending?->format('Y-m-d'),
            'campus' => $r->campus,
            'title' => $r->area ?? 'Facility report',
            'subtitle' => $r->status,
            'fields' => [
                ['label' => 'Area', 'value' => $r->area],
                ['label' => 'Status', 'value' => $r->status],
                ['label' => 'Issue noted', 'value' => $r->issue_noted],
                ['label' => 'Action needed', 'value' => $r->action_needed],
                ['label' => 'Responsible person', 'value' => $r->responsible_person],
                ['label' => 'Resolved', 'value' => $r->resolved ? 'Yes' : 'No'],
            ],
            'notes' => $r->notes,
        ];
    }

    protected function queryRecent($query, ?string $weekEnding, int $limit)
    {
        if ($weekEnding) {
            $query->whereDate('week_ending', $weekEnding);
        }

        return $query->orderByDesc('week_ending')->limit($limit)->get();
    }
}
