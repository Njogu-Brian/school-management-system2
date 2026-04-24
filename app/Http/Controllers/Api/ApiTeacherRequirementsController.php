<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RequirementTemplate;
use App\Models\Student;
use App\Models\StudentRequirement;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Teacher-facing requirement collection via mobile.
 *
 * Rules:
 *   - Continuing students (enrolled before the current term) have their
 *     `existing|both` templates received by teachers/senior teachers/admins.
 *   - New joiners (enrollment_term == current term) have their `new|both`
 *     templates received only by admin/super admin via the inventory UI.
 *     Teachers that try to access a new joiner get 403.
 */
class ApiTeacherRequirementsController extends Controller
{
    public function students(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $this->canUseApi($user)) {
            return response()->json(['success' => false, 'message' => 'Not allowed.'], 403);
        }

        $currentTerm = get_current_term_model();

        $query = Student::with(['classroom', 'stream'])
            ->where('archive', 0)
            ->where('is_alumni', false);

        if ($this->isTeacherOnly($user)) {
            $user->applyTeacherStudentFilter($query);
        }

        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', (int) $request->classroom_id);
        }
        if ($request->filled('search')) {
            $term = '%'.addcslashes($request->search, '%_\\').'%';
            $query->where(function ($q) use ($term) {
                $q->where('first_name', 'like', $term)
                  ->orWhere('last_name', 'like', $term)
                  ->orWhere('admission_number', 'like', $term);
            });
        }

        $students = $query->orderBy('first_name')->paginate(min(50, (int) $request->input('per_page', 20)));

        $students->getCollection()->transform(function (Student $s) use ($currentTerm, $user) {
            return [
                'id' => $s->id,
                'admission_number' => $s->admission_number,
                'full_name' => $s->full_name,
                'class_name' => $s->classroom?->name,
                'stream_name' => $s->stream?->name,
                'avatar' => $s->photo_url ?? null,
                'is_new_joiner' => $this->isNewJoiner($s, $currentTerm),
                // Teachers can't receive new-joiner items, so flag the UI.
                'can_teacher_receive' => ! ($this->isTeacherOnly($user) && $this->isNewJoiner($s, $currentTerm)),
            ];
        });

        return response()->json(['success' => true, 'data' => $students]);
    }

    public function templatesForStudent(Request $request, int $studentId)
    {
        $user = $request->user();
        if (! $user || ! $this->canUseApi($user)) {
            return response()->json(['success' => false, 'message' => 'Not allowed.'], 403);
        }

        $student = Student::with(['classroom', 'stream'])->findOrFail($studentId);

        if ($this->isTeacherOnly($user) && ! $user->canTeacherAccessClassroom((int) $student->classroom_id)) {
            return response()->json(['success' => false, 'message' => 'Not assigned to this student.'], 403);
        }

        $currentTerm = get_current_term_model();
        $isNewJoiner = $this->isNewJoiner($student, $currentTerm);

        if ($isNewJoiner && $this->isTeacherOnly($user)) {
            return response()->json([
                'success' => false,
                'message' => 'New-joiner requirements must be received by the admin office.',
            ], 403);
        }

        // Only show templates relevant to this student_type. Continuing => existing|both.
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
            ->get();

        $requirements = StudentRequirement::with(['requirementTemplate.requirementType'])
            ->where('student_id', $student->id)
            ->when($currentTerm, fn ($q) => $q->where('term_id', $currentTerm->id))
            ->get()
            ->keyBy('requirement_template_id');

        $items = $templates->map(function (RequirementTemplate $tpl) use ($requirements) {
            $existing = $requirements->get($tpl->id);
            return [
                'template_id' => $tpl->id,
                'requirement_id' => $existing?->id,
                'name' => $tpl->requirementType?->name ?? 'Requirement',
                'brand' => $tpl->brand,
                'unit' => $tpl->unit,
                'quantity_required' => (float) ($existing->quantity_required ?? $tpl->quantity_per_student ?? 0),
                'quantity_collected' => (float) ($existing->quantity_collected ?? 0),
                'status' => $existing->status ?? 'pending',
                'student_type' => $tpl->student_type,
                'custody_type' => $tpl->custody_type,
                'notes' => $existing->notes,
            ];
        });

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
                'items' => $items,
            ],
        ]);
    }

    public function collect(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $this->canUseApi($user)) {
            return response()->json(['success' => false, 'message' => 'Not allowed.'], 403);
        }

        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'template_id' => ['required', 'integer', 'exists:requirement_templates,id'],
            'quantity_received' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $student = Student::findOrFail($validated['student_id']);
        if ($this->isTeacherOnly($user) && ! $user->canTeacherAccessClassroom((int) $student->classroom_id)) {
            return response()->json(['success' => false, 'message' => 'Not assigned to this student.'], 403);
        }

        $currentTerm = get_current_term_model();
        $isNewJoiner = $this->isNewJoiner($student, $currentTerm);
        if ($isNewJoiner && $this->isTeacherOnly($user)) {
            return response()->json([
                'success' => false,
                'message' => 'New-joiner requirements must be received by the admin office.',
            ], 403);
        }

        $template = RequirementTemplate::findOrFail($validated['template_id']);

        // Guard: teachers can only receive templates for the right student_type.
        $allowedTypes = $isNewJoiner ? ['new', 'both'] : ['existing', 'both'];
        if (! in_array($template->student_type, $allowedTypes, true)) {
            return response()->json([
                'success' => false,
                'message' => 'This requirement does not apply to this student.',
            ], 422);
        }

        $requirement = DB::transaction(function () use ($student, $template, $currentTerm, $user, $validated) {
            $requirement = StudentRequirement::firstOrNew([
                'student_id' => $student->id,
                'requirement_template_id' => $template->id,
                'term_id' => $currentTerm?->id,
            ]);

            if (! $requirement->exists) {
                $requirement->academic_year_id = $currentTerm?->academic_year_id ?? $template->academic_year_id;
                $requirement->quantity_required = $template->quantity_per_student ?? 1;
                $requirement->expected_quantity = $template->quantity_per_student ?? 1;
                $requirement->quantity_collected = 0;
                $requirement->status = 'pending';
                $requirement->save();
            }

            $requirement->recordReceipt(
                (float) $validated['quantity_received'],
                $user->id,
                'fully_received',
                $validated['notes'] ?? null
            );
            $requirement->refresh();

            return $requirement;
        });

        return response()->json([
            'success' => true,
            'message' => 'Requirement recorded.',
            'data' => [
                'id' => $requirement->id,
                'quantity_collected' => (float) $requirement->quantity_collected,
                'status' => $requirement->status,
            ],
        ]);
    }

    private function canUseApi($user): bool
    {
        return $user->hasAnyRole([
            'Admin', 'Super Admin', 'admin', 'super admin',
            'Teacher', 'teacher', 'Senior Teacher', 'senior teacher', 'Supervisor', 'supervisor',
        ]);
    }

    private function isTeacherOnly($user): bool
    {
        return $user->hasTeacherLikeRole() && ! $user->hasAnyRole(['Admin', 'Super Admin', 'admin', 'super admin']);
    }

    private function isNewJoiner(Student $student, ?Term $currentTerm): bool
    {
        if (! $currentTerm) {
            return false;
        }
        // If enrollment_year/term aren't recorded we treat the student as continuing.
        if ($student->enrollment_year === null || $student->enrollment_term === null) {
            return false;
        }
        $currentYear = $currentTerm->academicYear?->year ?? (int) date('Y');
        // Determine numeric term order by opening_date within the year.
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
