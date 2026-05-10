<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\ParentInfo;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Controller for managing student families
 *
 * Handles CRUD operations for families and linking/unlinking students to families.
 */
class FamilyController extends Controller
{
    /** Parent info fields we consider when copying between siblings (blank fill / conflict resolution) */
    private const PARENT_FIELDS = [
        'father_name', 'father_phone', 'father_whatsapp', 'father_email',
        'mother_name', 'mother_phone', 'mother_whatsapp', 'mother_email',
    ];
    /**
     * Helper method to populate family details from parent info
     * Handles father, mother, and guardian separately
     *
     * @param Family $family
     * @param \App\Models\ParentInfo|null $parent
     * @return void
     */
    private function populateFamilyFromParent(Family $family, $parent)
    {
        if (!$parent) {
            return;
        }

        $updateData = [];
        
        // Father details
        if (!$family->father_name && $parent->father_name) {
            $updateData['father_name'] = $parent->father_name;
        }
        if (!$family->father_phone && $parent->father_phone) {
            $updateData['father_phone'] = $parent->father_phone;
        }
        if (!$family->father_email && $parent->father_email) {
            $updateData['father_email'] = $parent->father_email;
        }
        
        // Mother details
        if (!$family->mother_name && $parent->mother_name) {
            $updateData['mother_name'] = $parent->mother_name;
        }
        if (!$family->mother_phone && $parent->mother_phone) {
            $updateData['mother_phone'] = $parent->mother_phone;
        }
        if (!$family->mother_email && $parent->mother_email) {
            $updateData['mother_email'] = $parent->mother_email;
        }
        
        // Guardian details (fallback to father/mother if guardian not set)
        if (!$family->guardian_name || $family->guardian_name === 'New Family' || $family->guardian_name === 'Family') {
            $guardianName = $parent->guardian_name ?? $parent->father_name ?? $parent->mother_name;
            if ($guardianName) {
                $updateData['guardian_name'] = $guardianName;
            }
        }
        if (!$family->phone) {
            $phone = $parent->guardian_phone ?? $parent->father_phone ?? $parent->mother_phone;
            if ($phone) {
                $updateData['phone'] = $phone;
            }
        }
        if (!$family->email) {
            $email = $parent->guardian_email ?? $parent->father_email ?? $parent->mother_email;
            if ($email) {
                $updateData['email'] = $email;
            }
        }
        
        if (!empty($updateData)) {
            $family->update($updateData);
        }
    }

    /**
     * Merge family-level fields from all students' parents (only fill blanks).
     */
    private function mergeFamilyFromAllStudents(Family $family): void
    {
        foreach ($family->students as $student) {
            if ($student->parent) {
                $this->populateFamilyFromParent($family, $student->parent);
                $family->refresh();
            }
        }
    }

    /**
     * Merge duplicate parent_info rows for students in the same family when they share a contact
     * (same phone digits or same email). Chooses the richest row as canonical, merges non-empty fields
     * (prefers longer text on conflicts), repoints all dependents, then deletes unused rows.
     */
    private function consolidateDuplicateParentsInFamily(int $familyId): void
    {
        $parentIds = Student::query()
            ->where('family_id', $familyId)
            ->whereNotNull('parent_id')
            ->distinct()
            ->pluck('parent_id');

        if ($parentIds->count() < 2) {
            return;
        }

        /** @var Collection<int, ParentInfo> $parents */
        $parents = ParentInfo::query()->whereIn('id', $parentIds)->get()->keyBy('id');
        if ($parents->count() < 2) {
            return;
        }

        $ids = $parents->keys()->values()->all();
        $adj = [];
        foreach ($ids as $id) {
            $adj[$id] = [];
        }

        foreach ($ids as $id1) {
            foreach ($ids as $id2) {
                if ($id1 >= $id2) {
                    continue;
                }
                $p1 = $parents->get($id1);
                $p2 = $parents->get($id2);
                if ($p1 && $p2 && $this->parentsShareAnyContact($p1, $p2)) {
                    $adj[$id1][] = $id2;
                    $adj[$id2][] = $id1;
                }
            }
        }

        $visited = [];
        foreach ($ids as $startId) {
            if (isset($visited[$startId])) {
                continue;
            }
            $stack = [$startId];
            $comp = [];
            while ($stack !== []) {
                $cur = array_pop($stack);
                if (isset($visited[$cur])) {
                    continue;
                }
                $visited[$cur] = true;
                $comp[] = $cur;
                foreach ($adj[$cur] as $nbr) {
                    if (! isset($visited[$nbr])) {
                        $stack[] = $nbr;
                    }
                }
            }

            if (count($comp) < 2) {
                continue;
            }

            $cluster = collect($comp)->map(fn ($id) => $parents->get($id))->filter();
            $winner = $this->pickCanonicalParent($cluster);
            $losers = $cluster->filter(fn (ParentInfo $p) => $p->id !== $winner->id);
            $merged = $this->mergeParentAttributes($winner, $losers);

            $winner->fill($merged);
            $winner->save();

            $removeIds = $losers->pluck('id')->values()->all();
            if ($removeIds === []) {
                continue;
            }

            DB::table('students')->whereIn('parent_id', $removeIds)->update(['parent_id' => $winner->id]);
            DB::table('users')->whereIn('parent_id', $removeIds)->update(['parent_id' => $winner->id]);
            DB::table('pos_orders')->whereIn('parent_id', $removeIds)->update(['parent_id' => $winner->id]);

            foreach ($removeIds as $pid) {
                $stillUsed = DB::table('students')->where('parent_id', $pid)->exists()
                    || DB::table('users')->where('parent_id', $pid)->exists()
                    || DB::table('pos_orders')->where('parent_id', $pid)->exists();
                if (! $stillUsed) {
                    DB::table('parent_info')->where('id', $pid)->delete();
                }
            }
        }
    }

    private function parentsShareAnyContact(ParentInfo $a, ParentInfo $b): bool
    {
        $ka = $this->parentContactKeys($a);
        $kb = $this->parentContactKeys($b);
        foreach ($ka as $token => $_) {
            if (isset($kb[$token])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, true>
     */
    private function parentContactKeys(ParentInfo $p): array
    {
        $keys = [];
        foreach (['father_phone', 'mother_phone', 'guardian_phone', 'father_whatsapp', 'mother_whatsapp', 'guardian_whatsapp'] as $field) {
            $digits = $this->phoneDigitCore($p->getAttribute($field));
            if ($digits !== null) {
                $keys['p:'.$digits] = true;
            }
        }
        foreach (['father_email', 'mother_email', 'guardian_email'] as $field) {
            $e = strtolower(trim((string) ($p->getAttribute($field) ?? '')));
            if ($e !== '') {
                $keys['e:'.$e] = true;
            }
        }

        return $keys;
    }

    private function phoneDigitCore(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);
        if ($digits === '') {
            return null;
        }

        return strlen($digits) >= 9 ? $digits : null;
    }

    /**
     * @param  Collection<int, ParentInfo>  $cluster
     */
    private function pickCanonicalParent(Collection $cluster): ParentInfo
    {
        return $cluster->sort(function (ParentInfo $a, ParentInfo $b) {
            $sa = $this->parentNonEmptyFieldCount($a);
            $sb = $this->parentNonEmptyFieldCount($b);
            if ($sa !== $sb) {
                return $sb <=> $sa;
            }

            return $a->id <=> $b->id;
        })->first();
    }

    private function parentNonEmptyFieldCount(ParentInfo $p): int
    {
        $n = 0;
        foreach ($p->getFillable() as $field) {
            if (filled(trim((string) ($p->getAttribute($field) ?? '')))) {
                $n++;
            }
        }

        return $n;
    }

    /**
     * @param  Collection<int, ParentInfo>  $losers
     * @return array<string, mixed>
     */
    private function mergeParentAttributes(ParentInfo $winner, Collection $losers): array
    {
        $fields = $winner->getFillable();
        $attrs = [];
        foreach ($fields as $field) {
            $attrs[$field] = $winner->getAttribute($field);
        }

        foreach ($losers as $loser) {
            foreach ($fields as $field) {
                $incoming = $loser->getAttribute($field);
                if ($incoming === null || trim((string) $incoming) === '') {
                    continue;
                }
                $incomingStr = trim((string) $incoming);
                $current = $attrs[$field] ?? null;
                $currentStr = trim((string) ($current ?? ''));

                if ($currentStr === '') {
                    $attrs[$field] = $incomingStr;

                    continue;
                }

                if ($this->fieldValuesEquivalent($field, $currentStr, $incomingStr)) {
                    continue;
                }

                if ($field === 'school_notifications_muted_parent') {
                    continue;
                }

                if (strlen($incomingStr) > strlen($currentStr)) {
                    $attrs[$field] = $incomingStr;
                }
            }
        }

        return $attrs;
    }

    private function fieldValuesEquivalent(string $field, string $a, string $b): bool
    {
        if (str_ends_with($field, '_email')) {
            return strtolower($a) === strtolower($b);
        }
        if (str_contains($field, 'phone') || str_contains($field, 'whatsapp')) {
            return $this->phoneDigitCore($a) !== null
                && $this->phoneDigitCore($b) !== null
                && $this->phoneDigitCore($a) === $this->phoneDigitCore($b);
        }

        return $a === $b;
    }

    /**
     * Build proposed changes and conflicts for "Fix blank fields" across all families.
     * Returns [ 'familyFills' => [], 'studentFills' => [], 'conflicts' => [] ].
     *
     * @return array{familyFills: array, studentFills: array, conflicts: array}
     */
    private function buildPopulatePreview(): array
    {
        $families = Family::with(['students.parent'])->get();
        $familyFills = [];
        $studentFills = [];
        $conflicts = [];

        foreach ($families as $family) {
            $students = $family->students->filter(fn ($s) => $s->parent);
            if ($students->isEmpty()) {
                continue;
            }

            // Family-level: which fields would get filled from which student (we merge from all; for preview we just note "from siblings")
            $familyBefore = $family->only(['guardian_name', 'phone', 'email', 'father_name', 'father_phone', 'father_email', 'mother_name', 'mother_phone', 'mother_email']);
            $merged = $familyBefore;
            foreach ($students as $student) {
                $p = $student->parent;
                $candidates = [
                    'guardian_name' => $p->guardian_name ?? $p->father_name ?? $p->mother_name,
                    'phone' => $p->guardian_phone ?? $p->father_phone ?? $p->mother_phone,
                    'email' => $p->guardian_email ?? $p->father_email ?? $p->mother_email,
                    'father_name' => $p->father_name,
                    'father_phone' => $p->father_phone,
                    'father_email' => $p->father_email,
                    'mother_name' => $p->mother_name,
                    'mother_phone' => $p->mother_phone,
                    'mother_email' => $p->mother_email,
                ];
                foreach ($candidates as $key => $value) {
                    if (!empty($value) && (empty($merged[$key]) || $merged[$key] === 'Family' || $merged[$key] === 'New Family')) {
                        $merged[$key] = $value;
                    }
                }
            }
            $familyChanges = [];
            foreach ($merged as $key => $value) {
                if (!empty($value) && (empty($familyBefore[$key]) || $familyBefore[$key] === 'Family' || $familyBefore[$key] === 'New Family')) {
                    $familyChanges[$key] = $value;
                }
            }
            if (!empty($familyChanges)) {
                $familyFills[] = ['family' => $family, 'changes' => $familyChanges];
            }

            // Per-student parent fields: blanks we can fill from a sibling, and conflicts (different values)
            foreach (self::PARENT_FIELDS as $field) {
                $valuesByStudent = [];
                foreach ($students as $student) {
                    $v = $student->parent->getAttribute($field);
                    $valuesByStudent[$student->id] = $v;
                }
                $uniqueValues = array_unique(array_filter(array_map('trim', $valuesByStudent)));
                $uniqueValues = array_filter($uniqueValues, fn ($x) => $x !== '');
                if (count($uniqueValues) > 1) {
                    // Conflict: different non-empty values
                    $conflicts[] = [
                        'family' => $family,
                        'field' => $field,
                        'students' => $students->keyBy('id'),
                        'values' => $valuesByStudent,
                    ];
                } else {
                    // No conflict: fill blanks from first available
                    $fillValue = null;
                    foreach ($valuesByStudent as $v) {
                        if (!empty(trim((string) $v))) {
                            $fillValue = $v;
                            break;
                        }
                    }
                    if ($fillValue !== null) {
                        foreach ($students as $student) {
                            $current = $student->parent->getAttribute($field);
                            if (empty(trim((string) $current))) {
                                $studentFills[] = [
                                    'family' => $family,
                                    'student' => $student,
                                    'field' => $field,
                                    'value' => $fillValue,
                                ];
                            }
                        }
                    }
                }
            }
        }

        return [
            'familyFills' => $familyFills,
            'studentFills' => $studentFills,
            'conflicts' => $conflicts,
        ];
    }

    /**
     * Show preview of "Fix blank fields" and optionally resolve conflicts.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function populatePreview()
    {
        $preview = $this->buildPopulatePreview();
        $hasAny = !empty($preview['familyFills']) || !empty($preview['studentFills']) || !empty($preview['conflicts']);
        if (!$hasAny) {
            return redirect()->route('families.index')
                ->with('info', 'No blank fields or conflicts found. All family and parent records are already filled or consistent.');
        }
        return view('families.populate-preview', $preview);
    }

    /**
     * Apply "Fix blank fields" with optional conflict resolutions.
     * Resolutions: request key "resolutions" => [ "familyId_field" => studentId (use this student's value) ]
     * or "familyId_field" => "keep" to leave as is.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function populateFromPreview(Request $request)
    {
        $resolutions = $request->input('resolutions', []);
        if (!is_array($resolutions)) {
            $resolutions = [];
        }

        $families = Family::with(['students.parent'])->get();
        $familyUpdated = 0;
        $parentUpdated = 0;

        foreach ($families as $family) {
            $beforeFamily = $family->only(['guardian_name', 'phone', 'email', 'father_name', 'father_phone', 'father_email', 'mother_name', 'mother_phone', 'mother_email']);
            // 1. Merge family from all students (fill blanks only)
            $this->mergeFamilyFromAllStudents($family);
            $family->refresh();
            $afterFamily = $family->only(array_keys($beforeFamily));
            if ($beforeFamily !== $afterFamily) {
                $familyUpdated++;
            }

            $students = $family->students->filter(fn ($s) => $s->parent);
            if ($students->isEmpty()) {
                continue;
            }

            // 2. For each parent field: apply resolution or fill blanks from first sibling
            foreach (self::PARENT_FIELDS as $field) {
                $valuesByStudent = [];
                foreach ($students as $student) {
                    $valuesByStudent[$student->id] = trim((string) $student->parent->getAttribute($field));
                }
                $uniqueNonEmpty = array_unique(array_filter($valuesByStudent));
                $resolutionKey = $family->id . '_' . $field;
                $chosenStudentId = $resolutions[$resolutionKey] ?? null;
                if ($chosenStudentId === 'keep' || $chosenStudentId === '') {
                    // Leave conflicting values as is; still fill blanks from first available
                    $chosenStudentId = null;
                }
                if (count($uniqueNonEmpty) > 1 && $chosenStudentId) {
                    $sourceStudent = $students->firstWhere('id', (int) $chosenStudentId);
                    $valueToApply = $sourceStudent ? trim((string) $sourceStudent->parent->getAttribute($field)) : null;
                    if ($valueToApply !== null && $valueToApply !== '') {
                        foreach ($students as $student) {
                            $current = trim((string) $student->parent->getAttribute($field));
                            if ($current !== $valueToApply) {
                                $student->parent->setAttribute($field, $valueToApply);
                                $student->parent->save();
                                $parentUpdated++;
                            }
                        }
                    }
                } else {
                    // No conflict or no resolution: fill blanks from first non-empty
                    $fillValue = null;
                    foreach ($students as $student) {
                        $v = trim((string) $student->parent->getAttribute($field));
                        if ($v !== '') {
                            $fillValue = $v;
                            break;
                        }
                    }
                    if ($fillValue !== null) {
                        foreach ($students as $student) {
                            $current = trim((string) $student->parent->getAttribute($field));
                            if ($current === '') {
                                $student->parent->setAttribute($field, $fillValue);
                                $student->parent->save();
                                $parentUpdated++;
                            }
                        }
                    }
                }
            }
        }

        $message = 'Fix blank fields applied.';
        if ($familyUpdated) {
            $message .= " Family records updated.";
        }
        if ($parentUpdated) {
            $message .= " {$parentUpdated} parent field(s) updated.";
        }
        return redirect()->route('families.index')->with('success', trim($message));
    }

    /**
     * List all families with optional search
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $families = Family::query()
            ->with(['students.parent']) // Load students and their parents
            ->when($q !== '', function ($familyQuery) use ($q) {
                $searchTerm = '%'.addcslashes($q, '%_\\').'%';
                $familyQuery->where(function ($w) use ($searchTerm) {
                    $w->where('guardian_name', 'like', $searchTerm)
                        ->orWhere('phone', 'like', $searchTerm)
                        ->orWhere('email', 'like', $searchTerm)
                        ->orWhereHas('students', function ($sq) use ($searchTerm) {
                            $sq->where(function ($st) use ($searchTerm) {
                                $st->where('first_name', 'like', $searchTerm)
                                    ->orWhere('last_name', 'like', $searchTerm)
                                    ->orWhere('admission_number', 'like', $searchTerm);
                            });
                        });
                });
            })
            ->withCount('students')
            ->orderByDesc('students_count')
            ->paginate(20)
            ->withQueryString();

        // Auto-populate family details from any sibling's parent (fill blanks only)
        foreach ($families as $family) {
            $this->mergeFamilyFromAllStudents($family);
        }
        $families->load(['students.parent']);

        return view('families.index', compact('families','q'));
    }

    /**
     * Show form to link two students as siblings
     *
     * @return \Illuminate\View\View
     */
    public function link()
    {
        return view('families.link');
    }

    /**
     * Link multiple students as siblings (creates or merges families as needed).
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function linkStudents(Request $request)
    {
        $data = $request->validate([
            // New multi-select flow
            'student_ids' => 'required_without_all:student_a_id,student_b_id|array|min:2|max:40',
            'student_ids.*' => 'distinct|exists:students,id',
            // Backward compatibility for old two-field form
            'student_a_id' => 'required_without:student_ids|exists:students,id',
            'student_b_id' => 'required_without:student_ids|exists:students,id|different:student_a_id',
        ]);

        // Normalize selected student IDs (supports legacy two-field payload)
        if (!empty($data['student_ids'])) {
            $studentIds = array_values(array_unique(array_map('intval', $data['student_ids'])));
        } else {
            $studentIds = [
                (int)$data['student_a_id'],
                (int)$data['student_b_id'],
            ];
        }
        
        // Fetch students with parents (includes archived)
        $students = Student::withArchived()->with('parent')->whereIn('id', $studentIds)->get();
        if ($students->count() !== count($studentIds)) {
            return back()->withErrors(['student_ids' => 'Some selected students could not be found.']);
        }

        $family = DB::transaction(function () use ($students) {
            $family = null;
            $familiesToMerge = [];

            // Decide which family to attach to (reuse if any selected student already has one)
            foreach ($students as $student) {
                if ($student->family_id) {
                    if (!$family) {
                        $family = Family::find($student->family_id);
                    }
                    $familiesToMerge[] = $student->family_id;
                }
            }

            $familiesToMerge = array_unique($familiesToMerge);

            if (!$family) {
                $family = Family::create(['guardian_name' => 'Family']);
            }

            // Pick a parent record (if any) to enrich the family details
            $parentWithData = $students->first(fn ($stu) => $stu->parent);
            if ($parentWithData && $parentWithData->parent) {
                $this->populateFamilyFromParent($family, $parentWithData->parent);
            }

            // Link selected students to the chosen family
            Student::whereIn('id', $students->pluck('id'))->update(['family_id' => $family->id]);

            // Merge other families into the chosen one (must happen before parent consolidation)
            foreach ($familiesToMerge as $familyId) {
                if ($familyId == $family->id) {
                    continue;
                }
                $oldFamily = Family::find($familyId);
                if ($oldFamily) {
                    Student::where('family_id', $oldFamily->id)->update(['family_id' => $family->id]);
                    $oldFamily->delete();
                }
            }

            // Merge duplicate parent_info rows that share a phone/email into one record (rich-field merge)
            $this->consolidateDuplicateParentsInFamily($family->id);
            ensure_family_payment_link($family->id);

            return $family;
        });

        $successMsg = 'Students linked as siblings. Duplicate parent contacts were merged where possible.';
        if ($request->input('link_context') === 'integrity_report') {
            $qs = array_filter([
                'dup_limit' => $request->input('dup_limit'),
                'page' => $request->input('page'),
                'q' => $request->input('q'),
            ], fn ($v) => $v !== null && $v !== '');

            return redirect()->route('families.integrity-report', $qs)->with('success', $successMsg);
        }

        return redirect()->route('families.manage', $family)
            ->with('success', $successMsg);
    }

    /**
     * Manage a family (view and edit family details and members)
     *
     * @param Family $family
     * @return \Illuminate\View\View
     */
    public function manage(Family $family)
    {
        $family->load(['students.classroom','students.stream','students.parent','updateLink']);
        // Auto-populate family details from any sibling's parent (fill blanks only)
        $this->mergeFamilyFromAllStudents($family);
        $family->refresh();
        $family->load(['students.classroom','students.stream','students.parent','updateLink']);
        // Get parent info from first student if available (for display)
        $parentInfo = $family->students->first()?->parent;
        
        return view('families.manage', compact('family', 'parentInfo'));
    }

    /**
     * Update family details (optional - guardian details can be edited if needed)
     *
     * @param Request $request
     * @param Family $family
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Family $family)
    {
        $data = $request->validate([
            'guardian_name' => 'nullable|string|max:255',
            'father_name'   => 'nullable|string|max:255',
            'mother_name'   => 'nullable|string|max:255',
            'phone'         => 'nullable|string|max:255',
            'father_phone' => 'nullable|string|max:255',
            'mother_phone'  => 'nullable|string|max:255',
            'email'         => 'nullable|email|max:255',
            'father_email'  => 'nullable|email|max:255',
            'mother_email'  => 'nullable|email|max:255',
        ]);
        $family->update(array_filter($data)); // Only update provided fields
        return back()->with('success','Family details updated.');
    }

    /**
     * Attach a student to a family
     * 
     * If student already has a family, all siblings from that family will be merged into this family.
     * Auto-populates family guardian details from student's parent info if family is empty.
     *
     * @param Request $request
     * @param Family $family
     * @return \Illuminate\Http\RedirectResponse
     */
    public function attachMember(Request $request, Family $family)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id'
        ]);
        $student = Student::withArchived()->with('parent')->findOrFail($data['student_id']);
        
        // Auto-populate family details from student's parent if family is empty or has default values
        if ($student->parent) {
            $this->populateFamilyFromParent($family, $student->parent);
        }
        
        // If student already belongs to another family, merge all siblings
        if ($student->family_id && $student->family_id != $family->id) {
            $oldFamily = Family::find($student->family_id);
            // Move all students from old family to new family
            Student::where('family_id', $oldFamily->id)->update(['family_id' => $family->id]);
            // Delete old family if it has no other data to preserve
            $oldFamily->delete();
        } else {
            // Simply attach the student
            $student->update(['family_id' => $family->id]);
        }

        $this->consolidateDuplicateParentsInFamily($family->id);
        ensure_family_payment_link($family->id);

        return back()->with('success', 'Student linked to family as sibling.');
    }

    /**
     * Detach a student from a family
     *
     * @param Request $request
     * @param Family $family
     * @return \Illuminate\Http\RedirectResponse
     */
    public function detachMember(Request $request, Family $family)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id'
        ]);
        $student = Student::withArchived()->findOrFail($data['student_id']);

        // Only detach if they belong to this family
        if ($student->family_id == $family->id) {
            $student->update(['family_id' => null]);
            // No family may have only one child: if this family now has one child left, remove it
            if ($family->students()->count() === 1) {
                $last = $family->students()->first();
                if ($last) {
                    $last->update(['family_id' => null]);
                }
                \App\Models\FamilyUpdateLink::where('family_id', $family->id)->delete();
                \App\Models\PaymentLink::where('family_id', $family->id)->whereNull('student_id')->update(['status' => 'expired']);
                $family->delete();
            }
        }
        return back()->with('success', 'Student removed from family.');
    }

    /**
     * Delete a family (unlinks all students first)
     *
     * @param Family $family
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Family $family)
    {
        // Unlink all students from this family
        Student::where('family_id', $family->id)->update(['family_id' => null]);
        
        // Delete the family
        $family->delete();
        
        return redirect()->route('families.index')
            ->with('success', 'Family deleted successfully. All students have been unlinked.');
    }

    /**
     * Bulk delete families (unlinks all students from each family, then deletes)
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:families,id',
        ]);

        $ids = $request->input('ids', []);
        $count = 0;

        foreach ($ids as $id) {
            $family = Family::find($id);
            if ($family) {
                Student::where('family_id', $family->id)->update(['family_id' => null]);
                $family->delete();
                $count++;
            }
        }

        $message = $count === 1
            ? '1 family deleted. All students have been unlinked.'
            : $count . ' families deleted. All students have been unlinked.';

        return redirect()->route('families.index')->with('success', $message);
    }
}
