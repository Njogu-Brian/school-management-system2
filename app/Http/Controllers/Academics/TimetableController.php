<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Timetable;
use App\Models\Academics\Classroom;
use App\Models\Academics\ClassroomSubject;
use App\Models\Academics\ExtraCurricularActivity;
use App\Models\Staff;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Services\TimetableService;
use App\Services\TimetableOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TimetableController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Filter classrooms based on user role
        if (($user->hasRole('Teacher') || $user->hasRole('teacher')) && !is_supervisor()) {
            $assignedClassroomIds = $user->getAssignedClassroomIds();
            if (!empty($assignedClassroomIds)) {
                $classrooms = Classroom::whereIn('id', $assignedClassroomIds)->orderBy('name')->get();
            } else {
                $classrooms = collect();
            }
            // Teachers can only see their own timetable
            $teachers = collect();
        } elseif (is_supervisor() && !$user->hasAnyRole(['Admin', 'Super Admin'])) {
            // Supervisors can see their subordinates' classrooms
            $subordinateClassroomIds = get_subordinate_classroom_ids();
            $ownClassroomIds = $user->getAssignedClassroomIds();
            $allClassroomIds = array_unique(array_merge($ownClassroomIds, $subordinateClassroomIds));
            
            if (!empty($allClassroomIds)) {
                $classrooms = Classroom::whereIn('id', $allClassroomIds)->orderBy('name')->get();
            } else {
                $classrooms = collect();
            }
            // Supervisors can see their subordinates as teachers
            $subordinateIds = get_subordinate_staff_ids();
            $teachers = Staff::whereIn('id', $subordinateIds)
                ->whereHas('user.roles', fn($q) => $q->whereIn('name', ['Teacher', 'teacher']))
                ->get();
        } else {
            $classrooms = Classroom::orderBy('name')->get();
            $teachers = Staff::whereHas('user.roles', fn($q) => $q->whereIn('name', ['Teacher', 'teacher']))->get();
        }
        
        $years = AcademicYear::orderByDesc('year')->get();
        $terms = Term::orderByDesc('start_date')->orderBy('name')->get();

        $selectedClassroom = $request->filled('classroom_id') ? Classroom::find($request->classroom_id) : null;
        $selectedTeacher = $request->filled('teacher_id') ? Staff::find($request->teacher_id) : null;
        $selectedYear = $request->filled('academic_year_id') ? AcademicYear::find($request->academic_year_id) : $years->first();
        $selectedTerm = $request->filled('term_id') ? Term::find($request->term_id) : $terms->first();

        $timetable = null;
        $savedTimetable = null;
        $conflicts = [];
        
        if ($selectedClassroom && $selectedYear && $selectedTerm) {
            // Check if saved timetable exists
            $savedTimetable = Timetable::where('classroom_id', $selectedClassroom->id)
                ->where('academic_year_id', $selectedYear->id)
                ->where('term_id', $selectedTerm->id)
                ->get()
                ->groupBy(['day', 'period']);
            
            if ($savedTimetable->isEmpty()) {
                // Generate new timetable
                $timetable = TimetableService::generateForClassroom(
                    $selectedClassroom->id,
                    $selectedYear->id,
                    $selectedTerm->id
                );
                $conflicts = TimetableService::checkConflicts($timetable);
            }
        }

        // Get extra-curricular activities
        $activities = [];
        if ($selectedYear && $selectedTerm) {
            $activities = ExtraCurricularActivity::where('academic_year_id', $selectedYear->id)
                ->where('term_id', $selectedTerm->id)
                ->where('is_active', true)
                ->get()
                ->groupBy('day');
        }

        return view('academics.timetable.index', compact(
            'classrooms', 'teachers', 'years', 'terms',
            'selectedClassroom', 'selectedTeacher', 'selectedYear', 'selectedTerm',
            'timetable', 'savedTimetable', 'conflicts', 'activities'
        ));
    }

    public function classroom(Classroom $classroom, Request $request)
    {
        $yearId = $request->get('academic_year_id') ?? AcademicYear::orderByDesc('year')->first()?->id;
        $termId = $request->get('term_id') ?? Term::orderByDesc('start_date')->orderBy('name')->first()?->id;

        if (!$yearId || !$termId) {
            return back()->with('error', 'Please select academic year and term.');
        }

        // Get saved timetable or generate new
        $savedTimetable = Timetable::where('classroom_id', $classroom->id)
            ->where('academic_year_id', $yearId)
            ->where('term_id', $termId)
            ->with(['subject', 'teacher'])
            ->get()
            ->groupBy(['day', 'period']);

        $timetable = null;
        $conflicts = [];
        
        if ($savedTimetable->isEmpty()) {
            // Use optimized generation by default
            $timetable = TimetableOptimizationService::generateOptimized($classroom->id, $yearId, $termId);
            $conflicts = $timetable['conflicts'] ?? [];
        }

        // Get subject assignments with lessons_per_week
        $assignments = ClassroomSubject::where('classroom_id', $classroom->id)
            ->where(function($q) use ($yearId, $termId) {
                $q->where(function($q2) use ($yearId, $termId) {
                    $q2->where('academic_year_id', $yearId)
                       ->where('term_id', $termId);
                })
                ->orWhere(function($q2) {
                    $q2->whereNull('academic_year_id')
                       ->whereNull('term_id');
                });
            })
            ->with(['subject', 'teacher'])
            ->get();

        // Get extra-curricular activities
        $activities = ExtraCurricularActivity::where('academic_year_id', $yearId)
            ->where('term_id', $termId)
            ->where('is_active', true)
            ->where(function($q) use ($classroom) {
                $q->whereJsonContains('classroom_ids', $classroom->id)
                  ->orWhereNull('classroom_ids');
            })
            ->get()
            ->groupBy('day');

        $year = AcademicYear::find($yearId);
        $term = Term::find($termId);

        return view('academics.timetable.classroom', compact(
            'classroom', 'year', 'term', 'timetable', 'savedTimetable', 
            'conflicts', 'assignments', 'activities'
        ));
    }

    public function edit(Classroom $classroom, Request $request)
    {
        $yearId = $request->get('academic_year_id') ?? AcademicYear::orderByDesc('year')->first()?->id;
        $termId = $request->get('term_id') ?? Term::orderByDesc('start_date')->orderBy('name')->first()?->id;

        if (!$yearId || !$termId) {
            return back()->with('error', 'Please select academic year and term.');
        }

        $savedTimetable = Timetable::where('classroom_id', $classroom->id)
            ->where('academic_year_id', $yearId)
            ->where('term_id', $termId)
            ->with(['subject', 'teacher'])
            ->get();

        $assignments = ClassroomSubject::where('classroom_id', $classroom->id)
            ->where(function($q) use ($yearId, $termId) {
                $q->where(function($q2) use ($yearId, $termId) {
                    $q2->where('academic_year_id', $yearId)
                       ->where('term_id', $termId);
                })
                ->orWhere(function($q2) {
                    $q2->whereNull('academic_year_id')
                       ->whereNull('term_id');
                });
            })
            ->with(['subject', 'teacher'])
            ->get();

        $year = AcademicYear::find($yearId);
        $term = Term::find($termId);

        // Default time slots
        $timeSlots = [
            ['start' => '08:00', 'end' => '08:40', 'period' => 1],
            ['start' => '08:40', 'end' => '09:20', 'period' => 2],
            ['start' => '09:20', 'end' => '10:00', 'period' => 3],
            ['start' => '10:00', 'end' => '10:20', 'period' => 'Break'],
            ['start' => '10:20', 'end' => '11:00', 'period' => 4],
            ['start' => '11:00', 'end' => '11:40', 'period' => 5],
            ['start' => '11:40', 'end' => '12:20', 'period' => 6],
            ['start' => '12:20', 'end' => '13:00', 'period' => 'Lunch'],
            ['start' => '13:00', 'end' => '13:40', 'period' => 7],
            ['start' => '13:40', 'end' => '14:20', 'period' => 8],
        ];

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        return view('academics.timetable.edit', compact(
            'classroom', 'year', 'term', 'savedTimetable', 
            'assignments', 'timeSlots', 'days'
        ));
    }

    public function teacher(Staff $teacher, Request $request)
    {
        $yearId = $request->get('academic_year_id') ?? AcademicYear::orderByDesc('year')->first()?->id;
        $termId = $request->get('term_id') ?? Term::orderBy('name')->first()?->id;

        if (!$yearId || !$termId) {
            return back()->with('error', 'Please select academic year and term.');
        }

        $timetable = TimetableService::generateForTeacher($teacher->id, $yearId, $termId);

        return view('academics.timetable.teacher', compact('timetable', 'teacher'));
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'use_optimization' => 'nullable|boolean',
        ]);

        // Use AI optimization if requested
        if ($request->filled('use_optimization') && $request->use_optimization) {
            $timetable = TimetableOptimizationService::generateOptimized(
                $validated['classroom_id'],
                $validated['academic_year_id'],
                $validated['term_id']
            );
        } else {
            $timetable = TimetableService::generateForClassroom(
                $validated['classroom_id'],
                $validated['academic_year_id'],
                $validated['term_id']
            );
        }

        return view('academics.timetable.preview', compact('timetable'));
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'timetable' => 'required|array',
        ]);

        // Check if user has access to this classroom
        if (!Auth::user()->hasAnyRole(['Admin', 'Super Admin'])) {
            $staff = Auth::user()->staff;
            if ($staff) {
                $hasOwnAccess = DB::table('classroom_subjects')
                    ->where('staff_id', $staff->id)
                    ->where('classroom_id', $validated['classroom_id'])
                    ->exists();
                
                // Supervisors can create timetables for their subordinates' classrooms
                $hasSubordinateAccess = false;
                if (is_supervisor()) {
                    $subordinateClassroomIds = get_subordinate_classroom_ids();
                    $hasSubordinateAccess = in_array($validated['classroom_id'], $subordinateClassroomIds);
                }
                
                if (!$hasOwnAccess && !$hasSubordinateAccess) {
                    return back()
                        ->withInput()
                        ->with('error', 'You do not have access to create timetables for this classroom.');
                }
            }
        }

        // Delete existing timetable
        Timetable::where('classroom_id', $validated['classroom_id'])
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('term_id', $validated['term_id'])
            ->delete();

        // Check for conflicts and teacher lesson limits
        $conflicts = [];
        $teacherCounts = [];
        $defaultMaxLessons = (int) \App\Models\Setting::where('key', 'max_lessons_per_teacher_per_week')->value('value') ?? 40;
        
        foreach ($validated['timetable'] as $day => $periods) {
            foreach ($periods as $period => $data) {
                if (isset($data['subject_id']) && $data['subject_id'] && isset($data['teacher_id']) && $data['teacher_id']) {
                    $teacherId = $data['teacher_id'];
                    
                    // Initialize teacher count
                    if (!isset($teacherCounts[$teacherId])) {
                        $teacher = Staff::find($teacherId);
                        $maxLessons = $teacher->max_lessons_per_week ?? $defaultMaxLessons;
                        $teacherCounts[$teacherId] = [
                            'teacher' => $teacher,
                            'current' => 0,
                            'max' => $maxLessons,
                        ];
                    }
                    
                    // Count this lesson
                    $teacherCounts[$teacherId]['current']++;
                    
                    // Check if teacher has another class at same time
                    $existing = Timetable::where('staff_id', $teacherId)
                        ->where('academic_year_id', $validated['academic_year_id'])
                        ->where('term_id', $validated['term_id'])
                        ->where('day', $day)
                        ->where('period', $period)
                        ->where('classroom_id', '!=', $validated['classroom_id'])
                        ->exists();
                    
                    if ($existing) {
                        $conflicts[] = [
                            'type' => 'teacher_conflict',
                            'day' => $day,
                            'period' => $period,
                            'teacher_id' => $teacherId,
                            'message' => 'Teacher has another class at this time',
                        ];
                    }
                }
            }
        }
        
        // Check teacher lesson limits
        foreach ($teacherCounts as $teacherId => $count) {
            if ($count['current'] > $count['max']) {
                $conflicts[] = [
                    'type' => 'teacher_limit_exceeded',
                    'teacher_id' => $teacherId,
                    'teacher_name' => $count['teacher']->full_name ?? 'Unknown',
                    'current' => $count['current'],
                    'max' => $count['max'],
                    'message' => "{$count['teacher']->full_name} exceeds maximum lessons ({$count['current']}/{$count['max']})",
                ];
            }
        }

        if (!empty($conflicts)) {
            return back()
                ->with('error', 'Conflicts detected. Please resolve before saving.')
                ->with('conflicts', $conflicts)
                ->with('teacher_counts', $teacherCounts);
        }

        // Save new timetable
        foreach ($validated['timetable'] as $day => $periods) {
            foreach ($periods as $period => $data) {
                if (isset($data['subject_id']) && $data['subject_id'] && !in_array($period, ['Break', 'Lunch'])) {
                    Timetable::create([
                        'classroom_id' => $validated['classroom_id'],
                        'academic_year_id' => $validated['academic_year_id'],
                        'term_id' => $validated['term_id'],
                        'day' => $day,
                        'period' => is_numeric($period) ? $period : 0,
                        'start_time' => $data['start'] ?? '08:00',
                        'end_time' => $data['end'] ?? '08:40',
                        'subject_id' => $data['subject_id'],
                        'staff_id' => $data['teacher_id'] ?? null,
                        'room' => $data['room'] ?? null,
                        'is_break' => in_array($period, ['Break', 'Lunch']),
                    ]);
                }
            }
        }

        return redirect()
            ->route('academics.timetable.classroom', $validated['classroom_id'])
            ->with('success', 'Timetable saved successfully.');
    }

    public function duplicate(Request $request)
    {
        $validated = $request->validate([
            'source_classroom_id' => 'required|exists:classrooms,id',
            'target_classroom_ids' => 'required|array',
            'target_classroom_ids.*' => 'exists:classrooms,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
        ]);

        $sourceTimetable = Timetable::where('classroom_id', $validated['source_classroom_id'])
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('term_id', $validated['term_id'])
            ->get();

        if ($sourceTimetable->isEmpty()) {
            return back()->with('error', 'Source timetable is empty.');
        }

        foreach ($validated['target_classroom_ids'] as $targetClassroomId) {
            // Delete existing timetable for target
            Timetable::where('classroom_id', $targetClassroomId)
                ->where('academic_year_id', $validated['academic_year_id'])
                ->where('term_id', $validated['term_id'])
                ->delete();

            // Duplicate timetable
            foreach ($sourceTimetable as $entry) {
                Timetable::create([
                    'classroom_id' => $targetClassroomId,
                    'academic_year_id' => $entry->academic_year_id,
                    'term_id' => $entry->term_id,
                    'day' => $entry->day,
                    'period' => $entry->period,
                    'start_time' => $entry->start_time,
                    'end_time' => $entry->end_time,
                    'subject_id' => $entry->subject_id,
                    'staff_id' => $entry->staff_id,
                    'room' => $entry->room,
                    'is_break' => $entry->is_break,
                ]);
            }
        }

        return back()->with('success', 'Timetable duplicated successfully.');
    }

    public function updatePeriod(Request $request, Timetable $timetable)
    {
        $validated = $request->validate([
            'subject_id' => 'nullable|exists:subjects,id',
            'staff_id' => 'nullable|exists:staff,id',
            'start_time' => 'required|string',
            'end_time' => 'required|string',
            'room' => 'nullable|string|max:50',
        ]);

        // Check for teacher conflict
        if ($validated['staff_id']) {
            $conflict = Timetable::where('staff_id', $validated['staff_id'])
                ->where('academic_year_id', $timetable->academic_year_id)
                ->where('term_id', $timetable->term_id)
                ->where('day', $timetable->day)
                ->where('period', $timetable->period)
                ->where('id', '!=', $timetable->id)
                ->exists();

            if ($conflict) {
                return back()->with('error', 'Teacher has another class at this time.');
            }
            
            // Check teacher lesson limit
            $teacher = Staff::find($validated['staff_id']);
            $defaultMaxLessons = (int) \App\Models\Setting::where('key', 'max_lessons_per_teacher_per_week')->value('value') ?? 40;
            $maxLessons = $teacher->max_lessons_per_week ?? $defaultMaxLessons;
            
            $currentLessons = Timetable::where('staff_id', $validated['staff_id'])
                ->where('academic_year_id', $timetable->academic_year_id)
                ->where('term_id', $timetable->term_id)
                ->where('id', '!=', $timetable->id)
                ->count();
            
            // If this is a new assignment (not just updating existing), check limit
            if (!$timetable->staff_id || $timetable->staff_id != $validated['staff_id']) {
                if ($currentLessons >= $maxLessons) {
                    return back()->with('error', "Teacher {$teacher->full_name} has reached maximum lessons per week ({$maxLessons}).");
                }
            }
        }

        $timetable->update($validated);

        return back()->with('success', 'Period updated successfully.');
    }
    
    /**
     * Check conflicts in real-time (AJAX)
     */
    public function checkConflicts(Request $request)
    {
        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'timetable' => 'required|array',
        ]);
        
        $conflicts = [];
        $teacherCounts = [];
        $defaultMaxLessons = (int) \App\Models\Setting::where('key', 'max_lessons_per_teacher_per_week')->value('value') ?? 40;
        
        foreach ($validated['timetable'] as $day => $periods) {
            foreach ($periods as $period => $data) {
                if (isset($data['teacher_id']) && $data['teacher_id']) {
                    $teacherId = $data['teacher_id'];
                    
                    if (!isset($teacherCounts[$teacherId])) {
                        $teacher = Staff::find($teacherId);
                        $maxLessons = $teacher->max_lessons_per_week ?? $defaultMaxLessons;
                        $teacherCounts[$teacherId] = [
                            'teacher_name' => $teacher->full_name ?? 'Unknown',
                            'current' => 0,
                            'max' => $maxLessons,
                        ];
                    }
                    
                    $teacherCounts[$teacherId]['current']++;
                }
            }
        }
        
        // Check limits
        foreach ($teacherCounts as $teacherId => $count) {
            if ($count['current'] > $count['max']) {
                $conflicts[] = [
                    'type' => 'teacher_limit',
                    'teacher_id' => $teacherId,
                    'message' => "{$count['teacher_name']} exceeds limit ({$count['current']}/{$count['max']})",
                ];
            }
        }
        
        return response()->json([
            'conflicts' => $conflicts,
            'teacher_counts' => $teacherCounts,
        ]);
    }
}
