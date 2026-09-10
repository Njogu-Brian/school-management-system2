<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Academics\Assessment;
use App\Models\Academics\Classroom;
use App\Models\Academics\Subject;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApiSpeedTestController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $request->validate([
            'student_id' => 'nullable|integer|exists:students,id',
            'classroom_id' => 'nullable|integer|exists:classrooms,id',
        ]);

        $query = Assessment::query()->where('assessment_type', 'Speed Test')->whereNotNull('batch_key');

        if ($user->shouldScopeAsParent()) {
            $studentId = (int) $request->input('student_id');
            if ($studentId < 1 || ! $user->canAccessStudent($studentId)) {
                abort(403, 'You do not have access to this student.');
            }
            $query->where('student_id', $studentId);
        } elseif ($user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Academic Administrator'])) {
            if ($request->filled('student_id')) {
                $query->where('student_id', (int) $request->student_id);
            }
            if ($request->filled('classroom_id')) {
                $query->where('classroom_id', (int) $request->classroom_id);
            }
        } elseif ($user->hasTeacherLikeRole()) {
            $classIds = $user->getDashboardClassroomIds();
            if ($classIds === []) {
                return response()->json(['success' => true, 'data' => []]);
            }
            $query->whereIn('classroom_id', $classIds);
            if ($request->filled('classroom_id')) {
                $classId = (int) $request->classroom_id;
                if (! $user->canTeacherAccessClassroom($classId)) {
                    abort(403, 'You do not have access to this class.');
                }
                $query->where('classroom_id', $classId);
            }
        } else {
            abort(403);
        }

        $rows = $query->with(['classroom', 'subject', 'student'])
            ->orderByDesc('assessment_date')
            ->orderByDesc('id')
            ->limit(2000)
            ->get();

        $includeEntries = $user->shouldScopeAsParent() || $request->filled('student_id');
        $batches = $rows->groupBy('batch_key')->map(function ($group) use ($includeEntries) {
            return $this->formatBatch($group, $includeEntries);
        })->values();

        return response()->json(['success' => true, 'data' => $batches]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (! $user || (! $user->hasTeacherLikeRole() && ! $user->hasAnyRole(['Super Admin', 'Admin']))) {
            abort(403, 'Only teaching staff can create speed tests.');
        }

        $data = $request->validate([
            'classroom_id' => 'required|integer|exists:classrooms,id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'question_count' => 'required|integer|min:1|max:200',
            'max_marks' => 'required|numeric|min:1|max:1000',
            'title' => 'nullable|string|max:100',
            'assessment_date' => 'nullable|date',
        ]);

        $classId = (int) $data['classroom_id'];
        $priv = $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary']);
        if (! $priv && ! $user->canTeacherAccessClassroom($classId)) {
            return response()->json(['success' => false, 'message' => 'You do not have access to this class.'], 403);
        }

        $staffId = $user->staff?->id;
        if (! $priv && $staffId) {
            $taught = DB::table('classroom_subjects')
                ->where('classroom_id', $classId)
                ->where('staff_id', $staffId)
                ->where('subject_id', $data['subject_id'])
                ->exists();
            if (! $taught && ! $user->isSeniorTeacherUser()) {
                return response()->json(['success' => false, 'message' => 'You do not teach this subject in this class.'], 403);
            }
        }

        $subject = Subject::findOrFail((int) $data['subject_id']);
        $classroom = Classroom::findOrFail($classId);
        $title = trim((string) ($data['title'] ?? '')) ?: ('Speed test — '.$subject->name);
        $batchKey = (string) Str::uuid();
        $date = $data['assessment_date'] ?? now()->toDateString();
        $max = (float) $data['max_marks'];
        $questions = (int) $data['question_count'];

        $studentsQuery = Student::query()
            ->where('classroom_id', $classId)
            ->where('archive', 0)
            ->where('is_alumni', false);
        if (! $priv && $user->hasTeacherLikeRole()) {
            $user->applyTeacherStudentFilter($studentsQuery);
        }
        $students = $studentsQuery->orderBy('last_name')->orderBy('first_name')->get();

        if ($students->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No students found in this class.'], 422);
        }

        DB::transaction(function () use ($students, $batchKey, $date, $classId, $subject, $staffId, $title, $max, $questions) {
            foreach ($students as $student) {
                Assessment::create([
                    'assessment_date' => $date,
                    'classroom_id' => $classId,
                    'subject_id' => $subject->id,
                    'student_id' => $student->id,
                    'staff_id' => $staffId,
                    'assessment_type' => 'Speed Test',
                    'academic_group' => $title,
                    'out_of' => $max,
                    'question_count' => $questions,
                    'batch_key' => $batchKey,
                    'score' => null,
                ]);
            }
        });

        $rows = Assessment::query()->with(['classroom', 'subject', 'student'])->where('batch_key', $batchKey)->get();

        return response()->json([
            'success' => true,
            'message' => 'Speed test created for '.$students->count().' students in '.$classroom->name.'.',
            'data' => $this->formatBatch($rows, true),
        ], 201);
    }

    public function show(Request $request, string $batchKey)
    {
        $rows = $this->authorizedBatch($request, $batchKey);
        return response()->json(['success' => true, 'data' => $this->formatBatch($rows, true)]);
    }

    public function saveMarks(Request $request, string $batchKey)
    {
        $user = $request->user();
        if (! $user || (! $user->hasTeacherLikeRole() && ! $user->hasAnyRole(['Super Admin', 'Admin']))) {
            abort(403, 'Only teaching staff can enter speed-test marks.');
        }

        $data = $request->validate([
            'entries' => 'required|array|min:1',
            'entries.*.student_id' => 'required|integer|exists:students,id',
            'entries.*.score' => 'nullable|numeric|min:0',
        ]);

        $rows = $this->authorizedBatch($request, $batchKey);
        $byStudent = $rows->keyBy('student_id');
        $max = (float) ($rows->first()?->out_of ?? 0);

        DB::transaction(function () use ($data, $byStudent, $max) {
            foreach ($data['entries'] as $entry) {
                $row = $byStudent->get((int) $entry['student_id']);
                if (! $row) {
                    continue;
                }
                $score = $entry['score'] === null || $entry['score'] === '' ? null : (float) $entry['score'];
                if ($score !== null && $max > 0) {
                    $score = min($score, $max);
                }
                $row->score = $score;
                $row->save();
            }
        });

        $fresh = Assessment::query()->with(['classroom', 'subject', 'student'])->where('batch_key', $batchKey)->get();

        return response()->json([
            'success' => true,
            'message' => 'Speed test marks saved.',
            'data' => $this->formatBatch($fresh, true),
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Assessment>
     */
    protected function authorizedBatch(Request $request, string $batchKey)
    {
        $user = $request->user();
        $rows = Assessment::query()
            ->with(['classroom', 'subject', 'student'])
            ->where('batch_key', $batchKey)
            ->where('assessment_type', 'Speed Test')
            ->get();

        if ($rows->isEmpty()) {
            abort(404, 'Speed test not found.');
        }

        $classId = (int) $rows->first()->classroom_id;
        if ($user->shouldScopeAsParent()) {
            $allowed = $rows->first(fn (Assessment $row) => $user->canAccessStudent((int) $row->student_id));
            if (! $allowed) {
                abort(403, 'You do not have access to this speed test.');
            }
            return $rows->filter(fn (Assessment $row) => $user->canAccessStudent((int) $row->student_id))->values();
        }

        if ($user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Academic Administrator'])) {
            return $rows;
        }

        if ($user->hasTeacherLikeRole() && $user->canTeacherAccessClassroom($classId)) {
            return $rows;
        }

        abort(403, 'You do not have access to this speed test.');
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Assessment>  $rows
     * @return array<string, mixed>
     */
    protected function formatBatch($rows, bool $includeEntries = false): array
    {
        $first = $rows->first();
        $marked = $rows->filter(fn (Assessment $r) => $r->score !== null)->count();
        $payload = [
            'batch_key' => $first?->batch_key,
            'title' => $first?->academic_group ?: 'Speed Test',
            'classroom_id' => $first?->classroom_id,
            'classroom_name' => $first?->classroom?->name,
            'subject_id' => $first?->subject_id,
            'subject_name' => $first?->subject?->name,
            'question_count' => $first?->question_count,
            'max_marks' => $first?->out_of !== null ? (float) $first->out_of : null,
            'assessment_date' => optional($first?->assessment_date)->toDateString(),
            'student_count' => $rows->count(),
            'marked_count' => $marked,
        ];

        if ($includeEntries) {
            $payload['entries'] = $rows->sortBy(fn (Assessment $r) => $r->student?->last_name.' '.$r->student?->first_name)
                ->values()
                ->map(fn (Assessment $r) => [
                    'id' => $r->id,
                    'student_id' => $r->student_id,
                    'student_name' => trim(($r->student?->first_name ?? '').' '.($r->student?->last_name ?? '')),
                    'score' => $r->score !== null ? (float) $r->score : null,
                    'out_of' => $r->out_of !== null ? (float) $r->out_of : null,
                    'score_percent' => $r->score_percent !== null ? (float) $r->score_percent : null,
                ])->all();
        }

        return $payload;
    }
}
