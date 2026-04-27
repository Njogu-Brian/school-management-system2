<?php

namespace App\Services\Timetable;

use App\Models\AcademicYear;
use App\Models\Academics\Stream;
use App\Models\Academics\TimetableLayoutPeriod;
use App\Models\Academics\TimetableStreamActivityRequirement;
use App\Models\Academics\TimetableStreamActivityTeacher;
use App\Models\Academics\TimetableStreamLayout;
use App\Models\Academics\TimetableStreamSubjectRequirement;
use App\Models\Academics\TimetableStreamSubjectTeacher;
use App\Models\Staff;
use App\Models\Term;
use Illuminate\Support\Collection;

class FeasibilityValidator
{
    public function validateWholeSchool(int $academicYearId, int $termId, array $streamIds = []): array
    {
        $year = AcademicYear::findOrFail($academicYearId);
        $term = Term::findOrFail($termId);

        $streams = Stream::query()
            ->when($streamIds !== [], fn ($q) => $q->whereIn('id', array_map('intval', $streamIds)))
            ->orderBy('name')
            ->get();

        $out = [
            'success' => true,
            'meta' => [
                'academic_year_id' => $year->id,
                'term_id' => $term->id,
                'stream_count' => $streams->count(),
            ],
            'streams' => [],
            'teacher_load' => [],
            'errors' => [],
        ];

        // Global cap only (weekly lesson slots)
        $defaultMax = (int) setting('max_lessons_per_teacher_per_week', 40);

        $teacherDemand = []; // staff_id => total periods demanded

        foreach ($streams as $stream) {
            $layout = TimetableStreamLayout::where('stream_id', $stream->id)
                ->where('academic_year_id', $year->id)
                ->where('term_id', $term->id)
                ->first();

            if (! $layout) {
                $out['streams'][] = [
                    'stream_id' => $stream->id,
                    'stream_name' => $stream->name,
                    'ok' => false,
                    'errors' => ['No layout assigned to this stream.'],
                ];
                $out['success'] = false;
                continue;
            }

            $periods = TimetableLayoutPeriod::where('template_id', $layout->template_id)->get();
            $lessonSlots = $periods->where('slot_type', 'lesson')->count();
            $combinePairs = $this->countCombinablePairs($periods);

            $reqs = TimetableStreamSubjectRequirement::where('stream_id', $stream->id)
                ->where('academic_year_id', $year->id)
                ->where('term_id', $term->id)
                ->get();

            $allocs = TimetableStreamSubjectTeacher::where('stream_id', $stream->id)
                ->where('academic_year_id', $year->id)
                ->where('term_id', $term->id)
                ->get();

            $activityReqs = TimetableStreamActivityRequirement::where('stream_id', $stream->id)
                ->where('academic_year_id', $year->id)
                ->where('term_id', $term->id)
                ->get();

            $activityTeachers = TimetableStreamActivityTeacher::whereIn('activity_requirement_id', $activityReqs->pluck('id'))->get();

            $errors = [];

            $requiredLessons = (int) $reqs->sum('periods_per_week');
            $blockedActivities = (int) $activityReqs->where('is_teacher_assigned', false)->sum('periods_per_week');
            $teacherActivities = (int) $activityReqs->where('is_teacher_assigned', true)->sum('periods_per_week');

            $availableLessonSlots = max(0, $lessonSlots - $blockedActivities);
            $totalDemandSlots = $requiredLessons + $teacherActivities;

            if ($availableLessonSlots !== $totalDemandSlots) {
                $errors[] = "Capacity mismatch: available lesson slots ({$availableLessonSlots}) vs demand ({$totalDemandSlots}).";
            }

            // Allocation completeness per subject
            $allocBySubject = $allocs->groupBy('subject_id')->map(fn ($g) => (int) $g->sum('periods_per_week'));
            foreach ($reqs as $r) {
                $allocated = (int) ($allocBySubject[$r->subject_id] ?? 0);
                if ($allocated !== (int) $r->periods_per_week) {
                    $errors[] = "Allocation mismatch for subject_id={$r->subject_id}: required {$r->periods_per_week}, allocated {$allocated}.";
                }
            }

            // Teacher assigned activities need teachers if they have any demand
            foreach ($activityReqs->where('is_teacher_assigned', true) as $ar) {
                $tSum = (int) $activityTeachers->where('activity_requirement_id', $ar->id)->sum('periods_per_week');
                if ($tSum !== (int) $ar->periods_per_week) {
                    $errors[] = "Activity allocation mismatch for '{$ar->name}': required {$ar->periods_per_week}, allocated {$tSum}.";
                }
            }

            // Double feasibility
            $subjectsNeedingDoubles = $reqs->where('allow_double', true)->where('max_doubles_per_week', '>', 0);
            $totalRequestedDoubles = (int) $subjectsNeedingDoubles->sum('max_doubles_per_week');
            if ($totalRequestedDoubles > $combinePairs) {
                $errors[] = "Double-lesson capacity too low: requested {$totalRequestedDoubles} doubles, but layout supports {$combinePairs} combinable pairs/week.";
            }

            // Accumulate teacher demand (lesson slots only; activities are excluded from the cap)
            foreach ($allocs as $a) {
                $teacherDemand[(int) $a->staff_id] = ($teacherDemand[(int) $a->staff_id] ?? 0) + (int) $a->periods_per_week;
            }

            $out['streams'][] = [
                'stream_id' => $stream->id,
                'stream_name' => $stream->name,
                'template_id' => $layout->template_id,
                'lesson_slots' => $lessonSlots,
                'blocked_activities' => $blockedActivities,
                'teacher_assigned_activities' => $teacherActivities,
                'available_lesson_slots' => $availableLessonSlots,
                'required_lessons' => $requiredLessons,
                'total_demand_slots' => $totalDemandSlots,
                'combinable_pairs' => $combinePairs,
                'ok' => $errors === [],
                'errors' => $errors,
            ];

            if ($errors !== []) {
                $out['success'] = false;
            }
        }

        // Teacher load check
        $teacherRows = [];
        foreach ($teacherDemand as $staffId => $demand) {
            $cap = $defaultMax;
            $teacherRows[] = [
                'staff_id' => $staffId,
                'demand' => (int) $demand,
                'cap' => $cap,
                'ok' => (int) $demand <= $cap,
            ];
            if ((int) $demand > $cap) {
                $out['success'] = false;
            }
        }
        usort($teacherRows, fn ($a, $b) => ($b['demand'] - $b['cap']) <=> ($a['demand'] - $a['cap']));
        $out['teacher_load'] = $teacherRows;

        return $out;
    }

    protected function countCombinablePairs(Collection $periods): int
    {
        // For each day, count adjacent lesson slots where can_combine is true.
        $count = 0;
        $byDay = $periods->groupBy('day');
        foreach ($byDay as $day => $rows) {
            $rows = $rows->sortBy('sort_order')->values();
            for ($i = 0; $i < $rows->count() - 1; $i++) {
                $a = $rows[$i];
                $b = $rows[$i + 1];
                if ($a->slot_type === 'lesson' && $b->slot_type === 'lesson' && $a->can_combine) {
                    $count++;
                }
            }
        }
        return $count;
    }
}

