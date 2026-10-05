<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinancialNote;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FinancialNoteController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'nullable|exists:students,id',
            'family_id' => 'nullable|exists:families,id',
            'body' => 'required|string|max:5000',
            'promise_date' => 'nullable|date',
            'is_pinned' => 'nullable|boolean',
            'redirect_to' => 'nullable|string|max:500',
        ]);

        if (empty($validated['student_id']) && empty($validated['family_id'])) {
            return back()->with('error', 'A student or family is required for a financial note.');
        }

        if (!empty($validated['student_id']) && empty($validated['family_id'])) {
            $student = Student::withArchived()->find($validated['student_id']);
            $validated['family_id'] = $student?->family_id;
        }

        FinancialNote::create([
            'student_id' => $validated['student_id'] ?? null,
            'family_id' => $validated['family_id'] ?? null,
            'body' => $validated['body'],
            'promise_date' => $validated['promise_date'] ?? null,
            'is_pinned' => (bool) ($validated['is_pinned'] ?? false),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

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

        if ($request->filled('redirect_to')) {
            return redirect($request->input('redirect_to'))->with('success', 'Financial note removed.');
        }

        return back()->with('success', 'Financial note removed.');
    }
}
