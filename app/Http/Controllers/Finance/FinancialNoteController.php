<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinancialNote;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FinancialNoteController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'nullable|integer|exists:students,id',
            'family_id' => 'nullable|integer|exists:families,id',
        ]);

        $studentId = !empty($validated['student_id']) ? (int) $validated['student_id'] : null;
        $familyId = !empty($validated['family_id']) ? (int) $validated['family_id'] : null;

        if (!$studentId && !$familyId) {
            return response()->json(['message' => 'A student or family is required.'], 422);
        }

        if ($familyId && !$studentId) {
            $childIds = Student::query()->where('family_id', $familyId)->pluck('id');
            $notes = FinancialNote::query()
                ->with(['creator:id,name'])
                ->where(function ($q) use ($familyId, $childIds) {
                    $q->where('family_id', $familyId);
                    if ($childIds->isNotEmpty()) {
                        $q->orWhereIn('student_id', $childIds);
                    }
                })
                ->orderByDesc('is_pinned')
                ->orderByDesc('created_at')
                ->limit(50)
                ->get();
        } else {
            $notes = FinancialNote::forStudentContext($studentId, $familyId)->limit(50)->get();
        }

        return response()->json([
            'notes' => $notes->map(fn (FinancialNote $note) => $this->transform($note))->values(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'nullable|exists:students,id',
            'family_id' => 'nullable|exists:families,id',
            'body' => 'nullable|string|max:5000',
            'promise_date' => 'nullable|date',
            'is_pinned' => 'nullable|boolean',
            'redirect_to' => 'nullable|string|max:500',
        ]);

        if (empty($validated['student_id']) && empty($validated['family_id'])) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'A student or family is required for a financial note.'], 422);
            }

            return back()->with('error', 'A student or family is required for a financial note.');
        }

        if (empty($validated['body']) && empty($validated['promise_date'])) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Add a note or a promise date.'], 422);
            }

            return back()->with('error', 'Add a note or a promise date.');
        }

        if (!empty($validated['student_id']) && empty($validated['family_id'])) {
            $student = Student::withArchived()->find($validated['student_id']);
            $validated['family_id'] = $student?->family_id;
        }

        $body = trim((string) ($validated['body'] ?? ''));
        if ($body === '' && !empty($validated['promise_date'])) {
            $body = 'Promise date set from Fee Balance Report.';
        }

        $note = FinancialNote::create([
            'student_id' => $validated['student_id'] ?? null,
            'family_id' => $validated['family_id'] ?? null,
            'body' => $body,
            'promise_date' => $validated['promise_date'] ?? null,
            'is_pinned' => $request->boolean('is_pinned'),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
        $note->load('creator:id,name');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Financial note saved.',
                'note' => $this->transform($note),
            ]);
        }

        if (!empty($validated['redirect_to'])) {
            return redirect($validated['redirect_to'])->with('success', 'Financial note saved.');
        }

        return back()->with('success', 'Financial note saved.');
    }

    public function update(Request $request, FinancialNote $financialNote)
    {
        $validated = $request->validate([
            'body' => 'required|string|max:5000',
            'promise_date' => 'nullable|date',
            'is_pinned' => 'nullable|boolean',
            'redirect_to' => 'nullable|string|max:500',
        ]);

        $financialNote->update([
            'body' => $validated['body'],
            'promise_date' => $validated['promise_date'] ?? null,
            'is_pinned' => (bool) ($validated['is_pinned'] ?? $financialNote->is_pinned),
            'updated_by' => Auth::id(),
        ]);

        if (!empty($validated['redirect_to'])) {
            return redirect($validated['redirect_to'])->with('success', 'Financial note updated.');
        }

        return back()->with('success', 'Financial note updated.');
    }

    public function destroy(Request $request, FinancialNote $financialNote)
    {
        $financialNote->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Financial note removed.']);
        }

        if ($request->filled('redirect_to')) {
            return redirect($request->input('redirect_to'))->with('success', 'Financial note removed.');
        }

        return back()->with('success', 'Financial note removed.');
    }

    private function transform(FinancialNote $note): array
    {
        return [
            'id' => $note->id,
            'body' => $note->body,
            'promise_date' => $note->promise_date?->format('Y-m-d'),
            'promise_label' => $note->promise_date?->format('d M Y'),
            'is_pinned' => (bool) $note->is_pinned,
            'scope' => $note->student_id ? 'student' : 'family',
            'author' => $note->creator?->name ?? 'Staff',
            'created_at' => $note->created_at?->format('d M Y H:i'),
        ];
    }
}
