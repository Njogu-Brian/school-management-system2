<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Controller for managing student families
 *
 * Handles CRUD operations for families and linking/unlinking students to families.
 */
class FamilyController extends Controller
{
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
     * List all families with optional search
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $q = trim((string)$request->input('q', ''));
        $families = Family::query()
            ->with(['students.parent']) // Load students and their parents
            ->when($q, function($f) use ($q){
                $searchTerm = '%' . addcslashes($q, '%_\\') . '%';
                $f->where('guardian_name','like', $searchTerm)
                  ->orWhere('phone','like', $searchTerm)
                  ->orWhere('email','like', $searchTerm);
            })
            ->withCount('students')
            ->orderByDesc('students_count')
            ->paginate(20)
            ->withQueryString();

        // Auto-populate family details for families that are empty or have default values
        foreach ($families as $family) {
            $firstStudent = $family->students->first();
            if ($firstStudent && $firstStudent->parent) {
                $this->populateFamilyFromParent($family, $firstStudent->parent);
            }
        }
        
        // Refresh to show updated values
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
     * Link 2-4 students as siblings (creates family if needed)
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function linkStudents(Request $request)
    {
        $data = $request->validate([
            // New multi-select flow
            'student_ids' => 'required_without_all:student_a_id,student_b_id|array|min:2|max:4',
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

            // Merge other families into the chosen one
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

            return $family;
        });

        return redirect()->route('families.manage', $family)
            ->with('success', 'Students linked as siblings successfully.');
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
        
        // Auto-populate family details from students' parent info if empty
        $firstStudent = $family->students->first();
        if ($firstStudent && $firstStudent->parent) {
            $this->populateFamilyFromParent($family, $firstStudent->parent);
            $family->refresh(); // Reload to show updated values
            $family->load(['students.classroom','students.stream','students.parent','updateLink']);
        }
        
        // Get parent info from first student if available
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
        }
        return back()->with('success', 'Student removed from family.');
    }

    /**
     * Auto-populate all families with blank/default values from their students' parent info
     * This is a one-time fix for existing families
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function populateAllFamilies()
    {
        $families = Family::with(['students.parent'])->get();
        $updated = 0;

        foreach ($families as $family) {
            $firstStudent = $family->students->first();
            if ($firstStudent && $firstStudent->parent) {
                $beforeUpdate = [
                    'guardian_name' => $family->guardian_name,
                    'father_name' => $family->father_name,
                    'mother_name' => $family->mother_name,
                ];
                $this->populateFamilyFromParent($family, $firstStudent->parent);
                $family->refresh();
                // Check if anything changed
                if ($beforeUpdate['guardian_name'] !== $family->guardian_name || 
                    $beforeUpdate['father_name'] !== $family->father_name || 
                    $beforeUpdate['mother_name'] !== $family->mother_name) {
                    $updated++;
                }
            }
        }

        return redirect()->route('families.index')
            ->with('success', "Updated {$updated} families with parent information.");
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
}
