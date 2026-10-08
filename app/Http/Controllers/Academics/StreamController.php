<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\Academics\Stream;
use App\Models\Academics\Classroom;
use App\Models\AssistantClassTeacherAssignment;
use App\Models\ClassTeacherAssignment;
use App\Models\Staff;
use App\Models\Student;
use App\Services\StreamLifecycleService;

class StreamController extends Controller
{
    public function __construct(
        protected StreamLifecycleService $streamLifecycle
    ) {
    }

    /**
     * Get supervised classroom IDs for senior teachers (empty for non–senior teachers).
     */
    protected function supervisedClassroomIds(): array
    {
        $user = auth()->user();
        if (!$user || !$user->hasRole('Senior Teacher')) {
            return [];
        }
        return $user->getSupervisedClassroomIds();
    }

    public function index(Request $request)
    {
        $supervisedIds = $this->supervisedClassroomIds();

        $classroomQuery = Classroom::with(['primaryStreams'])
            ->withCount(['students' => fn ($q) => $q->where('archive', 0)])
            ->orderBy('name');

        if (! empty($supervisedIds)) {
            $classroomQuery->whereIn('id', $supervisedIds);
        }

        $classrooms = $classroomQuery->get();

        $classTeacherRows = ClassTeacherAssignment::query()
            ->with('staff')
            ->get();
        $classTeacherMap = [];
        foreach ($classTeacherRows as $row) {
            if (!$row->staff || $row->staff->isArchived()) {
                continue;
            }
            $key = (int) $row->classroom_id . ':' . ($row->stream_id === null ? 'null' : (int) $row->stream_id);
            $classTeacherMap[$key] = $row->staff;
        }

        $assistantRows = AssistantClassTeacherAssignment::query()
            ->with('staff')
            ->get();
        $assistantMap = [];
        foreach ($assistantRows as $row) {
            if (!$row->staff || $row->staff->isArchived()) {
                continue;
            }
            $key = (int) $row->classroom_id . ':' . ($row->stream_id === null ? 'null' : (int) $row->stream_id);
            $assistantMap[$key] = $row->staff;
        }

        $teacherRoleNames = ['Teacher', 'teacher', 'Senior Teacher', 'senior teacher', 'Supervisor', 'supervisor'];
        $staffTeachers = Staff::with('user')
            ->where('status', 'active')
            ->whereHas('user.roles', fn ($q) => $q->whereIn('name', $teacherRoleNames))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $studentCountRows = Student::query()
            ->where('archive', 0)
            ->whereNotNull('stream_id')
            ->whereNotNull('classroom_id')
            ->selectRaw('classroom_id, stream_id, count(*) as total')
            ->groupBy('classroom_id', 'stream_id')
            ->get();
        $studentCountsByStream = [];
        foreach ($studentCountRows as $row) {
            $studentCountsByStream[(int) $row->classroom_id . ':' . (int) $row->stream_id] = (int) $row->total;
        }

        $catalogStreams = Stream::query()->orderBy('name')->get();

        return view('academics.streams.index', compact(
            'classrooms',
            'classTeacherMap',
            'assistantMap',
            'staffTeachers',
            'studentCountsByStream',
            'catalogStreams',
        ));
    }

    public function create()
    {
        $classrooms = Classroom::orderBy('name')->get();
        $supervisedIds = $this->supervisedClassroomIds();
        if (!empty($supervisedIds)) {
            $classrooms = $classrooms->whereIn('id', $supervisedIds)->values();
        }
        $catalogStreams = Stream::query()->orderBy('name')->get();

        return view('academics.streams.create', compact('classrooms', 'catalogStreams'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'stream_id' => 'required|exists:streams,id',
            'classroom_id' => 'required|exists:classrooms,id',
        ]);

        $classroom = Classroom::findOrFail($request->classroom_id);
        $this->linkExistingStream($classroom, (int) $request->stream_id);

        return redirect()->route('academics.streams.index')
            ->with('success', 'Stream assigned to ' . $classroom->name . '.');
    }

    public function edit($id)
    {
        $stream = Stream::with('classrooms')->findOrFail($id);
        $supervisedIds = $this->supervisedClassroomIds();
        if (!empty($supervisedIds)) {
            $allowed = in_array((int) $stream->classroom_id, $supervisedIds, true) ||
                $stream->classrooms->contains(fn ($c) => in_array((int) $c->id, $supervisedIds, true));
            if (!$allowed) {
                abort(403, 'You can only edit streams for classes you supervise.');
            }
        }
        $classrooms = Classroom::orderBy('name')->get();
        if (!empty($supervisedIds)) {
            $classrooms = $classrooms->whereIn('id', $supervisedIds)->values();
        }
        $assignedClassrooms = $stream->classrooms->pluck('id')->toArray();

        return view('academics.streams.edit', compact('stream', 'classrooms', 'assignedClassrooms'));
    }

    public function update(Request $request, $id)
    {
        $stream = Stream::with('classrooms')->findOrFail($id);
        $previousClassroomIds = $this->streamLifecycle->collectLinkedClassroomIds($stream);
        $supervisedIds = $this->supervisedClassroomIds();
        if (!empty($supervisedIds)) {
            $allowed = in_array((int) $stream->classroom_id, $supervisedIds, true) ||
                $stream->classrooms->contains(fn ($c) => in_array((int) $c->id, $supervisedIds, true));
            if (!$allowed) {
                abort(403, 'You can only edit streams for classes you supervise.');
            }
            if (!in_array((int) $request->classroom_id, $supervisedIds, true)) {
                abort(403, 'You can only assign streams to classes you supervise.');
            }
            if ($request->has('classroom_ids')) {
                foreach ((array) $request->classroom_ids as $cid) {
                    if (!in_array((int) $cid, $supervisedIds, true)) {
                        abort(403, 'You can only assign streams to classes you supervise.');
                    }
                }
            }
        }

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                // Unique per primary classroom: same name can exist in different classrooms
                Rule::unique('streams')->where(function ($query) use ($request) {
                    return $query->where('classroom_id', $request->classroom_id);
                })->ignore($id),
            ],
            'classroom_id' => 'required|exists:classrooms,id',
            'classroom_ids' => 'nullable|array',
            'classroom_ids.*' => 'exists:classrooms,id',
        ]);

        $stream->update([
            'name' => $request->name,
            'classroom_id' => $request->classroom_id,
        ]);

        // Sync additional classrooms via pivot table (exclude primary classroom)
        if ($request->has('classroom_ids')) {
            $additionalClassrooms = array_filter($request->classroom_ids, fn($id) => $id != $request->classroom_id);
            $stream->classrooms()->sync($additionalClassrooms);
        } else {
            // If no additional classrooms selected, remove all pivot assignments
            $stream->classrooms()->detach();
        }

        // The primary classroom must stay linked, otherwise the class list hides the stream.
        if ($stream->classroom_id) {
            $stream->classrooms()->syncWithoutDetaching([(int) $stream->classroom_id]);
        }

        $stream->refresh();
        $stream->load(['classrooms', 'classroom']);
        $currentClassroomIds = $this->streamLifecycle->collectLinkedClassroomIds($stream);
        $removedClassroomIds = array_values(array_diff($previousClassroomIds, $currentClassroomIds));
        if ($removedClassroomIds !== []) {
            $this->streamLifecycle->propagateWhenClassroomsRemoved($stream, $removedClassroomIds);
        }

        return redirect()->route('academics.streams.index')
            ->with('success', 'Stream updated successfully. Related teacher, subject, fee, and student links for removed classes were updated.');
    }

    /**
     * Quick update from Class Streams card modal (name + homeroom teachers).
     */
    public function quickUpdate(Request $request, $id)
    {
        $stream = Stream::with('classrooms')->findOrFail($id);
        $supervisedIds = $this->supervisedClassroomIds();
        if (! empty($supervisedIds)) {
            $allowed = in_array((int) $stream->classroom_id, $supervisedIds, true) ||
                $stream->classrooms->contains(fn ($c) => in_array((int) $c->id, $supervisedIds, true));
            if (! $allowed) {
                abort(403, 'You can only edit streams for classes you supervise.');
            }
        }

        $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'class_teacher_staff_id' => 'nullable|integer|exists:staff,id',
            'assistant_teacher_staff_id' => 'nullable|integer|exists:staff,id',
        ]);

        $classroomId = (int) $request->classroom_id;
        $streamId = (int) $stream->id;
        $linked = $this->streamLifecycle->collectLinkedClassroomIds($stream);
        if (! in_array($classroomId, $linked, true)) {
            abort(403, 'This stream is not assigned to that class.');
        }
        if (! empty($supervisedIds) && ! in_array($classroomId, $supervisedIds, true)) {
            abort(403, 'You can only edit classes you supervise.');
        }

        if ($request->filled('class_teacher_staff_id')) {
            ClassTeacherAssignment::updateOrCreate(
                ['classroom_id' => $classroomId, 'stream_id' => $streamId],
                ['staff_id' => (int) $request->class_teacher_staff_id]
            );
        } else {
            ClassTeacherAssignment::query()
                ->where('classroom_id', $classroomId)
                ->where('stream_id', $streamId)
                ->delete();
        }

        if ($request->filled('assistant_teacher_staff_id')) {
            AssistantClassTeacherAssignment::updateOrCreate(
                ['classroom_id' => $classroomId, 'stream_id' => $streamId],
                ['staff_id' => (int) $request->assistant_teacher_staff_id]
            );
        } else {
            AssistantClassTeacherAssignment::query()
                ->where('classroom_id', $classroomId)
                ->where('stream_id', $streamId)
                ->delete();
        }

        return redirect()->route('academics.streams.index')
            ->with('success', 'Stream details updated successfully.');
    }

    /**
     * Quick update for classrooms without streams (homeroom only).
     */
    public function quickUpdateClassroom(Request $request, $classroomId)
    {
        $classroom = Classroom::findOrFail($classroomId);
        $supervisedIds = $this->supervisedClassroomIds();
        if (! empty($supervisedIds) && ! in_array((int) $classroom->id, $supervisedIds, true)) {
            abort(403, 'You can only edit classes you supervise.');
        }

        $request->validate([
            'class_teacher_staff_id' => 'nullable|integer|exists:staff,id',
            'assistant_teacher_staff_id' => 'nullable|integer|exists:staff,id',
        ]);

        $cid = (int) $classroom->id;

        if ($request->filled('class_teacher_staff_id')) {
            ClassTeacherAssignment::updateOrCreate(
                ['classroom_id' => $cid, 'stream_id' => null],
                ['staff_id' => (int) $request->class_teacher_staff_id]
            );
        } else {
            ClassTeacherAssignment::query()
                ->where('classroom_id', $cid)
                ->whereNull('stream_id')
                ->delete();
        }

        if ($request->filled('assistant_teacher_staff_id')) {
            AssistantClassTeacherAssignment::updateOrCreate(
                ['classroom_id' => $cid, 'stream_id' => null],
                ['staff_id' => (int) $request->assistant_teacher_staff_id]
            );
        } else {
            AssistantClassTeacherAssignment::query()
                ->where('classroom_id', $cid)
                ->whereNull('stream_id')
                ->delete();
        }

        return redirect()->route('academics.streams.index')
            ->with('success', $classroom->name . ' class teacher updated successfully.');
    }

    public function assignTeachers(Request $request, $id)
    {
        $stream = Stream::findOrFail($id);
        $supervisedIds = $this->supervisedClassroomIds();
        if (!empty($supervisedIds)) {
            $allowed = in_array((int) $stream->classroom_id, $supervisedIds, true) ||
                $stream->classrooms->contains(fn ($c) => in_array((int) $c->id, $supervisedIds, true));
            if (!$allowed) {
                abort(403, 'You can only assign teachers to streams for classes you supervise.');
            }
            if (!in_array((int) $request->classroom_id, $supervisedIds, true)) {
                abort(403, 'You can only assign teachers for classrooms you supervise.');
            }
        }

        $request->validate([
            'teacher_ids' => 'nullable|array',
            'teacher_ids.*' => 'exists:users,id',
            'stream_id' => 'nullable|exists:streams,id',
            'classroom_id' => 'required|exists:classrooms,id',
        ]);

        // Double-check: if stream_id is provided in request, ensure it matches the route parameter
        if ($request->has('stream_id') && (int)$request->stream_id !== (int)$id) {
            return redirect()->route('academics.assign-teachers')
                ->with('error', 'Stream ID mismatch. Assignment cancelled for security.');
        }

        $classroomId = $request->classroom_id;
        
        // Verify the classroom_id matches the stream's primary classroom or is in the stream's additional classrooms
        $isValidClassroom = $stream->classroom_id == $classroomId || 
                          $stream->classrooms->contains('id', $classroomId);
        
        if (!$isValidClassroom) {
            return redirect()->route('academics.assign-teachers')
                ->with('error', 'Invalid classroom for this stream. Assignment cancelled.');
        }

        // Get current teachers for this specific stream-classroom combination
        $currentTeachers = \DB::table('stream_teacher')
            ->where('stream_id', $stream->id)
            ->where('classroom_id', $classroomId)
            ->pluck('teacher_id')
            ->toArray();
        
        $newTeachers = $request->teacher_ids ?? [];

        // Delete existing assignments for this stream-classroom combination
        \DB::table('stream_teacher')
            ->where('stream_id', $stream->id)
            ->where('classroom_id', $classroomId)
            ->delete();

        // Insert new assignments with classroom_id
        $insertData = [];
        foreach ($newTeachers as $teacherId) {
            $insertData[] = [
                'stream_id' => $stream->id,
                'teacher_id' => $teacherId,
                'classroom_id' => $classroomId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($insertData)) {
            \DB::table('stream_teacher')->insert($insertData);
        }

        \Log::info('Stream teacher assignment', [
            'stream_id' => $stream->id,
            'stream_name' => $stream->name,
            'classroom_id' => $classroomId,
            'previous_teachers' => $currentTeachers,
            'new_teachers' => $newTeachers,
        ]);

        $classroom = Classroom::find($classroomId);
        return redirect()->route('academics.assign-teachers')
            ->with('success', "Teachers assigned to '{$stream->name}' stream in '{$classroom->name}' successfully.");
    }

    /**
     * Attach an existing school stream (Love, Peace) to a class. Does not create a class or a new stream.
     */
    public function assignToClassroom(Request $request, Classroom $classroom)
    {
        $request->validate([
            'stream_id' => 'required|exists:streams,id',
            'class_teacher_staff_id' => 'nullable|integer|exists:staff,id',
            'assistant_teacher_staff_id' => 'nullable|integer|exists:staff,id',
        ]);

        $stream = $this->linkExistingStream($classroom, (int) $request->stream_id);

        if ($request->filled('class_teacher_staff_id')) {
            ClassTeacherAssignment::updateOrCreate(
                ['classroom_id' => $classroom->id, 'stream_id' => $stream->id],
                ['staff_id' => (int) $request->class_teacher_staff_id]
            );
        }

        if ($request->filled('assistant_teacher_staff_id')) {
            AssistantClassTeacherAssignment::updateOrCreate(
                ['classroom_id' => $classroom->id, 'stream_id' => $stream->id],
                ['staff_id' => (int) $request->assistant_teacher_staff_id]
            );
        }

        return redirect()->route('academics.streams.index')
            ->with('success', $stream->name . ' assigned to ' . $classroom->name . '.');
    }

    /**
     * Remove a stream from one class. Other classes, and the stream itself, stay as they are.
     */
    public function unassignFromClassroom(Request $request, $id)
    {
        $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
        ]);

        $stream = Stream::with('classrooms')->findOrFail($id);
        $classroomId = (int) $request->classroom_id;
        $supervisedIds = $this->supervisedClassroomIds();
        if (! empty($supervisedIds) && ! in_array($classroomId, $supervisedIds, true)) {
            abort(403, 'You can only edit classes you supervise.');
        }

        $linked = $this->streamLifecycle->collectLinkedClassroomIds($stream);
        if (! in_array($classroomId, $linked, true)) {
            return redirect()->route('academics.streams.index')
                ->with('success', 'That stream is not assigned to this class.');
        }

        $others = array_values(array_filter($linked, fn ($cid) => (int) $cid !== $classroomId));
        if ($others === []) {
            return redirect()->route('academics.streams.index')
                ->with('error', $stream->name . ' is only on this class, so it was left in place.');
        }

        if ((int) $stream->classroom_id === $classroomId) {
            $stream->update(['classroom_id' => $others[0]]);
        }

        $stream->classrooms()->detach($classroomId);
        if ($stream->classroom_id) {
            $stream->classrooms()->syncWithoutDetaching([(int) $stream->classroom_id]);
        }

        $this->streamLifecycle->propagateWhenClassroomsRemoved($stream, [$classroomId]);

        ClassTeacherAssignment::query()
            ->where('classroom_id', $classroomId)
            ->where('stream_id', $stream->id)
            ->delete();
        AssistantClassTeacherAssignment::query()
            ->where('classroom_id', $classroomId)
            ->where('stream_id', $stream->id)
            ->delete();

        $classroom = Classroom::find($classroomId);

        return redirect()->route('academics.streams.index')
            ->with('success', $stream->name . ' removed from ' . ($classroom->name ?? 'that class') . '. Students and teachers in other classes were left unchanged.');
    }

    protected function linkExistingStream(Classroom $classroom, int $streamId): Stream
    {
        $supervisedIds = $this->supervisedClassroomIds();
        if (! empty($supervisedIds) && ! in_array((int) $classroom->id, $supervisedIds, true)) {
            abort(403, 'You can only assign streams to classes you supervise.');
        }

        $stream = Stream::with('classrooms')->findOrFail($streamId);
        $linked = $this->streamLifecycle->collectLinkedClassroomIds($stream);
        if (! in_array((int) $classroom->id, $linked, true)) {
            $stream->classrooms()->syncWithoutDetaching([(int) $classroom->id]);
        }
        if ($stream->classroom_id) {
            $stream->classrooms()->syncWithoutDetaching([(int) $stream->classroom_id]);
        }

        app(\App\Services\ClassroomSubjectSlotService::class)->splitWholeClassSlots($classroom->fresh());

        return $stream;
    }

    public function destroy($id)
    {
        $stream = Stream::with('classrooms')->findOrFail($id);
        $supervisedIds = $this->supervisedClassroomIds();
        if (!empty($supervisedIds)) {
            $allowed = in_array((int) $stream->classroom_id, $supervisedIds, true) ||
                $stream->classrooms->contains(fn ($c) => in_array((int) $c->id, $supervisedIds, true));
            if (!$allowed) {
                abort(403, 'You can only delete streams for classes you supervise.');
            }
        }

        $studentsUpdated = Student::withArchived()
            ->where('stream_id', $stream->id)
            ->count();

        $this->streamLifecycle->deleteStreamWithCascade($stream);

        $message = $studentsUpdated > 0
            ? "Stream deleted successfully. {$studentsUpdated} student(s) moved to no stream (class unchanged). Subject, fee, and teacher rows for this stream were removed where applicable."
            : 'Stream deleted successfully. Related subject, fee, and teacher data for this stream were removed where applicable.';

        return redirect()->route('academics.streams.index')
            ->with('success', $message);
    }
}
