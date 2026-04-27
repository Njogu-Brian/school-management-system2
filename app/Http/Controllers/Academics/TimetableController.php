<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Timetable;
use App\Models\Academics\Classroom;
use App\Models\Academics\ClassroomSubject;
use App\Models\Academics\ExtraCurricularActivity;
use App\Models\Staff;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Services\TimetableService;
use App\Services\TimetableOptimizationService;
use App\Services\Timetable\FeasibilityValidator;
use App\Services\Timetable\WholeSchoolGenerator;
use App\Models\Academics\TimetableGenerationRun;
use App\Models\Academics\TimetableGeneratedSlot;
use App\Models\Academics\TimetableLayoutPeriod;
use App\Models\Academics\TimetableSlotLock;
use App\Models\Academics\TimetableSlotOverride;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TimetableController extends Controller
{
    public function wholeSchool(Request $request)
    {
        $years = AcademicYear::orderByDesc('year')->get();
        $terms = Term::orderByDesc('start_date')->orderBy('name')->get();
        $selectedYear = $request->filled('academic_year_id') ? AcademicYear::find($request->academic_year_id) : $years->first();
        $selectedTerm = $request->filled('term_id') ? Term::find($request->term_id) : $terms->first();

        $streams = \App\Models\Academics\Stream::with('classroom')->orderBy('name')->get();

        return view('academics.timetable.whole_school', [
            'years' => $years,
            'terms' => $terms,
            'selectedYear' => $selectedYear,
            'selectedTerm' => $selectedTerm,
            'streams' => $streams,
            'report' => null,
        ]);
    }

    public function wholeSchoolFeasibility(Request $request, FeasibilityValidator $validator)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'stream_ids' => 'nullable|array',
            'stream_ids.*' => 'integer|exists:streams,id',
        ]);

        $report = $validator->validateWholeSchool(
            (int) $validated['academic_year_id'],
            (int) $validated['term_id'],
            array_map('intval', $validated['stream_ids'] ?? [])
        );

        $years = AcademicYear::orderByDesc('year')->get();
        $terms = Term::orderByDesc('start_date')->orderBy('name')->get();
        $selectedYear = AcademicYear::find($validated['academic_year_id']);
        $selectedTerm = Term::find($validated['term_id']);
        $streams = \App\Models\Academics\Stream::with('classroom')->orderBy('name')->get();

        return view('academics.timetable.whole_school', [
            'years' => $years,
            'terms' => $terms,
            'selectedYear' => $selectedYear,
            'selectedTerm' => $selectedTerm,
            'streams' => $streams,
            'report' => $report,
        ]);
    }

    public function wholeSchoolGenerate(Request $request, WholeSchoolGenerator $generator, FeasibilityValidator $validator)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'stream_ids' => 'nullable|array',
            'stream_ids.*' => 'integer|exists:streams,id',
        ]);

        $streamIds = array_map('intval', $validated['stream_ids'] ?? []);
        $report = $validator->validateWholeSchool((int) $validated['academic_year_id'], (int) $validated['term_id'], $streamIds);
        if (! $report['success']) {
            return back()->with('error', 'Feasibility report has issues. Fix them before generation.')->withInput();
        }

        $run = $generator->generateDraft(
            (int) $validated['academic_year_id'],
            (int) $validated['term_id'],
            $streamIds,
            ['double_policy' => 'same_day_consecutive'],
            Auth::id()
        );

        return redirect()
            ->route('academics.timetable.whole-school')
            ->with('success', 'Draft timetable generated. You can now publish it.')
            ->with('generated_run_id', $run->id);
    }

    public function wholeSchoolPublish(Request $request)
    {
        $validated = $request->validate([
            'run_id' => 'required|exists:timetable_generation_runs,id',
        ]);

        /** @var TimetableGenerationRun $run */
        $run = TimetableGenerationRun::findOrFail((int) $validated['run_id']);

        DB::transaction(function () use ($run) {
            // Archive any previously published run for same term/year
            TimetableGenerationRun::where('academic_year_id', $run->academic_year_id)
                ->where('term_id', $run->term_id)
                ->where('status', 'published')
                ->update(['status' => 'archived']);

            $run->status = 'published';
            $run->save();

            // Copy into existing `timetables` table (classroom-based). We map stream -> primary classroom_id.
            $slots = TimetableGeneratedSlot::with(['stream', 'layoutPeriod'])
                ->where('run_id', $run->id)
                ->get();

            $classroomIds = $slots->map(fn ($s) => (int) ($s->stream?->classroom_id ?? 0))->filter()->unique()->values()->all();
            if ($classroomIds !== []) {
                \App\Models\Academics\Timetable::whereIn('classroom_id', $classroomIds)
                    ->where('academic_year_id', $run->academic_year_id)
                    ->where('term_id', $run->term_id)
                    ->delete();
            }

            foreach ($slots as $s) {
                $classroomId = (int) ($s->stream?->classroom_id ?? 0);
                if (! $classroomId) {
                    continue;
                }
                $lp = $s->layoutPeriod;
                if (! $lp) {
                    continue;
                }
                if ($s->slot_type === 'break') {
                    continue; // `timetables` stores lessons; breaks are implied by layout in views
                }

                // period number is derived from sort_order (1..N for lesson slots, skipping breaks). For compatibility, store sort_order+1.
                $periodNum = (int) $lp->sort_order + 1;

                \App\Models\Academics\Timetable::create([
                    'classroom_id' => $classroomId,
                    'academic_year_id' => $run->academic_year_id,
                    'term_id' => $run->term_id,
                    'day' => $s->day,
                    'period' => $periodNum,
                    'start_time' => $lp->start_time,
                    'end_time' => $lp->end_time,
                    'subject_id' => $s->subject_id ?? 1,
                    'staff_id' => $s->staff_id,
                    'room' => $s->room,
                    'is_break' => false,
                    'meta' => [
                        'run_id' => $run->id,
                        'stream_id' => $s->stream_id,
                        'layout_period_id' => $s->layout_period_id,
                        'slot_type' => $s->slot_type,
                        'label' => $s->label,
                    ],
                ]);
            }
        });

        return back()->with('success', 'Published timetable and copied into timetables.');
    }

    public function wholeSchoolRunEditor(Request $request, TimetableGenerationRun $run)
    {
        $streamId = (int) ($request->input('stream_id') ?? 0);
        $stream = $streamId ? \App\Models\Academics\Stream::with('classroom')->find($streamId) : null;

        $streams = \App\Models\Academics\Stream::with('classroom')->orderBy('name')->get();

        $slots = collect();
        $periods = collect();
        if ($stream) {
            $layout = \App\Models\Academics\TimetableStreamLayout::where('stream_id', $stream->id)
                ->where('academic_year_id', $run->academic_year_id)
                ->where('term_id', $run->term_id)
                ->first();
            if ($layout) {
                $periods = TimetableLayoutPeriod::where('template_id', $layout->template_id)
                    ->orderBy('day')->orderBy('sort_order')
                    ->get();
            }
            $slots = TimetableGeneratedSlot::where('run_id', $run->id)
                ->where('stream_id', $stream->id)
                ->get()
                ->keyBy('layout_period_id');
        }

        $locks = $stream
            ? TimetableSlotLock::where('run_id', $run->id)->where('stream_id', $stream->id)->get()->keyBy('layout_period_id')
            : collect();

        $subjects = \App\Models\Academics\Subject::active()->orderBy('name')->get();
        $teachers = \App\Models\Staff::orderBy('first_name')->get();

        return view('academics.timetable.run_editor', compact('run', 'streams', 'stream', 'periods', 'slots', 'locks', 'subjects', 'teachers'));
    }

    public function wholeSchoolRunUpdateSlot(Request $request, TimetableGenerationRun $run)
    {
        $validated = $request->validate([
            'stream_id' => 'required|exists:streams,id',
            'layout_period_id' => 'required|exists:timetable_layout_periods,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'staff_id' => 'nullable|exists:staff,id',
            'reason' => 'nullable|string|max:255',
        ]);

        if ($run->status !== 'draft') {
            return back()->with('error', 'Only draft runs can be edited.');
        }

        $slot = TimetableGeneratedSlot::where('run_id', $run->id)
            ->where('stream_id', (int) $validated['stream_id'])
            ->where('layout_period_id', (int) $validated['layout_period_id'])
            ->firstOrFail();

        // Basic clash check: no teacher double-book at same day+layout_period across all streams
        if (! empty($validated['staff_id'])) {
            $conflict = TimetableGeneratedSlot::where('run_id', $run->id)
                ->where('day', $slot->day)
                ->where('layout_period_id', (int) $validated['layout_period_id'])
                ->where('staff_id', (int) $validated['staff_id'])
                ->where('stream_id', '!=', (int) $validated['stream_id'])
                ->exists();
            if ($conflict) {
                return back()->with('error', 'Teacher conflict: teacher already assigned in another stream at this time.');
            }
        }

        $slot->update([
            'subject_id' => $validated['subject_id'] ?? null,
            'staff_id' => $validated['staff_id'] ?? null,
            'slot_type' => 'lesson',
            'label' => null,
        ]);

        // Save an override record (weekly override, no effective_date) as audit trail
        TimetableSlotOverride::create([
            'run_id' => $run->id,
            'stream_id' => (int) $validated['stream_id'],
            'layout_period_id' => (int) $validated['layout_period_id'],
            'day' => $slot->day,
            'effective_date' => null,
            'slot_type' => 'lesson',
            'subject_id' => $validated['subject_id'] ?? null,
            'staff_id' => $validated['staff_id'] ?? null,
            'label' => null,
            'room' => null,
            'reason' => $validated['reason'] ?? 'manual edit',
            'created_by' => Auth::id(),
        ]);

        return back()->with('success', 'Slot updated.');
    }

    public function wholeSchoolRunToggleLock(Request $request, TimetableGenerationRun $run)
    {
        $validated = $request->validate([
            'stream_id' => 'required|exists:streams,id',
            'layout_period_id' => 'required|exists:timetable_layout_periods,id',
            'reason' => 'nullable|string|max:255',
        ]);

        if ($run->status !== 'draft') {
            return back()->with('error', 'Only draft runs can be edited.');
        }

        $slot = TimetableGeneratedSlot::where('run_id', $run->id)
            ->where('stream_id', (int) $validated['stream_id'])
            ->where('layout_period_id', (int) $validated['layout_period_id'])
            ->firstOrFail();

        $existing = TimetableSlotLock::where('run_id', $run->id)
            ->where('stream_id', (int) $validated['stream_id'])
            ->where('layout_period_id', (int) $validated['layout_period_id'])
            ->first();

        if ($existing) {
            $existing->delete();
            return back()->with('success', 'Slot unlocked.');
        }

        TimetableSlotLock::create([
            'run_id' => $run->id,
            'stream_id' => (int) $validated['stream_id'],
            'layout_period_id' => (int) $validated['layout_period_id'],
            'day' => $slot->day,
            'locked_subject_id' => $slot->subject_id,
            'locked_staff_id' => $slot->staff_id,
            'locked_label' => $slot->label,
            'locked_room' => $slot->room,
            'reason' => $validated['reason'] ?? 'locked',
            'locked_by' => Auth::id(),
        ]);

        return back()->with('success', 'Slot locked.');
    }

    public function wholeSchoolRunRegenerateStream(Request $request, TimetableGenerationRun $run, WholeSchoolGenerator $generator)
    {
        $validated = $request->validate([
            'stream_id' => 'required|exists:streams,id',
        ]);

        $generator->regenerateStream($run, (int) $validated['stream_id']);

        return back()->with('success', 'Stream regenerated (locks respected).');
    }

    public function wholeSchoolTeacherLoad(Request $request)
    {
        $years = AcademicYear::orderByDesc('year')->get();
        $terms = Term::orderByDesc('start_date')->orderBy('name')->get();
        $selectedYear = $request->filled('academic_year_id') ? AcademicYear::find($request->academic_year_id) : $years->first();
        $selectedTerm = $request->filled('term_id') ? Term::find($request->term_id) : $terms->first();

        $run = null;
        if ($selectedYear && $selectedTerm) {
            $run = TimetableGenerationRun::where('academic_year_id', $selectedYear->id)
                ->where('term_id', $selectedTerm->id)
                ->where('status', 'published')
                ->orderByDesc('id')
                ->first();
        }

        $rows = collect();
        if ($run) {
            $defaultMax = (int) setting('max_lessons_per_teacher_per_week', 40);
            $counts = TimetableGeneratedSlot::where('run_id', $run->id)
                ->where('slot_type', 'lesson')
                ->whereNotNull('staff_id')
                ->selectRaw('staff_id, count(*) as c')
                ->groupBy('staff_id')
                ->get();

            foreach ($counts as $c) {
                $staff = \App\Models\Staff::find((int) $c->staff_id);
                $cap = $defaultMax;
                $rows->push([
                    'staff_id' => (int) $c->staff_id,
                    'teacher_name' => $staff?->full_name ?? ('Staff #'.$c->staff_id),
                    'count' => (int) $c->c,
                    'cap' => $cap,
                    'ok' => (int) $c->c <= $cap,
                ]);
            }

            $rows = $rows->sortByDesc(fn ($r) => ($r['count'] - $r['cap']))->values();
        }

        return view('academics.timetable.teacher_load', [
            'years' => $years,
            'terms' => $terms,
            'selectedYear' => $selectedYear,
            'selectedTerm' => $selectedTerm,
            'run' => $run,
            'rows' => $rows,
        ]);
    }

    public function wholeSchoolSubstitutions(Request $request)
    {
        $streams = \App\Models\Academics\Stream::with('classroom')->orderBy('name')->get();
        $periods = TimetableLayoutPeriod::orderBy('day')->orderBy('sort_order')->get();
        $teachers = \App\Models\Staff::orderBy('first_name')->get();
        $subjects = \App\Models\Academics\Subject::active()->orderBy('name')->get();

        $overrides = TimetableSlotOverride::query()
            ->whereNotNull('effective_date')
            ->orderByDesc('effective_date')
            ->limit(200)
            ->get();

        return view('academics.timetable.substitutions', compact('streams', 'periods', 'teachers', 'subjects', 'overrides'));
    }

    public function wholeSchoolSubstitutionsStore(Request $request)
    {
        $validated = $request->validate([
            'stream_id' => 'required|exists:streams,id',
            'layout_period_id' => 'required|exists:timetable_layout_periods,id',
            'effective_date' => 'required|date',
            'subject_id' => 'nullable|exists:subjects,id',
            'staff_id' => 'nullable|exists:staff,id',
            'reason' => 'nullable|string|max:255',
        ]);

        $period = TimetableLayoutPeriod::findOrFail((int) $validated['layout_period_id']);

        TimetableSlotOverride::create([
            'run_id' => null,
            'stream_id' => (int) $validated['stream_id'],
            'layout_period_id' => (int) $validated['layout_period_id'],
            'day' => $period->day,
            'effective_date' => $validated['effective_date'],
            'slot_type' => 'lesson',
            'subject_id' => $validated['subject_id'] ?? null,
            'staff_id' => $validated['staff_id'] ?? null,
            'label' => null,
            'room' => null,
            'reason' => $validated['reason'] ?? 'substitution',
            'created_by' => Auth::id(),
        ]);

        return back()->with('success', 'Substitution saved. (Note: applying substitutions to published timetables is the next step.)');
    }

    public function wholeSchoolReplicate(Request $request)
    {
        $years = AcademicYear::orderByDesc('year')->get();
        $terms = Term::orderByDesc('start_date')->orderBy('name')->get();
        $selectedYear = $request->filled('academic_year_id') ? AcademicYear::find($request->academic_year_id) : $years->first();
        $selectedTerm = $request->filled('term_id') ? Term::find($request->term_id) : $terms->first();
        $streams = \App\Models\Academics\Stream::with('classroom')->orderBy('name')->get();

        return view('academics.timetable.replicate', compact('years', 'terms', 'selectedYear', 'selectedTerm', 'streams'));
    }

    public function wholeSchoolReplicateStore(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'source_stream_id' => 'required|exists:streams,id',
            'target_stream_ids' => 'required|array|min:1',
            'target_stream_ids.*' => 'integer|exists:streams,id',
        ]);

        $yearId = (int) $validated['academic_year_id'];
        $termId = (int) $validated['term_id'];
        $sourceId = (int) $validated['source_stream_id'];
        $targets = array_values(array_unique(array_map('intval', $validated['target_stream_ids'])));

        DB::transaction(function () use ($yearId, $termId, $sourceId, $targets) {
            $srcLayout = \App\Models\Academics\TimetableStreamLayout::where('stream_id', $sourceId)
                ->where('academic_year_id', $yearId)
                ->where('term_id', $termId)
                ->first();

            foreach ($targets as $tid) {
                if ($tid === $sourceId) continue;

                if ($srcLayout) {
                    \App\Models\Academics\TimetableStreamLayout::updateOrCreate(
                        ['stream_id' => $tid, 'academic_year_id' => $yearId, 'term_id' => $termId],
                        ['template_id' => $srcLayout->template_id, 'overrides' => $srcLayout->overrides]
                    );
                }

                // Requirements
                $reqs = \App\Models\Academics\TimetableStreamSubjectRequirement::where('stream_id', $sourceId)
                    ->where('academic_year_id', $yearId)
                    ->where('term_id', $termId)
                    ->get();
                foreach ($reqs as $r) {
                    \App\Models\Academics\TimetableStreamSubjectRequirement::updateOrCreate(
                        ['stream_id' => $tid, 'academic_year_id' => $yearId, 'term_id' => $termId, 'subject_id' => $r->subject_id],
                        [
                            'periods_per_week' => $r->periods_per_week,
                            'allow_double' => $r->allow_double,
                            'max_doubles_per_week' => $r->max_doubles_per_week,
                            'meta' => $r->meta,
                        ]
                    );
                }

                // Teacher splits
                $splits = \App\Models\Academics\TimetableStreamSubjectTeacher::where('stream_id', $sourceId)
                    ->where('academic_year_id', $yearId)
                    ->where('term_id', $termId)
                    ->get();
                foreach ($splits as $s) {
                    \App\Models\Academics\TimetableStreamSubjectTeacher::updateOrCreate(
                        [
                            'stream_id' => $tid,
                            'academic_year_id' => $yearId,
                            'term_id' => $termId,
                            'subject_id' => $s->subject_id,
                            'staff_id' => $s->staff_id,
                        ],
                        ['periods_per_week' => $s->periods_per_week, 'meta' => $s->meta]
                    );
                }

                // Activities
                $actReqs = \App\Models\Academics\TimetableStreamActivityRequirement::where('stream_id', $sourceId)
                    ->where('academic_year_id', $yearId)
                    ->where('term_id', $termId)
                    ->get();

                foreach ($actReqs as $ar) {
                    $new = \App\Models\Academics\TimetableStreamActivityRequirement::updateOrCreate(
                        ['stream_id' => $tid, 'academic_year_id' => $yearId, 'term_id' => $termId, 'name' => $ar->name],
                        [
                            'periods_per_week' => $ar->periods_per_week,
                            'is_teacher_assigned' => $ar->is_teacher_assigned,
                            'meta' => $ar->meta,
                        ]
                    );

                    if ($ar->is_teacher_assigned) {
                        $tRows = \App\Models\Academics\TimetableStreamActivityTeacher::where('activity_requirement_id', $ar->id)->get();
                        foreach ($tRows as $tr) {
                            \App\Models\Academics\TimetableStreamActivityTeacher::updateOrCreate(
                                ['activity_requirement_id' => $new->id, 'staff_id' => $tr->staff_id],
                                ['periods_per_week' => $tr->periods_per_week, 'meta' => $tr->meta]
                            );
                        }
                    }
                }
            }
        });

        return back()->with('success', 'Replication completed.');
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Filter classrooms based on user role
        if (($user->hasRole('Teacher') || $user->hasRole('teacher')) && !is_supervisor() && !$user->hasRole('Senior Teacher')) {
            $assignedClassroomIds = $user->getAssignedClassroomIds();
            if (!empty($assignedClassroomIds)) {
                $classrooms = Classroom::whereIn('id', $assignedClassroomIds)->orderBy('name')->get();
            } else {
                $classrooms = collect();
            }
            $teachers = collect();
        } elseif ($user->hasRole('Senior Teacher')) {
            $allClassroomIds = array_unique(array_merge(
                $user->getAssignedClassroomIds(),
                $user->getSupervisedClassroomIds()
            ));
            if (!empty($allClassroomIds)) {
                $classrooms = Classroom::whereIn('id', $allClassroomIds)->orderBy('name')->get();
            } else {
                $classrooms = collect();
            }
            $teachers = collect();
        } elseif (is_supervisor() && !$user->hasAnyRole(['Admin', 'Super Admin'])) {
            // Supervisors can see their subordinates' classrooms
            $subordinateClassroomIds = get_subordinate_classroom_ids();
            $ownClassroomIds = $user->getAssignedClassroomIds();
            $allClassroomIds = array_unique(array_merge($ownClassroomIds, $subordinateClassroomIds));
            
            if (!empty($allClassroomIds)) {
                $classrooms = Classroom::whereIn('id', $allClassroomIds)->orderBy('name')->get();
            } else {
                $classrooms = collect();
            }
            // Supervisors can see their subordinates as teachers
            $subordinateIds = get_subordinate_staff_ids();
            $teachers = Staff::whereIn('id', $subordinateIds)
                ->whereHas('user.roles', fn($q) => $q->whereIn('name', ['Teacher', 'teacher']))
                ->get();
        } else {
            $classrooms = Classroom::orderBy('name')->get();
            $teachers = Staff::whereHas('user.roles', fn($q) => $q->whereIn('name', ['Teacher', 'teacher']))->get();
        }
        
        $years = AcademicYear::orderByDesc('year')->get();
        $terms = Term::orderByDesc('start_date')->orderBy('name')->get();

        $selectedClassroom = $request->filled('classroom_id') ? Classroom::find($request->classroom_id) : null;
        $selectedTeacher = $request->filled('teacher_id') ? Staff::find($request->teacher_id) : null;
        $selectedYear = $request->filled('academic_year_id') ? AcademicYear::find($request->academic_year_id) : $years->first();
        $selectedTerm = $request->filled('term_id') ? Term::find($request->term_id) : $terms->first();

        $timetable = null;
        $savedTimetable = null;
        $conflicts = [];
        
        if ($selectedClassroom && $selectedYear && $selectedTerm) {
            // Check if saved timetable exists
            $savedTimetable = Timetable::where('classroom_id', $selectedClassroom->id)
                ->where('academic_year_id', $selectedYear->id)
                ->where('term_id', $selectedTerm->id)
                ->get()
                ->groupBy(['day', 'period']);
            
            if ($savedTimetable->isEmpty()) {
                // Generate new timetable
                $timetable = TimetableService::generateForClassroom(
                    $selectedClassroom->id,
                    $selectedYear->id,
                    $selectedTerm->id
                );
                $conflicts = TimetableService::checkConflicts($timetable);
            }
        }

        // Get extra-curricular activities
        $activities = [];
        if ($selectedYear && $selectedTerm) {
            $activities = ExtraCurricularActivity::where('academic_year_id', $selectedYear->id)
                ->where('term_id', $selectedTerm->id)
                ->where('is_active', true)
                ->get()
                ->groupBy('day');
        }

        return view('academics.timetable.index', compact(
            'classrooms', 'teachers', 'years', 'terms',
            'selectedClassroom', 'selectedTeacher', 'selectedYear', 'selectedTerm',
            'timetable', 'savedTimetable', 'conflicts', 'activities'
        ));
    }

    public function classroom(Classroom $classroom, Request $request)
    {
        $yearId = $request->get('academic_year_id') ?? AcademicYear::orderByDesc('year')->first()?->id;
        $termId = $request->get('term_id') ?? Term::orderByDesc('start_date')->orderBy('name')->first()?->id;

        if (!$yearId || !$termId) {
            return back()->with('error', 'Please select academic year and term.');
        }

        // Get saved timetable or generate new
        $savedTimetable = Timetable::where('classroom_id', $classroom->id)
            ->where('academic_year_id', $yearId)
            ->where('term_id', $termId)
            ->with(['subject', 'teacher'])
            ->get()
            ->groupBy(['day', 'period']);

        $timetable = null;
        $conflicts = [];
        
        if ($savedTimetable->isEmpty()) {
            // Use optimized generation by default
            $timetable = TimetableOptimizationService::generateOptimized($classroom->id, $yearId, $termId);
            $conflicts = $timetable['conflicts'] ?? [];
        }

        // Get subject assignments with lessons_per_week
        $assignments = ClassroomSubject::where('classroom_id', $classroom->id)
            ->where(function($q) use ($yearId, $termId) {
                $q->where(function($q2) use ($yearId, $termId) {
                    $q2->where('academic_year_id', $yearId)
                       ->where('term_id', $termId);
                })
                ->orWhere(function($q2) {
                    $q2->whereNull('academic_year_id')
                       ->whereNull('term_id');
                });
            })
            ->with(['subject', 'teacher'])
            ->get();

        // Get extra-curricular activities
        $activities = ExtraCurricularActivity::where('academic_year_id', $yearId)
            ->where('term_id', $termId)
            ->where('is_active', true)
            ->where(function($q) use ($classroom) {
                $q->whereJsonContains('classroom_ids', $classroom->id)
                  ->orWhereNull('classroom_ids');
            })
            ->get()
            ->groupBy('day');

        $year = AcademicYear::find($yearId);
        $term = Term::find($termId);

        return view('academics.timetable.classroom', compact(
            'classroom', 'year', 'term', 'timetable', 'savedTimetable', 
            'conflicts', 'assignments', 'activities'
        ));
    }

    public function edit(Classroom $classroom, Request $request)
    {
        $yearId = $request->get('academic_year_id') ?? AcademicYear::orderByDesc('year')->first()?->id;
        $termId = $request->get('term_id') ?? Term::orderByDesc('start_date')->orderBy('name')->first()?->id;

        if (!$yearId || !$termId) {
            return back()->with('error', 'Please select academic year and term.');
        }

        $savedTimetable = Timetable::where('classroom_id', $classroom->id)
            ->where('academic_year_id', $yearId)
            ->where('term_id', $termId)
            ->with(['subject', 'teacher'])
            ->get();

        $assignments = ClassroomSubject::where('classroom_id', $classroom->id)
            ->where(function($q) use ($yearId, $termId) {
                $q->where(function($q2) use ($yearId, $termId) {
                    $q2->where('academic_year_id', $yearId)
                       ->where('term_id', $termId);
                })
                ->orWhere(function($q2) {
                    $q2->whereNull('academic_year_id')
                       ->whereNull('term_id');
                });
            })
            ->with(['subject', 'teacher'])
            ->get();

        $year = AcademicYear::find($yearId);
        $term = Term::find($termId);

        // Default time slots
        $timeSlots = [
            ['start' => '08:00', 'end' => '08:40', 'period' => 1],
            ['start' => '08:40', 'end' => '09:20', 'period' => 2],
            ['start' => '09:20', 'end' => '10:00', 'period' => 3],
            ['start' => '10:00', 'end' => '10:20', 'period' => 'Break'],
            ['start' => '10:20', 'end' => '11:00', 'period' => 4],
            ['start' => '11:00', 'end' => '11:40', 'period' => 5],
            ['start' => '11:40', 'end' => '12:20', 'period' => 6],
            ['start' => '12:20', 'end' => '13:00', 'period' => 'Lunch'],
            ['start' => '13:00', 'end' => '13:40', 'period' => 7],
            ['start' => '13:40', 'end' => '14:20', 'period' => 8],
        ];

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        return view('academics.timetable.edit', compact(
            'classroom', 'year', 'term', 'savedTimetable', 
            'assignments', 'timeSlots', 'days'
        ));
    }

    public function teacher(Staff $teacher, Request $request)
    {
        $yearId = $request->get('academic_year_id') ?? AcademicYear::orderByDesc('year')->first()?->id;
        $termId = $request->get('term_id') ?? Term::orderBy('name')->first()?->id;

        if (!$yearId || !$termId) {
            return back()->with('error', 'Please select academic year and term.');
        }

        $timetable = TimetableService::generateForTeacher($teacher->id, $yearId, $termId);

        return view('academics.timetable.teacher', compact('timetable', 'teacher'));
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'use_optimization' => 'nullable|boolean',
        ]);

        // Use AI optimization if requested
        if ($request->filled('use_optimization') && $request->use_optimization) {
            $timetable = TimetableOptimizationService::generateOptimized(
                $validated['classroom_id'],
                $validated['academic_year_id'],
                $validated['term_id']
            );
        } else {
            $timetable = TimetableService::generateForClassroom(
                $validated['classroom_id'],
                $validated['academic_year_id'],
                $validated['term_id']
            );
        }

        return view('academics.timetable.preview', compact('timetable'));
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'timetable' => 'required|array',
        ]);

        // Check if user has access to this classroom
        if (!Auth::user()->hasAnyRole(['Admin', 'Super Admin'])) {
            $staff = Auth::user()->staff;
            if ($staff) {
                $hasOwnAccess = DB::table('classroom_subjects')
                    ->where('staff_id', $staff->id)
                    ->where('classroom_id', $validated['classroom_id'])
                    ->exists();
                
                // Supervisors can create timetables for their subordinates' classrooms
                $hasSubordinateAccess = false;
                if (is_supervisor()) {
                    $subordinateClassroomIds = get_subordinate_classroom_ids();
                    $hasSubordinateAccess = in_array($validated['classroom_id'], $subordinateClassroomIds);
                }
                
                if (!$hasOwnAccess && !$hasSubordinateAccess) {
                    return back()
                        ->withInput()
                        ->with('error', 'You do not have access to create timetables for this classroom.');
                }
            }
        }

        // Delete existing timetable
        Timetable::where('classroom_id', $validated['classroom_id'])
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('term_id', $validated['term_id'])
            ->delete();

        // Check for conflicts and teacher lesson limits
        $conflicts = [];
        $teacherCounts = [];
        $defaultMaxLessons = (int) \App\Models\Setting::where('key', 'max_lessons_per_teacher_per_week')->value('value') ?? 40;
        
        foreach ($validated['timetable'] as $day => $periods) {
            foreach ($periods as $period => $data) {
                if (isset($data['subject_id']) && $data['subject_id'] && isset($data['teacher_id']) && $data['teacher_id']) {
                    $teacherId = $data['teacher_id'];
                    
                    // Initialize teacher count
                    if (!isset($teacherCounts[$teacherId])) {
                        $teacher = Staff::find($teacherId);
                        $maxLessons = $defaultMaxLessons;
                        $teacherCounts[$teacherId] = [
                            'teacher' => $teacher,
                            'current' => 0,
                            'max' => $maxLessons,
                        ];
                    }
                    
                    // Count this lesson
                    $teacherCounts[$teacherId]['current']++;
                    
                    // Check if teacher has another class at same time
                    $existing = Timetable::where('staff_id', $teacherId)
                        ->where('academic_year_id', $validated['academic_year_id'])
                        ->where('term_id', $validated['term_id'])
                        ->where('day', $day)
                        ->where('period', $period)
                        ->where('classroom_id', '!=', $validated['classroom_id'])
                        ->exists();
                    
                    if ($existing) {
                        $conflicts[] = [
                            'type' => 'teacher_conflict',
                            'day' => $day,
                            'period' => $period,
                            'teacher_id' => $teacherId,
                            'message' => 'Teacher has another class at this time',
                        ];
                    }
                }
            }
        }
        
        // Check teacher lesson limits
        foreach ($teacherCounts as $teacherId => $count) {
            if ($count['current'] > $count['max']) {
                $conflicts[] = [
                    'type' => 'teacher_limit_exceeded',
                    'teacher_id' => $teacherId,
                    'teacher_name' => $count['teacher']->full_name ?? 'Unknown',
                    'current' => $count['current'],
                    'max' => $count['max'],
                    'message' => "{$count['teacher']->full_name} exceeds maximum lessons ({$count['current']}/{$count['max']})",
                ];
            }
        }

        if (!empty($conflicts)) {
            return back()
                ->with('error', 'Conflicts detected. Please resolve before saving.')
                ->with('conflicts', $conflicts)
                ->with('teacher_counts', $teacherCounts);
        }

        // Save new timetable
        foreach ($validated['timetable'] as $day => $periods) {
            foreach ($periods as $period => $data) {
                if (isset($data['subject_id']) && $data['subject_id'] && !in_array($period, ['Break', 'Lunch'])) {
                    Timetable::create([
                        'classroom_id' => $validated['classroom_id'],
                        'academic_year_id' => $validated['academic_year_id'],
                        'term_id' => $validated['term_id'],
                        'day' => $day,
                        'period' => is_numeric($period) ? $period : 0,
                        'start_time' => $data['start'] ?? '08:00',
                        'end_time' => $data['end'] ?? '08:40',
                        'subject_id' => $data['subject_id'],
                        'staff_id' => $data['teacher_id'] ?? null,
                        'room' => $data['room'] ?? null,
                        'is_break' => in_array($period, ['Break', 'Lunch']),
                    ]);
                }
            }
        }

        return redirect()
            ->route('academics.timetable.classroom', $validated['classroom_id'])
            ->with('success', 'Timetable saved successfully.');
    }

    public function duplicate(Request $request)
    {
        $validated = $request->validate([
            'source_classroom_id' => 'required|exists:classrooms,id',
            'target_classroom_ids' => 'required|array',
            'target_classroom_ids.*' => 'exists:classrooms,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
        ]);

        $sourceTimetable = Timetable::where('classroom_id', $validated['source_classroom_id'])
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('term_id', $validated['term_id'])
            ->get();

        if ($sourceTimetable->isEmpty()) {
            return back()->with('error', 'Source timetable is empty.');
        }

        foreach ($validated['target_classroom_ids'] as $targetClassroomId) {
            // Delete existing timetable for target
            Timetable::where('classroom_id', $targetClassroomId)
                ->where('academic_year_id', $validated['academic_year_id'])
                ->where('term_id', $validated['term_id'])
                ->delete();

            // Duplicate timetable
            foreach ($sourceTimetable as $entry) {
                Timetable::create([
                    'classroom_id' => $targetClassroomId,
                    'academic_year_id' => $entry->academic_year_id,
                    'term_id' => $entry->term_id,
                    'day' => $entry->day,
                    'period' => $entry->period,
                    'start_time' => $entry->start_time,
                    'end_time' => $entry->end_time,
                    'subject_id' => $entry->subject_id,
                    'staff_id' => $entry->staff_id,
                    'room' => $entry->room,
                    'is_break' => $entry->is_break,
                ]);
            }
        }

        return back()->with('success', 'Timetable duplicated successfully.');
    }

    public function updatePeriod(Request $request, Timetable $timetable)
    {
        $validated = $request->validate([
            'subject_id' => 'nullable|exists:subjects,id',
            'staff_id' => 'nullable|exists:staff,id',
            'start_time' => 'required|string',
            'end_time' => 'required|string',
            'room' => 'nullable|string|max:50',
        ]);

        // Check for teacher conflict
        if ($validated['staff_id']) {
            $conflict = Timetable::where('staff_id', $validated['staff_id'])
                ->where('academic_year_id', $timetable->academic_year_id)
                ->where('term_id', $timetable->term_id)
                ->where('day', $timetable->day)
                ->where('period', $timetable->period)
                ->where('id', '!=', $timetable->id)
                ->exists();

            if ($conflict) {
                return back()->with('error', 'Teacher has another class at this time.');
            }
            
            // Check teacher lesson limit
            $teacher = Staff::find($validated['staff_id']);
            $defaultMaxLessons = (int) \App\Models\Setting::where('key', 'max_lessons_per_teacher_per_week')->value('value') ?? 40;
            $maxLessons = $defaultMaxLessons;
            
            $currentLessons = Timetable::where('staff_id', $validated['staff_id'])
                ->where('academic_year_id', $timetable->academic_year_id)
                ->where('term_id', $timetable->term_id)
                ->where('id', '!=', $timetable->id)
                ->count();
            
            // If this is a new assignment (not just updating existing), check limit
            if (!$timetable->staff_id || $timetable->staff_id != $validated['staff_id']) {
                if ($currentLessons >= $maxLessons) {
                    return back()->with('error', "Teacher {$teacher->full_name} has reached maximum lessons per week ({$maxLessons}).");
                }
            }
        }

        $timetable->update($validated);

        return back()->with('success', 'Period updated successfully.');
    }
    
    /**
     * Check conflicts in real-time (AJAX)
     */
    public function checkConflicts(Request $request)
    {
        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'timetable' => 'required|array',
        ]);
        
        $conflicts = [];
        $teacherCounts = [];
        $defaultMaxLessons = (int) \App\Models\Setting::where('key', 'max_lessons_per_teacher_per_week')->value('value') ?? 40;
        
        foreach ($validated['timetable'] as $day => $periods) {
            foreach ($periods as $period => $data) {
                if (isset($data['teacher_id']) && $data['teacher_id']) {
                    $teacherId = $data['teacher_id'];
                    
                    if (!isset($teacherCounts[$teacherId])) {
                        $teacher = Staff::find($teacherId);
                        $maxLessons = $defaultMaxLessons;
                        $teacherCounts[$teacherId] = [
                            'teacher_name' => $teacher->full_name ?? 'Unknown',
                            'current' => 0,
                            'max' => $maxLessons,
                        ];
                    }
                    
                    $teacherCounts[$teacherId]['current']++;
                }
            }
        }
        
        // Check limits
        foreach ($teacherCounts as $teacherId => $count) {
            if ($count['current'] > $count['max']) {
                $conflicts[] = [
                    'type' => 'teacher_limit',
                    'teacher_id' => $teacherId,
                    'message' => "{$count['teacher_name']} exceeds limit ({$count['current']}/{$count['max']})",
                ];
            }
        }
        
        return response()->json([
            'conflicts' => $conflicts,
            'teacher_counts' => $teacherCounts,
        ]);
    }
}
