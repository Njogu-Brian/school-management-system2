<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Subject;
use App\Models\Academics\SubjectGroup;
use App\Models\Academics\Classroom;
use App\Models\Academics\ClassroomSubject;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:subjects.view')->only(['index', 'show']);
        $this->middleware('permission:subjects.create')->only(['create', 'store', 'generateCBCSubjects', 'assignToClassrooms']);
        $this->middleware('permission:subjects.edit')->only(['edit', 'update', 'updateLessonsPerWeek']);
        $this->middleware('permission:subjects.delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $query = Subject::with(['group', 'classroomSubjects.classroom', 'teachers'])
            ->withCount(['classroomSubjects', 'teachers']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('learning_area', 'like', "%{$search}%")
                  ->orWhereHas('group', function($subQ) use ($search) {
                      $subQ->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filters
        if ($request->filled('group_id')) {
            $query->where('subject_group_id', $request->group_id);
        }

        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        if ($request->filled('is_optional')) {
            $query->where('is_optional', $request->is_optional === '1');
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active === '1');
        }

        $subjects = $query->orderBy('name')->paginate(20)->withQueryString();

        $groups = SubjectGroup::active()->ordered()->get();
        $levels = Subject::distinct()->whereNotNull('level')->pluck('level')->sort();

        return view('academics.subjects.index', compact('subjects', 'groups', 'levels'));
    }

    public function create()
    {
        $groups = SubjectGroup::active()->ordered()->get();
        $classrooms = Classroom::orderBy('name')->get();
        $teachers = Staff::whereHas('user.roles', fn($q) => $q->whereIn('name', ['Teacher', 'teacher']))->get();
        $years = AcademicYear::orderByDesc('year')->get();
        $terms = Term::orderBy('name')->get();

        return view('academics.subjects.create', compact('groups', 'classrooms', 'teachers', 'years', 'terms'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:subjects,code',
            'name' => 'required|string|max:255',
            'subject_group_id' => 'nullable|exists:subject_groups,id',
            'learning_area' => 'nullable|string|max:255',
            'level' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'is_optional' => 'boolean',
        ]);

        $subject = Subject::create($validated);

        // Handle classroom assignments with detailed information
        if ($request->filled('classroom_assignments')) {
            foreach ($request->classroom_assignments as $assignment) {
                ClassroomSubject::updateOrCreate(
                    [
                        'classroom_id' => $assignment['classroom_id'],
                        'subject_id' => $subject->id,
                        'stream_id' => $assignment['stream_id'] ?? null,
                        'academic_year_id' => $assignment['academic_year_id'] ?? null,
                        'term_id' => $assignment['term_id'] ?? null,
                    ],
                    [
                        'staff_id' => $assignment['staff_id'] ?? null,
                        'is_compulsory' => $assignment['is_compulsory'] ?? !$subject->is_optional,
                    ]
                );
            }
        }

        return redirect()
            ->route('academics.subjects.index')
            ->with('success', 'Subject created successfully.');
    }

    public function show(Subject $subject)
    {
        $subject->load([
            'group',
            'classrooms',
            'teachers',
            'classroomSubjects.classroom',
            'classroomSubjects.teacher'
        ]);

        return view('academics.subjects.show', compact('subject'));
    }

    public function edit(Subject $subject)
    {
        $groups = SubjectGroup::active()->ordered()->get();
        $classrooms = Classroom::orderBy('name')->get();
        $teachers = Staff::whereHas('user.roles', fn($q) => $q->whereIn('name', ['Teacher', 'teacher']))->get();
        $years = AcademicYear::orderByDesc('year')->get();
        $terms = Term::orderBy('name')->get();

        $classroomAssignments = $subject->classroomSubjects()
            ->with(['classroom', 'teacher'])
            ->get();

        return view('academics.subjects.edit', compact(
            'subject',
            'groups',
            'classrooms',
            'teachers',
            'years',
            'terms',
            'classroomAssignments'
        ));
    }

    public function update(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:subjects,code,' . $subject->id,
            'name' => 'required|string|max:255',
            'subject_group_id' => 'nullable|exists:subject_groups,id',
            'learning_area' => 'nullable|string|max:255',
            'level' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'is_optional' => 'boolean',
        ]);

        $subject->update($validated);

        // Handle classroom assignments
        if ($request->filled('classroom_assignments')) {
            // Delete existing assignments not in the new list
            $newAssignmentIds = collect($request->classroom_assignments)
                ->pluck('id')
                ->filter()
                ->toArray();

            $subject->classroomSubjects()
                ->whereNotIn('id', $newAssignmentIds)
                ->delete();

            // Update or create assignments
            foreach ($request->classroom_assignments as $assignment) {
                if (isset($assignment['id']) && $assignment['id']) {
                    $classroomSubject = ClassroomSubject::find($assignment['id']);
                    if ($classroomSubject) {
                        $classroomSubject->update([
                            'classroom_id' => $assignment['classroom_id'],
                            'stream_id' => $assignment['stream_id'] ?? null,
                            'staff_id' => $assignment['staff_id'] ?? null,
                            'academic_year_id' => $assignment['academic_year_id'] ?? null,
                            'term_id' => $assignment['term_id'] ?? null,
                            'is_compulsory' => $assignment['is_compulsory'] ?? !$subject->is_optional,
                        ]);
                    }
                } else {
                    ClassroomSubject::create([
                        'classroom_id' => $assignment['classroom_id'],
                        'subject_id' => $subject->id,
                        'stream_id' => $assignment['stream_id'] ?? null,
                        'staff_id' => $assignment['staff_id'] ?? null,
                        'academic_year_id' => $assignment['academic_year_id'] ?? null,
                        'term_id' => $assignment['term_id'] ?? null,
                        'is_compulsory' => $assignment['is_compulsory'] ?? !$subject->is_optional,
                    ]);
                }
            }
        }

        return redirect()
            ->route('academics.subjects.index')
            ->with('success', 'Subject updated successfully.');
    }

    public function destroy(Subject $subject)
    {
        // Check if subject has marks or exams
        if ($subject->classroomSubjects()->exists()) {
            return back()
                ->with('error', 'Cannot delete subject with existing classroom assignments. Remove assignments first.');
        }

        $subject->delete();

        return redirect()
            ->route('academics.subjects.index')
            ->with('success', 'Subject deleted successfully.');
    }

    /**
     * Generate CBC/CBE Kenyan subjects
     */
    public function generateCBCSubjects(Request $request)
    {
        $validated = $request->validate([
            'level' => 'required|in:PP1,PP2,Grade 1,Grade 2,Grade 3,Grade 4,Grade 5,Grade 6,Grade 7,Grade 8,Grade 9',
            'assign_to_classrooms' => 'nullable|boolean',
            'classroom_ids' => 'nullable|array',
            'classroom_ids.*' => 'exists:classrooms,id',
        ]);

        $level = $validated['level'];
        $assignToClassrooms = $request->boolean('assign_to_classrooms', false);
        $subjects = $this->getCBCSubjectsForLevel($level);

        DB::beginTransaction();
        try {
            $createdSubjects = [];
            $subjectGroup = SubjectGroup::firstOrCreate(
                ['code' => 'CBC'],
                ['name' => 'CBC Subjects', 'display_order' => 1, 'is_active' => true]
            );

            foreach ($subjects as $subjectData) {
                $subject = Subject::firstOrCreate(
                    [
                        'code' => $subjectData['code'],
                    ],
                    [
                        'name' => $subjectData['name'],
                        'subject_group_id' => $subjectGroup->id,
                        'learning_area' => $subjectData['learning_area'] ?? null,
                        'level' => $level,
                        'is_active' => true,
                        'is_optional' => $subjectData['is_optional'] ?? false,
                    ]
                );

                $createdSubjects[] = $subject;

                // Assign to classrooms if requested
                if ($assignToClassrooms && $request->filled('classroom_ids')) {
                    foreach ($request->classroom_ids as $classroomId) {
                        ClassroomSubject::firstOrCreate(
                            [
                                'classroom_id' => $classroomId,
                                'subject_id' => $subject->id,
                            ],
                            [
                                'is_compulsory' => !$subject->is_optional,
                            ]
                        );
                    }
                }
            }

            DB::commit();

            return redirect()
                ->route('academics.subjects.index')
                ->with('success', count($createdSubjects) . ' CBC subjects generated successfully for ' . $level);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Failed to generate subjects: ' . $e->getMessage());
        }
    }

    /**
     * Get CBC subjects for a specific level
     */
    private function getCBCSubjectsForLevel($level)
    {
        $allSubjects = [
            // Pre-Primary (PP1, PP2)
            'PP1' => [
                ['code' => 'LANG', 'name' => 'Language Activities', 'learning_area' => 'Language', 'is_optional' => false],
                ['code' => 'MATH', 'name' => 'Mathematical Activities', 'learning_area' => 'Mathematics', 'is_optional' => false],
                ['code' => 'ENV', 'name' => 'Environmental Activities', 'learning_area' => 'Environmental', 'is_optional' => false],
                ['code' => 'CRE', 'name' => 'CRE Activities', 'learning_area' => 'Religious', 'is_optional' => false],
                ['code' => 'PE', 'name' => 'Psychomotor and Creative Activities', 'learning_area' => 'Physical', 'is_optional' => false],
            ],
            'PP2' => [
                ['code' => 'LANG', 'name' => 'Language Activities', 'learning_area' => 'Language', 'is_optional' => false],
                ['code' => 'MATH', 'name' => 'Mathematical Activities', 'learning_area' => 'Mathematics', 'is_optional' => false],
                ['code' => 'ENV', 'name' => 'Environmental Activities', 'learning_area' => 'Environmental', 'is_optional' => false],
                ['code' => 'CRE', 'name' => 'CRE Activities', 'learning_area' => 'Religious', 'is_optional' => false],
                ['code' => 'PE', 'name' => 'Psychomotor and Creative Activities', 'learning_area' => 'Physical', 'is_optional' => false],
            ],
            // Lower Primary (Grade 1-3)
            'Grade 1' => [
                ['code' => 'ENG', 'name' => 'English', 'learning_area' => 'Language', 'is_optional' => false],
                ['code' => 'KIS', 'name' => 'Kiswahili', 'learning_area' => 'Language', 'is_optional' => false],
                ['code' => 'MATH', 'name' => 'Mathematics', 'learning_area' => 'Mathematics', 'is_optional' => false],
                ['code' => 'ENV', 'name' => 'Environmental Activities', 'learning_area' => 'Environmental', 'is_optional' => false],
                ['code' => 'CRE', 'name' => 'CRE', 'learning_area' => 'Religious', 'is_optional' => false],
                ['code' => 'PE', 'name' => 'Physical Education', 'learning_area' => 'Physical', 'is_optional' => false],
                ['code' => 'ART', 'name' => 'Art and Craft', 'learning_area' => 'Creative', 'is_optional' => false],
            ],
            'Grade 2' => [
                ['code' => 'ENG', 'name' => 'English', 'learning_area' => 'Language', 'is_optional' => false],
                ['code' => 'KIS', 'name' => 'Kiswahili', 'learning_area' => 'Language', 'is_optional' => false],
                ['code' => 'MATH', 'name' => 'Mathematics', 'learning_area' => 'Mathematics', 'is_optional' => false],
                ['code' => 'ENV', 'name' => 'Environmental Activities', 'learning_area' => 'Environmental', 'is_optional' => false],
                ['code' => 'CRE', 'name' => 'CRE', 'learning_area' => 'Religious', 'is_optional' => false],
                ['code' => 'PE', 'name' => 'Physical Education', 'learning_area' => 'Physical', 'is_optional' => false],
                ['code' => 'ART', 'name' => 'Art and Craft', 'learning_area' => 'Creative', 'is_optional' => false],
            ],
            'Grade 3' => [
                ['code' => 'ENG', 'name' => 'English', 'learning_area' => 'Language', 'is_optional' => false],
                ['code' => 'KIS', 'name' => 'Kiswahili', 'learning_area' => 'Language', 'is_optional' => false],
                ['code' => 'MATH', 'name' => 'Mathematics', 'learning_area' => 'Mathematics', 'is_optional' => false],
                ['code' => 'ENV', 'name' => 'Environmental Activities', 'learning_area' => 'Environmental', 'is_optional' => false],
                ['code' => 'CRE', 'name' => 'CRE', 'learning_area' => 'Religious', 'is_optional' => false],
                ['code' => 'PE', 'name' => 'Physical Education', 'learning_area' => 'Physical', 'is_optional' => false],
                ['code' => 'ART', 'name' => 'Art and Craft', 'learning_area' => 'Creative', 'is_optional' => false],
            ],
            // Upper Primary (Grade 4-6)
            'Grade 4' => [
                ['code' => 'ENG', 'name' => 'English', 'learning_area' => 'Language', 'is_optional' => false],
                ['code' => 'KIS', 'name' => 'Kiswahili', 'learning_area' => 'Language', 'is_optional' => false],
                ['code' => 'MATH', 'name' => 'Mathematics', 'learning_area' => 'Mathematics', 'is_optional' => false],
                ['code' => 'SCI', 'name' => 'Science and Technology', 'learning_area' => 'Science', 'is_optional' => false],
                ['code' => 'SS', 'name' => 'Social Studies', 'learning_area' => 'Social', 'is_optional' => false],
                ['code' => 'CRE', 'name' => 'CRE', 'learning_area' => 'Religious', 'is_optional' => false],
                ['code' => 'PE', 'name' => 'Physical Education', 'learning_area' => 'Physical', 'is_optional' => false],
                ['code' => 'ART', 'name' => 'Art and Craft', 'learning_area' => 'Creative', 'is_optional' => false],
            ],
            'Grade 5' => [
                ['code' => 'ENG', 'name' => 'English', 'learning_area' => 'Language', 'is_optional' => false],
                ['code' => 'KIS', 'name' => 'Kiswahili', 'learning_area' => 'Language', 'is_optional' => false],
                ['code' => 'MATH', 'name' => 'Mathematics', 'learning_area' => 'Mathematics', 'is_optional' => false],
                ['code' => 'SCI', 'name' => 'Science and Technology', 'learning_area' => 'Science', 'is_optional' => false],
                ['code' => 'SS', 'name' => 'Social Studies', 'learning_area' => 'Social', 'is_optional' => false],
                ['code' => 'CRE', 'name' => 'CRE', 'learning_area' => 'Religious', 'is_optional' => false],
                ['code' => 'PE', 'name' => 'Physical Education', 'learning_area' => 'Physical', 'is_optional' => false],
                ['code' => 'ART', 'name' => 'Art and Craft', 'learning_area' => 'Creative', 'is_optional' => false],
            ],
            'Grade 6' => [
                ['code' => 'ENG', 'name' => 'English', 'learning_area' => 'Language', 'is_optional' => false],
                ['code' => 'KIS', 'name' => 'Kiswahili', 'learning_area' => 'Language', 'is_optional' => false],
                ['code' => 'MATH', 'name' => 'Mathematics', 'learning_area' => 'Mathematics', 'is_optional' => false],
                ['code' => 'SCI', 'name' => 'Science and Technology', 'learning_area' => 'Science', 'is_optional' => false],
                ['code' => 'SS', 'name' => 'Social Studies', 'learning_area' => 'Social', 'is_optional' => false],
                ['code' => 'CRE', 'name' => 'CRE', 'learning_area' => 'Religious', 'is_optional' => false],
                ['code' => 'PE', 'name' => 'Physical Education', 'learning_area' => 'Physical', 'is_optional' => false],
                ['code' => 'ART', 'name' => 'Art and Craft', 'learning_area' => 'Creative', 'is_optional' => false],
            ],
            // Junior Secondary (Grade 7-9)
            'Grade 7' => [
                // Core (Mandatory)
                ['code' => 'ENG', 'name' => 'English', 'learning_area' => 'Language', 'is_optional' => false],
                ['code' => 'KIS', 'name' => 'Kiswahili', 'learning_area' => 'Language', 'is_optional' => false],
                ['code' => 'MATH', 'name' => 'Mathematics', 'learning_area' => 'Mathematics', 'is_optional' => false],
                ['code' => 'INTSCI', 'name' => 'Integrated Science', 'learning_area' => 'Science', 'is_optional' => false],
                ['code' => 'SS', 'name' => 'Social Studies', 'learning_area' => 'Social', 'is_optional' => false],
                ['code' => 'CRE', 'name' => 'CRE', 'learning_area' => 'Religious', 'is_optional' => false],
                ['code' => 'PE', 'name' => 'Physical Education', 'learning_area' => 'Physical', 'is_optional' => false],
                ['code' => 'LIFE', 'name' => 'Life Skills', 'learning_area' => 'Life Skills', 'is_optional' => false],
                // Optional
                ['code' => 'AGR', 'name' => 'Agriculture', 'learning_area' => 'Applied', 'is_optional' => true],
                ['code' => 'HOME', 'name' => 'Home Science', 'learning_area' => 'Applied', 'is_optional' => true],
                ['code' => 'COMP', 'name' => 'Computer Studies', 'learning_area' => 'Applied', 'is_optional' => true],
                ['code' => 'BS', 'name' => 'Business Studies', 'learning_area' => 'Applied', 'is_optional' => true],
                ['code' => 'VIS', 'name' => 'Visual Arts', 'learning_area' => 'Creative', 'is_optional' => true],
                ['code' => 'PERF', 'name' => 'Performing Arts', 'learning_area' => 'Creative', 'is_optional' => true],
                ['code' => 'FRE', 'name' => 'French', 'learning_area' => 'Language', 'is_optional' => true],
                ['code' => 'GER', 'name' => 'German', 'learning_area' => 'Language', 'is_optional' => true],
                ['code' => 'ARAB', 'name' => 'Arabic', 'learning_area' => 'Language', 'is_optional' => true],
                ['code' => 'MAND', 'name' => 'Mandarin', 'learning_area' => 'Language', 'is_optional' => true],
            ],
            'Grade 8' => [
                // Core (Mandatory)
                ['code' => 'ENG', 'name' => 'English', 'learning_area' => 'Language', 'is_optional' => false],
                ['code' => 'KIS', 'name' => 'Kiswahili', 'learning_area' => 'Language', 'is_optional' => false],
                ['code' => 'MATH', 'name' => 'Mathematics', 'learning_area' => 'Mathematics', 'is_optional' => false],
                ['code' => 'INTSCI', 'name' => 'Integrated Science', 'learning_area' => 'Science', 'is_optional' => false],
                ['code' => 'SS', 'name' => 'Social Studies', 'learning_area' => 'Social', 'is_optional' => false],
                ['code' => 'CRE', 'name' => 'CRE', 'learning_area' => 'Religious', 'is_optional' => false],
                ['code' => 'PE', 'name' => 'Physical Education', 'learning_area' => 'Physical', 'is_optional' => false],
                ['code' => 'LIFE', 'name' => 'Life Skills', 'learning_area' => 'Life Skills', 'is_optional' => false],
                // Optional
                ['code' => 'AGR', 'name' => 'Agriculture', 'learning_area' => 'Applied', 'is_optional' => true],
                ['code' => 'HOME', 'name' => 'Home Science', 'learning_area' => 'Applied', 'is_optional' => true],
                ['code' => 'COMP', 'name' => 'Computer Studies', 'learning_area' => 'Applied', 'is_optional' => true],
                ['code' => 'BS', 'name' => 'Business Studies', 'learning_area' => 'Applied', 'is_optional' => true],
                ['code' => 'VIS', 'name' => 'Visual Arts', 'learning_area' => 'Creative', 'is_optional' => true],
                ['code' => 'PERF', 'name' => 'Performing Arts', 'learning_area' => 'Creative', 'is_optional' => true],
                ['code' => 'FRE', 'name' => 'French', 'learning_area' => 'Language', 'is_optional' => true],
                ['code' => 'GER', 'name' => 'German', 'learning_area' => 'Language', 'is_optional' => true],
                ['code' => 'ARAB', 'name' => 'Arabic', 'learning_area' => 'Language', 'is_optional' => true],
                ['code' => 'MAND', 'name' => 'Mandarin', 'learning_area' => 'Language', 'is_optional' => true],
            ],
            'Grade 9' => [
                // Core (Mandatory)
                ['code' => 'ENG', 'name' => 'English', 'learning_area' => 'Language', 'is_optional' => false],
                ['code' => 'KIS', 'name' => 'Kiswahili', 'learning_area' => 'Language', 'is_optional' => false],
                ['code' => 'MATH', 'name' => 'Mathematics', 'learning_area' => 'Mathematics', 'is_optional' => false],
                ['code' => 'INTSCI', 'name' => 'Integrated Science', 'learning_area' => 'Science', 'is_optional' => false],
                ['code' => 'SS', 'name' => 'Social Studies', 'learning_area' => 'Social', 'is_optional' => false],
                ['code' => 'CRE', 'name' => 'CRE', 'learning_area' => 'Religious', 'is_optional' => false],
                ['code' => 'PE', 'name' => 'Physical Education', 'learning_area' => 'Physical', 'is_optional' => false],
                ['code' => 'LIFE', 'name' => 'Life Skills', 'learning_area' => 'Life Skills', 'is_optional' => false],
                // Optional
                ['code' => 'AGR', 'name' => 'Agriculture', 'learning_area' => 'Applied', 'is_optional' => true],
                ['code' => 'HOME', 'name' => 'Home Science', 'learning_area' => 'Applied', 'is_optional' => true],
                ['code' => 'COMP', 'name' => 'Computer Studies', 'learning_area' => 'Applied', 'is_optional' => true],
                ['code' => 'BS', 'name' => 'Business Studies', 'learning_area' => 'Applied', 'is_optional' => true],
                ['code' => 'VIS', 'name' => 'Visual Arts', 'learning_area' => 'Creative', 'is_optional' => true],
                ['code' => 'PERF', 'name' => 'Performing Arts', 'learning_area' => 'Creative', 'is_optional' => true],
                ['code' => 'FRE', 'name' => 'French', 'learning_area' => 'Language', 'is_optional' => true],
                ['code' => 'GER', 'name' => 'German', 'learning_area' => 'Language', 'is_optional' => true],
                ['code' => 'ARAB', 'name' => 'Arabic', 'learning_area' => 'Language', 'is_optional' => true],
                ['code' => 'MAND', 'name' => 'Mandarin', 'learning_area' => 'Language', 'is_optional' => true],
            ],
        ];

        return $allSubjects[$level] ?? [];
    }

    /**
     * Assign subjects to classrooms
     */
    public function assignToClassrooms(Request $request)
    {
        $validated = $request->validate([
            'subject_ids' => 'required|array',
            'subject_ids.*' => 'exists:subjects,id',
            'classroom_ids' => 'required|array',
            'classroom_ids.*' => 'exists:classrooms,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'term_id' => 'nullable|exists:terms,id',
            'is_compulsory' => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            $count = 0;
            foreach ($validated['subject_ids'] as $subjectId) {
                $subject = Subject::find($subjectId);
                foreach ($validated['classroom_ids'] as $classroomId) {
                    ClassroomSubject::updateOrCreate(
                        [
                            'classroom_id' => $classroomId,
                            'subject_id' => $subjectId,
                            'academic_year_id' => $validated['academic_year_id'] ?? null,
                            'term_id' => $validated['term_id'] ?? null,
                        ],
                        [
                            'is_compulsory' => $validated['is_compulsory'] ?? !$subject->is_optional,
                        ]
                    );
                    $count++;
                }
            }

            DB::commit();

            return redirect()
                ->route('academics.subjects.index')
                ->with('success', "Successfully assigned {$count} subject(s) to classroom(s).");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Failed to assign subjects: ' . $e->getMessage());
        }
    }

    /**
     * Update lessons per week for a classroom subject
     */
    public function updateLessonsPerWeek(Request $request, ClassroomSubject $classroomSubject)
    {
        $validated = $request->validate([
            'lessons_per_week' => 'required|integer|min:1|max:20',
        ]);

        $classroomSubject->update($validated);

        return back()->with('success', 'Lessons per week updated successfully.');
    }
}
