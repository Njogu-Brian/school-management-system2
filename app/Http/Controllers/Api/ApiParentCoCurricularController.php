<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParentActivityChangeRequest;
use App\Models\Student;
use App\Services\ParentCoCurricularService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ApiParentCoCurricularController extends Controller
{
    public function __construct(protected ParentCoCurricularService $coCurricular) {}

    public function show(Request $request, Student $student)
    {
        $this->assertParentAccess($request, $student);

        $year = $request->filled('year') ? (int) $request->integer('year') : null;
        $term = $request->filled('term') ? (int) $request->integer('term') : null;

        return response()->json([
            'success' => true,
            'data' => $this->coCurricular->snapshotForStudent($student, $year, $term),
        ]);
    }

    public function store(Request $request, Student $student)
    {
        $this->assertParentAccess($request, $student);

        $validated = $request->validate([
            'votehead_id' => 'required|integer|exists:voteheads,id',
            'action' => 'required|in:join,leave',
            'year' => 'required|integer|min:2000|max:2100',
            'term' => 'required|integer|in:1,2,3',
            'note' => 'nullable|string|max:500',
        ]);

        try {
            $change = $this->coCurricular->requestChange(
                $student,
                $request->user(),
                (int) $validated['votehead_id'],
                $validated['action'],
                (int) $validated['year'],
                (int) $validated['term'],
                $validated['note'] ?? null,
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'The school office has been notified and will confirm this change.',
            'data' => $this->coCurricular->serializeRequest($change->load(['student', 'votehead'])),
        ]);
    }

    public function cancel(Request $request, Student $student, ParentActivityChangeRequest $changeRequest)
    {
        $this->assertParentAccess($request, $student);
        if ((int) $changeRequest->student_id !== (int) $student->id) {
            abort(404);
        }

        try {
            $this->coCurricular->cancelRequest($changeRequest, $request->user());
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Request cancelled.',
        ]);
    }

    protected function assertParentAccess(Request $request, Student $student): void
    {
        $user = $request->user();
        abort_unless($user && $user->canAccessStudent($student->id), 403, 'You do not have access to this student.');
    }
}
