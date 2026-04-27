<?php

namespace App\Services\Timetable;

use App\Models\Academics\Stream;
use App\Models\Academics\TimetableGenerationRun;
use App\Models\Academics\TimetableGeneratedSlot;
use App\Models\Academics\TimetableLayoutPeriod;
use App\Models\Academics\TimetableStreamActivityRequirement;
use App\Models\Academics\TimetableStreamActivityTeacher;
use App\Models\Academics\TimetableStreamLayout;
use App\Models\Academics\TimetableStreamSubjectRequirement;
use App\Models\Academics\TimetableStreamSubjectTeacher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WholeSchoolGenerator
{
    /**
     * Generate a draft whole-school timetable run.
     *
     * Returns: TimetableGenerationRun
     */
    public function generateDraft(int $academicYearId, int $termId, array $streamIds = [], array $settings = [], ?int $createdByUserId = null): TimetableGenerationRun
    {
        return DB::transaction(function () use ($academicYearId, $termId, $streamIds, $settings, $createdByUserId) {
            $run = TimetableGenerationRun::create([
                'academic_year_id' => $academicYearId,
                'term_id' => $termId,
                'scope' => 'whole_school',
                'status' => 'draft',
                'settings' => $settings,
                'summary' => null,
                'created_by' => $createdByUserId,
            ]);

            $streams = Stream::query()
                ->when($streamIds !== [], fn ($q) => $q->whereIn('id', array_map('intval', $streamIds)))
                ->orderBy('name')
                ->get();

            // Global teacher schedule: key = day|layout_period_id => staff_id
            $teacherBusy = [];

            $unfilled = [];

            foreach ($streams as $stream) {
                $layout = TimetableStreamLayout::where('stream_id', $stream->id)
                    ->where('academic_year_id', $academicYearId)
                    ->where('term_id', $termId)
                    ->first();

                if (! $layout) {
                    $unfilled[] = ['stream_id' => $stream->id, 'reason' => 'No layout'];
                    continue;
                }

                $periods = TimetableLayoutPeriod::where('template_id', $layout->template_id)
                    ->orderBy('day')
                    ->orderBy('sort_order')
                    ->get();

                // Create base slots (breaks/activities)
                foreach ($periods as $p) {
                    TimetableGeneratedSlot::create([
                        'run_id' => $run->id,
                        'stream_id' => $stream->id,
                        'layout_period_id' => $p->id,
                        'day' => $p->day,
                        'slot_type' => $p->slot_type,
                        'label' => $p->label,
                        'subject_id' => null,
                        'staff_id' => null,
                        'room' => null,
                        'meta' => [
                            'start_time' => $p->start_time,
                            'end_time' => $p->end_time,
                            'sort_order' => $p->sort_order,
                        ],
                    ]);
                }

                // Apply stream activity requirements (blocked vs teacher-assigned) by placing into activity slots.
                $activitySlots = $periods->where('slot_type', 'activity')->values();
                $activityReqs = TimetableStreamActivityRequirement::where('stream_id', $stream->id)
                    ->where('academic_year_id', $academicYearId)
                    ->where('term_id', $termId)
                    ->get();

                $slotIndex = 0;
                foreach ($activityReqs as $ar) {
                    for ($i = 0; $i < (int) $ar->periods_per_week; $i++) {
                        $slot = $activitySlots[$slotIndex] ?? null;
                        if (! $slot) {
                            $unfilled[] = ['stream_id' => $stream->id, 'reason' => "Not enough activity slots for {$ar->name}"];
                            break;
                        }
                        $slotIndex++;

                        $staffId = null;
                        if ($ar->is_teacher_assigned) {
                            $teacherSplits = TimetableStreamActivityTeacher::where('activity_requirement_id', $ar->id)->get();
                            // naive pick: first teacher with remaining demand
                            foreach ($teacherSplits as $ts) {
                                $remaining = (int) $ts->periods_per_week - (int) ($run->summary['activity_assigned'][$ar->id][$ts->staff_id] ?? 0);
                                if ($remaining > 0) {
                                    $staffId = (int) $ts->staff_id;
                                    $run->summary['activity_assigned'][$ar->id][$ts->staff_id] = (int) ($run->summary['activity_assigned'][$ar->id][$ts->staff_id] ?? 0) + 1;
                                    break;
                                }
                            }
                        }

                        TimetableGeneratedSlot::where('run_id', $run->id)
                            ->where('stream_id', $stream->id)
                            ->where('layout_period_id', $slot->id)
                            ->update([
                                'slot_type' => 'activity',
                                'label' => $ar->name,
                                'staff_id' => $staffId,
                            ]);

                        if ($staffId) {
                            $busyKey = $slot->day.'|'.$slot->id;
                            $teacherBusy[$busyKey] = $staffId;
                        }
                    }
                }

                // Build lesson demands from split allocations
                $demands = TimetableStreamSubjectTeacher::where('stream_id', $stream->id)
                    ->where('academic_year_id', $academicYearId)
                    ->where('term_id', $termId)
                    ->get()
                    ->flatMap(function ($row) {
                        $items = [];
                        for ($i = 0; $i < (int) $row->periods_per_week; $i++) {
                            $items[] = [
                                'subject_id' => (int) $row->subject_id,
                                'staff_id' => (int) $row->staff_id,
                            ];
                        }
                        return $items;
                    })
                    ->values();

                // Prioritize subjects that request doubles
                $reqs = TimetableStreamSubjectRequirement::where('stream_id', $stream->id)
                    ->where('academic_year_id', $academicYearId)
                    ->where('term_id', $termId)
                    ->get()
                    ->keyBy('subject_id');

                $lessonSlots = $periods->where('slot_type', 'lesson')->groupBy('day');

                // Place doubles first (consecutive within day)
                $doublePlan = $this->extractDoublePlan($reqs);
                foreach ($doublePlan as $subjectId => $doubleCount) {
                    for ($i = 0; $i < $doubleCount; $i++) {
                        // find any demand matching subject (any teacher with remaining)
                        $idx = $demands->search(fn ($d) => $d['subject_id'] === (int) $subjectId);
                        if ($idx === false) {
                            $unfilled[] = ['stream_id' => $stream->id, 'reason' => "No demand for double subject {$subjectId}"];
                            break;
                        }
                        $demand = $demands[$idx];
                        // consume two occurrences for same subject+teacher if possible; else next teacher for same subject
                        $idx2 = $demands->search(fn ($d, $k) => $k !== $idx && $d['subject_id'] === (int) $subjectId && $d['staff_id'] === (int) $demand['staff_id']);
                        if ($idx2 === false) {
                            $idx2 = $demands->search(fn ($d, $k) => $k !== $idx && $d['subject_id'] === (int) $subjectId);
                        }
                        if ($idx2 === false) {
                            $unfilled[] = ['stream_id' => $stream->id, 'reason' => "Not enough demand units for double subject {$subjectId}"];
                            break;
                        }

                        $placed = $this->placeDouble($run->id, $stream->id, $lessonSlots, $teacherBusy, $demand['subject_id'], $demand['staff_id']);
                        if (! $placed) {
                            $unfilled[] = ['stream_id' => $stream->id, 'reason' => "Could not place double for subject {$subjectId}"];
                            break;
                        }

                        // remove consumed demands (remove higher index first)
                        $toRemove = [$idx, $idx2];
                        rsort($toRemove);
                        foreach ($toRemove as $rm) {
                            $demands->splice($rm, 1);
                        }
                    }
                }

                // Place remaining singles
                foreach ($demands as $demand) {
                    $placed = $this->placeSingle($run->id, $stream->id, $lessonSlots, $teacherBusy, $demand['subject_id'], $demand['staff_id']);
                    if (! $placed) {
                        $unfilled[] = ['stream_id' => $stream->id, 'reason' => "Could not place subject {$demand['subject_id']} for staff {$demand['staff_id']}"];
                    }
                }
            }

            $run->summary = [
                'unfilled' => $unfilled,
            ];
            $run->save();

            return $run;
        });
    }

    /**
     * Regenerate lesson slots for one stream inside an existing draft run.
     * Respects locks and teacher clashes across the whole run.
     */
    public function regenerateStream(TimetableGenerationRun $run, int $streamId): void
    {
        DB::transaction(function () use ($run, $streamId) {
            if ($run->status !== 'draft') {
                throw new \RuntimeException('Only draft runs can be regenerated.');
            }

            $stream = Stream::findOrFail($streamId);
            $layout = TimetableStreamLayout::where('stream_id', $stream->id)
                ->where('academic_year_id', $run->academic_year_id)
                ->where('term_id', $run->term_id)
                ->firstOrFail();

            $periods = TimetableLayoutPeriod::where('template_id', $layout->template_id)
                ->orderBy('day')->orderBy('sort_order')->get();

            // Build teacher busy map from OTHER streams + locked slots in this stream.
            $teacherBusy = [];
            $otherSlots = TimetableGeneratedSlot::where('run_id', $run->id)
                ->where('stream_id', '!=', $stream->id)
                ->whereNotNull('staff_id')
                ->get(['day', 'layout_period_id', 'staff_id']);
            foreach ($otherSlots as $s) {
                $teacherBusy[$s->day.'|'.$s->layout_period_id] = (int) $s->staff_id;
            }

            $locks = \App\Models\Academics\TimetableSlotLock::where('run_id', $run->id)
                ->where('stream_id', $stream->id)
                ->get();
            foreach ($locks as $l) {
                if ($l->locked_staff_id) {
                    $teacherBusy[$l->day.'|'.$l->layout_period_id] = (int) $l->locked_staff_id;
                }
            }

            $lockedIds = $locks->pluck('layout_period_id')->map(fn ($v) => (int) $v)->all();

            // Clear existing lesson assignments in this stream (except locked).
            TimetableGeneratedSlot::where('run_id', $run->id)
                ->where('stream_id', $stream->id)
                ->where('slot_type', 'lesson')
                ->whereNotIn('layout_period_id', $lockedIds === [] ? [-1] : $lockedIds)
                ->update([
                    'subject_id' => null,
                    'staff_id' => null,
                    'label' => null,
                ]);

            // Re-apply locks to generated slots (ensures slot values match lock).
            foreach ($locks as $l) {
                TimetableGeneratedSlot::where('run_id', $run->id)
                    ->where('stream_id', $stream->id)
                    ->where('layout_period_id', (int) $l->layout_period_id)
                    ->update([
                        'subject_id' => $l->locked_subject_id,
                        'staff_id' => $l->locked_staff_id,
                        'label' => $l->locked_label,
                        'room' => $l->locked_room,
                    ]);
            }

            // Fill remaining slots using the same algorithm as generateDraft (simplified: singles only + doubles request).
            $lessonSlots = $periods->where('slot_type', 'lesson')->filter(fn ($p) => !in_array((int) $p->id, $lockedIds, true))->groupBy('day');

            $demands = TimetableStreamSubjectTeacher::where('stream_id', $stream->id)
                ->where('academic_year_id', $run->academic_year_id)
                ->where('term_id', $run->term_id)
                ->get()
                ->flatMap(function ($row) {
                    $items = [];
                    for ($i = 0; $i < (int) $row->periods_per_week; $i++) {
                        $items[] = [
                            'subject_id' => (int) $row->subject_id,
                            'staff_id' => (int) $row->staff_id,
                        ];
                    }
                    return $items;
                })
                ->values();

            $reqs = TimetableStreamSubjectRequirement::where('stream_id', $stream->id)
                ->where('academic_year_id', $run->academic_year_id)
                ->where('term_id', $run->term_id)
                ->get()
                ->keyBy('subject_id');

            $doublePlan = $this->extractDoublePlan($reqs);
            foreach ($doublePlan as $subjectId => $doubleCount) {
                for ($i = 0; $i < $doubleCount; $i++) {
                    $idx = $demands->search(fn ($d) => $d['subject_id'] === (int) $subjectId);
                    if ($idx === false) {
                        break;
                    }
                    $demand = $demands[$idx];
                    $idx2 = $demands->search(fn ($d, $k) => $k !== $idx && $d['subject_id'] === (int) $subjectId);
                    if ($idx2 === false) {
                        break;
                    }
                    $placed = $this->placeDouble($run->id, $stream->id, $lessonSlots, $teacherBusy, $demand['subject_id'], $demand['staff_id']);
                    if (! $placed) {
                        break;
                    }
                    $toRemove = [$idx, $idx2];
                    rsort($toRemove);
                    foreach ($toRemove as $rm) {
                        $demands->splice($rm, 1);
                    }
                }
            }

            foreach ($demands as $demand) {
                $this->placeSingle($run->id, $stream->id, $lessonSlots, $teacherBusy, $demand['subject_id'], $demand['staff_id']);
            }
        });
    }

    protected function extractDoublePlan(Collection $reqsBySubjectId): array
    {
        $out = [];
        foreach ($reqsBySubjectId as $subjectId => $r) {
            if ($r->allow_double && (int) $r->max_doubles_per_week > 0) {
                $out[(int) $subjectId] = (int) $r->max_doubles_per_week;
            }
        }
        return $out;
    }

    protected function placeDouble(int $runId, int $streamId, Collection $lessonSlotsByDay, array &$teacherBusy, int $subjectId, int $staffId): bool
    {
        foreach ($lessonSlotsByDay as $day => $slots) {
            $slots = $slots->sortBy('sort_order')->values();
            for ($i = 0; $i < $slots->count() - 1; $i++) {
                $a = $slots[$i];
                $b = $slots[$i + 1];
                if (! $a->can_combine) {
                    continue;
                }
                if (! $this->isFreeLessonSlot($runId, $streamId, $a->id) || ! $this->isFreeLessonSlot($runId, $streamId, $b->id)) {
                    continue;
                }
                $k1 = $day.'|'.$a->id;
                $k2 = $day.'|'.$b->id;
                if (($teacherBusy[$k1] ?? null) || ($teacherBusy[$k2] ?? null)) {
                    continue;
                }

                $this->assignSlot($runId, $streamId, $a->id, $day, $subjectId, $staffId);
                $this->assignSlot($runId, $streamId, $b->id, $day, $subjectId, $staffId);
                $teacherBusy[$k1] = $staffId;
                $teacherBusy[$k2] = $staffId;
                return true;
            }
        }
        return false;
    }

    protected function placeSingle(int $runId, int $streamId, Collection $lessonSlotsByDay, array &$teacherBusy, int $subjectId, int $staffId): bool
    {
        foreach ($lessonSlotsByDay as $day => $slots) {
            $slots = $slots->sortBy('sort_order')->values();
            foreach ($slots as $slot) {
                if (! $this->isFreeLessonSlot($runId, $streamId, $slot->id)) {
                    continue;
                }
                $k = $day.'|'.$slot->id;
                if (($teacherBusy[$k] ?? null)) {
                    continue;
                }
                $this->assignSlot($runId, $streamId, $slot->id, $day, $subjectId, $staffId);
                $teacherBusy[$k] = $staffId;
                return true;
            }
        }
        return false;
    }

    protected function isFreeLessonSlot(int $runId, int $streamId, int $layoutPeriodId): bool
    {
        $row = TimetableGeneratedSlot::where('run_id', $runId)
            ->where('stream_id', $streamId)
            ->where('layout_period_id', $layoutPeriodId)
            ->first();
        if (! $row) {
            return false;
        }
        if ($row->slot_type !== 'lesson') {
            return false;
        }
        return $row->subject_id === null && $row->staff_id === null;
    }

    protected function assignSlot(int $runId, int $streamId, int $layoutPeriodId, string $day, int $subjectId, int $staffId): void
    {
        TimetableGeneratedSlot::where('run_id', $runId)
            ->where('stream_id', $streamId)
            ->where('layout_period_id', $layoutPeriodId)
            ->update([
                'slot_type' => 'lesson',
                'day' => $day,
                'subject_id' => $subjectId,
                'staff_id' => $staffId,
                'label' => null,
            ]);
    }
}

