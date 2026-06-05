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

    protected function queryRecent($query, ?string $weekEnding, int $limit)
    {
        if ($weekEnding) {
            $query->whereDate('week_ending', $weekEnding);
        }

        return $query->orderByDesc('week_ending')->limit($limit)->get();
    }
}
