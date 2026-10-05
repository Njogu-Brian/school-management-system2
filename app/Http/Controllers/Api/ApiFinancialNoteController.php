<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinancialNote;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiFinancialNoteController extends Controller
{
    public function index(Request $request, $id): JsonResponse
    {
        $student = Student::withArchived()->findOrFail($id);
        $notes = FinancialNote::forStudentContext((int) $student->id, $student->family_id ? (int) $student->family_id : null)
            ->limit(100)
            ->get()
            ->map(fn (FinancialNote $n) => $this->transform($n))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $notes,
        ]);
    }

    public function store(Request $request, $id): JsonResponse
    {
        $student = Student::withArchived()->findOrFail($id);

        $validated = $request->validate([
            'body' => 'required|string|max:5000',
            'promise_date' => 'nullable|date',
            'is_pinned' => 'nullable|boolean',
            'scope' => 'nullable|in:student,family',
        ]);

        $scope = $validated['scope'] ?? 'student';
        $note = FinancialNote::create([
            'student_id' => $scope === 'family' ? null : $student->id,
            'family_id' => $student->family_id,
            'body' => $validated['body'],
            'promise_date' => $validated['promise_date'] ?? null,
            'is_pinned' => (bool) ($validated['is_pinned'] ?? false),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->transform($note->load('creator:id,name')),
        ], 201);
    }

    public function destroy($id, FinancialNote $financialNote): JsonResponse
    {
        $student = Student::withArchived()->findOrFail($id);
        $allowed = ($financialNote->student_id && (int) $financialNote->student_id === (int) $student->id)
            || ($financialNote->family_id && $student->family_id && (int) $financialNote->family_id === (int) $student->family_id);

        if (!$allowed) {
            abort(404);
        }

        $financialNote->delete();

        return response()->json(['success' => true, 'data' => ['ok' => true]]);
    }

    private function transform(FinancialNote $n): array
    {
        return [
            'id' => $n->id,
            'student_id' => $n->student_id,
            'family_id' => $n->family_id,
            'scope' => $n->student_id ? 'student' : 'family',
            'body' => $n->body,
            'promise_date' => $n->promise_date?->format('Y-m-d'),
            'is_pinned' => (bool) $n->is_pinned,
            'created_by' => $n->creator?->name,
            'created_at' => $n->created_at?->toIso8601String(),
            'updated_at' => $n->updated_at?->toIso8601String(),
        ];
    }
}
