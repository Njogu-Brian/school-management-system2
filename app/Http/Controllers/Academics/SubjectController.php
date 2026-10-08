<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Subject;
use App\Models\Academics\Classroom;
use App\Models\Academics\ClassroomSubject;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\Staff;
use App\Services\ClassroomSubjectSlotService;
use App\Services\CbcRationalizedSubjectSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:subjects.view')->only(['index', 'show']);
        $this->middleware('permission:subjects.create')->only(['create', 'store', 'generateCBCSubjects', 'assignToClassrooms']);
        $this->middleware('permission:subjects.edit')->only([
            'edit',
            'update',
            'updateLessonsPerWeek',
            'teacherAssignments',
            'saveTeacherAssignments',
        ]);
        $this->middleware('permission:subjects.delete')->only(['destroy', 'restore', 'forceDestroy']);
    }

    public function index(Request $request)
    {
        $query = Subject::query()
            ->with([
                'classroomSubjects.classroom',
                'classroomSubjects.stream',
                'teachers'
            ])
            ->withCount(['classroomSubjects', 'teachers']);

        if ($request->input('archived') === '1') {
            $query->onlyTrashed();
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('learning_area', 'like', "%{$search}%");
            });
        }

        // Filters
        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        if ($request->filled('is_optional')) {
            $query->where('is_optional', $request->is_optional === '1');
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active === '1');
        }

        $subjects = $query->orderBy('name')->paginate(20)->withQueryString();

        $levels = Subject::withTrashed()->distinct()->whereNotNull('level')->pluck('level')->sort();

        return view('academics.subjects.index', compact('subjects', 'levels'));
    }

    public function create()
    {
        $classrooms = Classroom::with(['primaryStreams', 'streams'])->orderBy('name')->get();
        $teachers = Staff::where('status', 'active')->whereHas('user.roles', fn($q) => $q->whereIn('name', ['Teacher', 'teacher', 'Senior Teacher', 'Supervisor', 'supervisor']))->get();
        $years = \App\Support\AcademicContext::years();
        $terms = \App\Support\AcademicContext::allTermsForSelect();
        $streamsByClassroom = $classrooms->mapWithKeys(function (Classroom $classroom) {
            return [
                $classroom->id => $classroom->allStreams()->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                ])->values(),
            ];
        });

        return view('academics.subjects.create', compact('classrooms', 'teachers', 'years', 'terms', 'streamsByClassroom'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:subjects,code',
            'name' => 'required|string|max:255',
            'learning_area' => 'nullable|string|max:255',
            'level' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'is_optional' => 'boolean',
            'classroom_assignments' => 'nullable|array',
            'classroom_assignments.*.classroom_id' => 'nullable|exists:classrooms,id',
            'classroom_assignments.*.stream_id' => 'nullable|exists:streams,id',
            'classroom_assignments.*.staff_id' => 'nullable|exists:staff,id',
            'classroom_assignments.*.academic_year_id' => 'nullable|exists:academic_years,id',
            'classroom_assignments.*.term_id' => 'nullable|exists:terms,id',
            'classroom_assignments.*.is_compulsory' => 'nullable|boolean',
        ]);

        $subject = Subject::create($validated);

        if ($request->filled('classroom_assignments')) {
            $this->syncClassroomAssignments($subject, $request->classroom_assignments);
        }

        return redirect()
            ->route('academics.subjects.index')
            ->with('success', 'Subject created successfully.');
    }

    public function show(Subject $subject)
    {
        $subject->load([
            'classrooms',
            'teachers',
            'classroomSubjects.classroom',
            'classroomSubjects.stream',
            'classroomSubjects.teacher'
        ]);

        return view('academics.subjects.show', compact('subject'));
    }

    public function edit(Subject $subject)
    {
        $classrooms = Classroom::with(['primaryStreams', 'streams'])->orderBy('name')->get();
        $slotService = app(ClassroomSubjectSlotService::class);
        $classrooms->each(fn (Classroom $classroom) => $slotService->splitWholeClassSlots($classroom));
        $classroomAssignments = $subject->classroomSubjects()
            ->with(['classroom', 'stream', 'teacher'])
            ->get()
            ->sortBy(fn ($assignment) => sprintf(
                '%s %s',
                $assignment->classroom->name ?? '',
                $assignment->stream->name ?? ''
            ))
            ->values();
        $teachers = Staff::where('status', 'active')
            ->whereHas('user.roles', fn($q) => $q->whereIn('name', ['Teacher', 'teacher', 'Senior Teacher', 'Supervisor', 'supervisor']))
            ->get();
        $years = \App\Support\AcademicContext::years();
        $terms = \App\Support\AcademicContext::allTermsForSelect();
        $streamsByClassroom = $classrooms->mapWithKeys(function (Classroom $classroom) {
            return [
                $classroom->id => $classroom->allStreams()->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                ])->values(),
            ];
        });

        return view('academics.subjects.edit', compact(
            'subject',
            'classrooms',
            'teachers',
            'years',
            'terms',
            'classroomAssignments',
            'streamsByClassroom'
        ));
    }

    public function update(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:subjects,code,' . $subject->id,
            'name' => 'required|string|max:255',
            'learning_area' => 'nullable|string|max:255',
            'level' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'is_optional' => 'boolean',
            'classroom_assignments' => 'nullable|array',
            'classroom_assignments.*.id' => 'nullable|integer',
            'classroom_assignments.*.classroom_id' => 'nullable|exists:classrooms,id',
            'classroom_assignments.*.stream_id' => 'nullable|exists:streams,id',
            'classroom_assignments.*.staff_id' => 'nullable|exists:staff,id',
            'classroom_assignments.*.academic_year_id' => 'nullable|exists:academic_years,id',
            'classroom_assignments.*.term_id' => 'nullable|exists:terms,id',
            'classroom_assignments.*.is_compulsory' => 'nullable|boolean',
        ]);

        $subject->update($validated);

        if ($request->has('classroom_assignments')) {
            $this->syncClassroomAssignments($subject, $request->input('classroom_assignments', []), true);
        }

        return redirect()
            ->route('academics.subjects.index')
            ->with('success', 'Subject updated successfully.');
    }

    /**
     * Archive (soft-delete) a subject. Never hard-deletes rows that have marks.
     */
    public function destroy(Subject $subject)
    {
        $subject->update(['is_active' => false]);
        $subject->delete();

        return redirect()
            ->route('academics.subjects.index')
            ->with('success', 'Subject archived. Classroom assignments and marks are preserved. You can restore it from Archived subjects.');
    }

    public function restore(int $id)
    {
        $subject = Subject::onlyTrashed()->findOrFail($id);
        $subject->restore();
        $subject->update(['is_active' => true]);

        return redirect()
            ->route('academics.subjects.index')
            ->with('success', 'Subject restored successfully.');
    }

    /**
     * Permanent delete — blocked when any exam marks exist for this subject.
     */
    public function forceDestroy(int $id)
    {
        $subject = Subject::withTrashed()->findOrFail($id);

        if ($subject->hasEnteredMarks()) {
            return redirect()
                ->route('academics.subjects.index', ['archived' => 1])
                ->with('error', 'Cannot permanently delete this subject because exam marks have been entered. Keep it archived instead.');
        }

        DB::transaction(function () use ($subject) {
            $subject->classroomSubjects()->delete();
            $subject->teachers()->detach();
            $subject->forceDelete();
        });

        return redirect()
            ->route('academics.subjects.index', ['archived' => 1])
            ->with('success', 'Subject permanently deleted.');
    }

    /**
     * Persist classroom/stream/teacher assignment rows.
     * Empty stream_id + classroom with streams → one row per stream (same teacher).
     *
     * @param  array<int, array<string, mixed>>  $assignments
     */
    protected function syncClassroomAssignments(Subject $subject, array $assignments, bool $replaceMissing = false): void
    {
        $slotService = app(ClassroomSubjectSlotService::class);
        $keptIds = [];

        foreach ($assignments as $assignment) {
            $classroomId = (int) ($assignment['classroom_id'] ?? 0);
            if ($classroomId <= 0) {
                continue;
            }

            $streamId = filled($assignment['stream_id'] ?? null) ? (int) $assignment['stream_id'] : null;
            $staffId = filled($assignment['staff_id'] ?? null) ? (int) $assignment['staff_id'] : null;
            $yearId = filled($assignment['academic_year_id'] ?? null) ? (int) $assignment['academic_year_id'] : null;
            $termId = filled($assignment['term_id'] ?? null) ? (int) $assignment['term_id'] : null;
            $attrs = [
                'is_compulsory' => (bool) ($assignment['is_compulsory'] ?? ! $subject->is_optional),
            ];

            $classroom = Classroom::with(['primaryStreams', 'streams'])->find($classroomId);
            $streams = $classroom ? $classroom->allStreams() : collect();

            // Explicit stream, or class has no streams → single slot.
            if ($streamId !== null || $streams->isEmpty()) {
                $existingId = filled($assignment['id'] ?? null) ? (int) $assignment['id'] : null;
                if ($existingId) {
                    $row = ClassroomSubject::query()->where('subject_id', $subject->id)->find($existingId);
                    if ($row) {
                        $row->update([
                            'classroom_id' => $classroomId,
                            'stream_id' => $streamId,
                            'staff_id' => $staffId,
                            'academic_year_id' => $yearId,
                            'term_id' => $termId,
                            'is_compulsory' => $attrs['is_compulsory'],
                        ]);
                        $keptIds[] = $row->id;
                        continue;
                    }
                }

                $slotService->upsertSlot($classroomId, $subject->id, $streamId, $staffId, $yearId, $termId, $attrs);
                $keptIds = array_merge($keptIds, $this->findSlotIds($subject->id, $classroomId, $streamId, $yearId, $termId));
                continue;
            }

            // "All streams" — create/update one slot per stream with the same teacher.
            foreach ($streams as $stream) {
                $slotService->upsertSlot($classroomId, $subject->id, (int) $stream->id, $staffId, $yearId, $termId, $attrs);
                $keptIds = array_merge($keptIds, $this->findSlotIds($subject->id, $classroomId, (int) $stream->id, $yearId, $termId));
            }
        }

        if ($replaceMissing) {
            $subject->classroomSubjects()
                ->when(! empty($keptIds), fn ($q) => $q->whereNotIn('id', array_unique($keptIds)))
                ->when(empty($keptIds), fn ($q) => $q)
                ->delete();
        }
    }

    /**
     * @return list<int>
     */
    protected function findSlotIds(int $subjectId, int $classroomId, ?int $streamId, ?int $yearId, ?int $termId): array
    {
        $q = ClassroomSubject::query()
            ->where('subject_id', $subjectId)
            ->where('classroom_id', $classroomId);

        if ($streamId === null) {
            $q->whereNull('stream_id');
        } else {
            $q->where('stream_id', $streamId);
        }

        if ($yearId === null) {
            $q->whereNull('academic_year_id');
        } else {
            $q->where('academic_year_id', $yearId);
        }

        if ($termId === null) {
            $q->whereNull('term_id');
        } else {
            $q->where('term_id', $termId);
        }

        return $q->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * Generate CBC/CBE Kenyan subjects
     */
    public function generateCBCSubjects(Request $request)
    {
        $validated = $request->validate([
            'level' => 'required|in:all,Foundation,PP1,PP2,Grade 1,Grade 2,Grade 3,Grade 4,Grade 5,Grade 6,Grade 7,Grade 8,Grade 9',
            'assign_to_classrooms' => 'nullable|boolean',
            'classroom_ids' => 'nullable|array',
            'classroom_ids.*' => 'exists:classrooms,id',
            'wipe_all' => 'nullable|boolean',
            'wipe_confirm' => 'nullable|string|max:32',
        ]);

        $assignToClassrooms = $request->boolean('assign_to_classrooms', false);
        $classroomIds = collect($validated['classroom_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($request->boolean('wipe_all')) {
            if (($validated['wipe_confirm'] ?? '') !== 'WIPESUBJECTS') {
                return back()
                    ->withInput()
                    ->withErrors(['wipe_confirm' => 'Type WIPESUBJECTS (exactly) to confirm wiping all subject-linked data.']);
            }
        }

        try {
            /** @var CbcRationalizedSubjectSyncService $sync */
            $sync = app(CbcRationalizedSubjectSyncService::class);

            if ($request->boolean('wipe_all')) {
                $result = $sync->wipeAllSubjectsAndReseed($assignToClassrooms);
                $msg = 'All subject-linked academic data was cleared and the canonical CBC catalogue was recreated ('.$result['created'].' codes).';
                if ($assignToClassrooms) {
                    $msg .= ' '.$result['migrated_assignments'].' classroom assignment row(s) created across '.$result['levels_processed'].' level bands.';
                } else {
                    $msg .= ' Use “Assign to classrooms” or run sync again to link classes.';
                }

                return redirect()
                    ->route('academics.subjects.index')
                    ->with('success', $msg);
            }

            if ($validated['level'] === 'all') {
                $result = $sync->syncAllLevels(
                    $assignToClassrooms,
                    $classroomIds,
                );
                $msg = 'All '.$result['levels_processed'].' level bands processed: '.$result['created'].' subject code(s) in catalogue.';
                if ($result['migrated_assignments'] > 0) {
                    $msg .= ' '.$result['migrated_assignments'].' classroom assignment row(s) created for core subjects.';
                }
                if ($assignToClassrooms) {
                    $msg .= ' Optional JHS electives are in the catalogue; add per class if you offer them.';
                }
            } else {
                $result = $sync->syncLevel(
                    $validated['level'],
                    $assignToClassrooms,
                    $classroomIds,
                );

                $levelDisplayName = ucwords(str_replace('_', ' ', $result['level_type']));
                $msg = 'Catalogue: '.$result['created'].' subject code(s). Band '.$levelDisplayName.' ('.$validated['level'].'): '.$result['codes_for_level'].' code(s) apply.';
                if ($result['migrated_assignments'] > 0) {
                    $msg .= ' '.$result['migrated_assignments'].' classroom assignment row(s) for core subjects.';
                }
                if ($assignToClassrooms) {
                    if (($result['assign_classroom_count'] ?? 0) > 0) {
                        $msg .= ' Matched '.(int) $result['assign_classroom_count'].' classroom(s).';
                    } else {
                        $msg .= ' No classrooms matched (set classroom level_type or pick classes).';
                    }
                }
                if (in_array($validated['level'], ['Grade 7', 'Grade 8', 'Grade 9'], true)) {
                    $msg .= ' Optional languages / IRE / HRE exist in the catalogue; assign per class if needed.';
                }
            }

            return redirect()
                ->route('academics.subjects.index')
                ->with('success', $msg);
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to generate subjects: ' . $e->getMessage());
        }
    }

    public function teacherAssignments(Request $request)
    {
        $perPage = (int) $request->input('per_page', 25);
        $perPage = max(10, min(100, $perPage));

        $slotService = app(ClassroomSubjectSlotService::class);
        Classroom::with(['primaryStreams', 'streams'])->get()->each(
            fn (Classroom $classroom) => $slotService->splitWholeClassSlots($classroom)
        );

        $query = ClassroomSubject::with(['classroom', 'stream', 'subject', 'teacher']);

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->classroom_id);
        }

        if ($request->filled('level')) {
            $level = $request->level;
            // Check if it's a level type (preschool, lower_primary, etc.)
            $levelTypes = ['preschool', 'lower_primary', 'upper_primary', 'junior_high'];
            if (in_array($level, $levelTypes)) {
                $query->whereHas('classroom', fn($q) => $q->where('level_type', $level));
            } else {
                // Fallback to subject level
                $query->whereHas('subject', fn($q) => $q->where('level', $level));
            }
        }

        if ($request->filled('assigned')) {
            if ($request->assigned === 'assigned') {
                $query->whereNotNull('staff_id');
            } elseif ($request->assigned === 'unassigned') {
                $query->whereNull('staff_id');
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('subject', function($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('learning_area', 'like', "%{$search}%");
                })->orWhereHas('classroom', function($class) use ($search) {
                    $class->where('name', 'like', "%{$search}%");
                })->orWhereHas('stream', function($stream) use ($search) {
                    $stream->where('name', 'like', "%{$search}%");
                });
            });
        }

        $assignments = $query
            ->orderBy('classroom_id')
            ->orderBy('subject_id')
            ->orderByRaw('stream_id IS NULL')
            ->orderBy('stream_id')
            ->orderBy('staff_id')
            ->paginate($perPage)
            ->withQueryString();

        $subjects = Subject::orderBy('name')->get(['id', 'name', 'code', 'level']);
        $classrooms = Classroom::orderBy('name')->get(['id', 'name', 'level_type']);
        
        // Get level types from classrooms
        $levelTypes = Classroom::whereNotNull('level_type')
            ->distinct()
            ->pluck('level_type')
            ->map(function($type) {
                return [
                    'value' => $type,
                    'label' => ucwords(str_replace('_', ' ', $type))
                ];
            })
            ->sortBy('label')
            ->values();
        
        // Also get subject levels as fallback
        $subjectLevels = Subject::select('level')->whereNotNull('level')->distinct()->orderBy('level')->pluck('level');
        
        $teachers = Staff::where('status', 'active')->whereHas('user.roles', fn($q) => $q->whereIn('name', ['Teacher', 'teacher', 'Senior Teacher', 'Supervisor', 'supervisor']))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return view('academics.subjects.assign-teachers', compact(
            'assignments',
            'subjects',
            'classrooms',
            'levelTypes',
            'subjectLevels',
            'teachers',
            'perPage'
        ));
    }

    public function saveTeacherAssignments(Request $request)
    {
        $normalized = collect($request->input('assignments', []))
            ->map(function ($v) {
                if ($v === null || $v === '') {
                    return null;
                }

                return (int) $v;
            })
            ->all();

        $request->merge(['assignments' => $normalized]);

        $data = $request->validate([
            'assignments' => 'required|array|min:1',
            'assignments.*' => 'nullable|exists:staff,id',
        ]);

        $ids = collect(array_keys($data['assignments']))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        if ($ids->isEmpty()) {
            return back()->with('error', 'No valid classroom subjects were provided.');
        }

        $assignmentModels = ClassroomSubject::whereIn('id', $ids)->get()->keyBy('id');

        try {
            DB::transaction(function () use ($assignmentModels, $data) {
                foreach ($data['assignments'] as $id => $staffId) {
                    $id = (int) $id;
                    if (! $assignmentModels->has($id)) {
                        continue;
                    }

                    $assignment = $assignmentModels->get($id);
                    $assignment->update([
                        'staff_id' => $staffId ?: null,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', 'Could not save teacher assignments: '.$e->getMessage());
        }

        return back()->with('success', 'Subject teacher assignments updated successfully.');
    }

    /**
     * Assign subjects to classrooms (one slot per stream when the class has streams).
     */
    public function assignToClassrooms(Request $request, ClassroomSubjectSlotService $slots)
    {
        $validated = $request->validate([
            'subject_ids' => 'required|array',
            'subject_ids.*' => 'exists:subjects,id',
            'classroom_ids' => 'required|array',
            'classroom_ids.*' => 'exists:classrooms,id',
            'staff_id' => 'nullable|exists:staff,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'term_id' => 'nullable|exists:terms,id',
            'is_compulsory' => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            $count = 0;
            $staffId = isset($validated['staff_id']) ? (int) $validated['staff_id'] : null;
            if ($staffId === 0) {
                $staffId = null;
            }
            $yearId = $validated['academic_year_id'] ?? null;
            $termId = $validated['term_id'] ?? null;

            foreach ($validated['subject_ids'] as $subjectId) {
                $subject = Subject::findOrFail($subjectId);
                $baseAttrs = [
                    'is_compulsory' => $validated['is_compulsory'] ?? ! $subject->is_optional,
                ];

                foreach ($validated['classroom_ids'] as $classroomId) {
                    $count += $slots->ensureSlotsWithStaff(
                        (int) $classroomId,
                        (int) $subjectId,
                        $staffId ?: null,
                        $yearId ? (int) $yearId : null,
                        $termId ? (int) $termId : null,
                        $baseAttrs
                    );
                }
            }

            DB::commit();

            return redirect()
                ->route('academics.subjects.index')
                ->with('success', "Successfully created or updated {$count} class/subject slot(s). Classes with streams get one slot per stream.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Failed to assign subjects: ' . $e->getMessage());
        }
    }

    /**
     * Update lessons per week for a classroom subject
     */
    public function updateLessonsPerWeek(Request $request, ClassroomSubject $classroomSubject)
    {
        $validated = $request->validate([
            'lessons_per_week' => 'required|integer|min:1|max:20',
        ]);

        $classroomSubject->update($validated);

        return back()->with('success', 'Lessons per week updated successfully.');
    }
}
