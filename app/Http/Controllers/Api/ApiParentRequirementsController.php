<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RequirementTemplate;
use App\Models\Student;
use App\Models\StudentRequirement;
use App\Models\Term;
use Illuminate\Http\Request;

/**
 * Parent read-only view of class requirements / what has been brought.
 */
class ApiParentRequirementsController extends Controller
{
    public function show(Request $request, Student $student)
    {
        $user = $request->user();
        if (! $user || ! $user->canAccessStudent((int) $student->id)) {
            return response()->json(['success' => false, 'message' => 'Not allowed.'], 403);
        }

        $student->load(['classroom', 'stream']);
        $currentTerm = get_current_term_model();
        $isNewJoiner = $this->isNewJoiner($student, $currentTerm);
        $studentTypes = $isNewJoiner ? ['new', 'both'] : ['existing', 'both'];

        $templates = RequirementTemplate::with(['requirementType'])
            ->where('is_active', true)
            ->whereIn('student_type', $studentTypes)
            ->where(function ($q) use ($student) {
                $q->where('classroom_id', $student->classroom_id)
                  ->orWhereHas('classrooms', function ($qq) use ($student) {
                      $qq->where('classrooms.id', $student->classroom_id);
                  })
                  ->orWhereNull('classroom_id');
            })
            ->when($currentTerm, fn ($q) => $q->where(function ($qq) use ($currentTerm) {
                $qq->where('term_id', $currentTerm->id)->orWhereNull('term_id');
            }))
            ->orderBy('id')
            ->get();

        $requirements = StudentRequirement::query()
            ->where('student_id', $student->id)
            ->when($currentTerm, fn ($q) => $q->where('term_id', $currentTerm->id))
            ->get()
            ->keyBy('requirement_template_id');

        $items = $templates->map(function (RequirementTemplate $tpl) use ($requirements) {
            /** @var StudentRequirement|null $existing */
            $existing = $requirements->get($tpl->id);
            $required = (float) ($existing?->expected_quantity
                ?? $existing?->quantity_required
                ?? $tpl->quantity_per_student
                ?? 0);
            $brought = (float) ($existing?->quantity_collected ?? 0);
            $outstanding = max(0, $required - $brought);
            $status = $existing?->status ?? 'pending';
            if ($brought <= 0 && $required > 0) {
                $status = 'pending';
            } elseif ($outstanding <= 0 && $required > 0) {
                $status = 'complete';
            } elseif ($brought > 0 && $outstanding > 0) {
                $status = 'partial';
            }

            return [
                'template_id' => $tpl->id,
                'requirement_id' => $existing?->id,
                'name' => $tpl->requirementType?->name ?? 'Requirement',
                'brand' => $tpl->brand,
                'unit' => $tpl->unit,
                'quantity_required' => $required,
                'quantity_collected' => $brought,
                'quantity_outstanding' => $outstanding,
                'status' => $status,
                'student_type' => $tpl->student_type,
                'custody_type' => $tpl->custody_type,
                'is_verification_only' => (bool) $tpl->is_verification_only,
                'adds_to_inventory' => $tpl->addsToSchoolInventory(),
                'notes' => $existing?->notes,
                'last_received_at' => optional($existing?->last_received_at)?->toIso8601String()
                    ?? optional($existing?->collected_at)?->toIso8601String(),
            ];
        })->values();

        $broughtCount = $items->filter(fn ($i) => $i['quantity_collected'] > 0)->count();
        $completeCount = $items->filter(fn ($i) => $i['status'] === 'complete')->count();
        $outstandingCount = $items->filter(fn ($i) => $i['quantity_outstanding'] > 0)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'student' => [
                    'id' => $student->id,
                    'full_name' => $student->full_name,
                    'admission_number' => $student->admission_number,
                    'class_name' => $student->classroom?->name,
                    'is_new_joiner' => $isNewJoiner,
                ],
                'current_term' => $currentTerm ? [
                    'id' => $currentTerm->id,
                    'name' => $currentTerm->name,
                ] : null,
                'summary' => [
                    'total' => $items->count(),
                    'brought' => $broughtCount,
                    'complete' => $completeCount,
                    'outstanding' => $outstandingCount,
                ],
                'items' => $items,
            ],
        ]);
    }

    private function isNewJoiner(Student $student, ?Term $currentTerm): bool
    {
        if (! $currentTerm) {
            return false;
        }
        if ($student->enrollment_year === null || $student->enrollment_term === null) {
            return false;
        }
        $currentYear = $currentTerm->academicYear?->year ?? (int) date('Y');
        $currentTermNumber = $this->termNumber($currentTerm);

        return (int) $student->enrollment_year === (int) $currentYear
            && (int) $student->enrollment_term === (int) $currentTermNumber;
    }

    private function termNumber(Term $term): int
    {
        static $cache = [];
        $key = $term->academic_year_id;
        if (! isset($cache[$key])) {
            $cache[$key] = Term::where('academic_year_id', $term->academic_year_id)
                ->orderBy('opening_date')
                ->pluck('id')
                ->toArray();
        }
        $idx = array_search($term->id, $cache[$key], true);

        return $idx === false ? 1 : ($idx + 1);
    }
}
