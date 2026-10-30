<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Classroom;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\StudentAcademicHistory;
use App\Models\Term;
use App\Services\Academics\StudentPlacementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StudentPromotionController extends Controller
{
    public function index()
    {
        $classrooms = Classroom::with(['nextClass', 'streams', 'students'])
            ->orderBy('name')
            ->get();
        
        $currentYear = AcademicYear::where('is_active', true)->first();
        $currentTerm = Term::where('is_current', true)->first();
        
        return view('academics.promotions.index', compact('classrooms', 'currentYear', 'currentTerm'));
    }

    public function show(Classroom $classroom)
    {
        $classroom->load('nextClass', 'streams');
        
        $currentYear = AcademicYear::where('is_active', true)->first();
        $currentTerm = Term::where('is_current', true)->first();
        
        // Get students in this class with their streams (exclude alumni)
        $allStudents = Student::withAlumni()
            ->where('classroom_id', $classroom->id)
            ->where('is_alumni', false) // Explicitly exclude alumni
            ->with('stream')
            ->orderBy('admission_number')
            ->get();
        
        // Filter out students who were just promoted to this class in the current academic year
        // Only show students who have been in this class since the start of the academic year
        $students = $allStudents->filter(function($student) use ($currentYear, $classroom) {
            if (!$currentYear) {
                return true; // If no current year, show all students
            }
            
            // Check if student was promoted to this class in the current academic year
            $recentPromotion = \App\Models\StudentAcademicHistory::where('student_id', $student->id)
                ->where('academic_year_id', $currentYear->id)
                ->where('next_classroom_id', $classroom->id)
                ->where('promotion_status', 'promoted')
                ->exists();
            
            // If student was promoted to this class in current year, exclude them
            if ($recentPromotion) {
                return false;
            }
            
            // Check if student has already been promoted in the current academic year
            $alreadyPromotedThisYear = \App\Models\StudentAcademicHistory::where('student_id', $student->id)
                ->where('academic_year_id', $currentYear->id)
                ->where('promotion_status', 'promoted')
                ->exists();
            
            // If already promoted this year, exclude them
            if ($alreadyPromotedThisYear) {
                return false;
            }
            
            // If next class is alumni and student is already in that class, exclude them
            if ($classroom->nextClass && $classroom->nextClass->is_alumni) {
                // Check if student is already marked as alumni or in alumni class
                if ($student->is_alumni || $student->classroom_id === $classroom->nextClass->id) {
                    return false;
                }
            }
            
            return true;
        });
        
        return view('academics.promotions.show', compact('classroom', 'students', 'currentYear', 'currentTerm'));
    }

    public function promote(Request $request, Classroom $classroom)
    {
        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'promotion_date' => 'required|date',
        ]);

        if (!$classroom->nextClass && !$classroom->is_alumni) {
            return back()->with('error', 'This class does not have a next class mapped. Please set the next class in class settings.');
        }

        // If next class is alumni, check if students are already in that alumni class
        if ($classroom->nextClass && $classroom->nextClass->is_alumni) {
            $studentsInAlumniClass = Student::withAlumni()
                ->whereIn('id', $request->student_ids)
                ->where('classroom_id', $classroom->nextClass->id)
                ->orWhere('is_alumni', true)
                ->count();
            
            if ($studentsInAlumniClass > 0) {
                return back()->with('error', 'Some selected students are already in the alumni class or marked as alumni. They cannot be promoted again.');
            }
        }

        // Check if this class has already been promoted in this academic year
        $alreadyPromoted = \App\Models\StudentAcademicHistory::where('classroom_id', $classroom->id)
            ->where('academic_year_id', $request->academic_year_id)
            ->where('promotion_status', 'promoted')
            ->exists();

        if ($alreadyPromoted) {
            return back()->with('error', 'This class has already been promoted in the selected academic year. Each class can only be promoted once per academic year.');
        }

        DB::beginTransaction();
        try {
            $students = Student::whereIn('id', $request->student_ids)
                ->where('classroom_id', $classroom->id)
                ->get();

            // Filter out students who have already been promoted in this academic year
            $validStudents = $students->filter(function($student) use ($request, $classroom) {
                // Check if student is already alumni or in alumni class
                if ($student->is_alumni) {
                    return false;
                }
                
                // If next class is alumni, check if student is already in that class
                if ($classroom->nextClass && $classroom->nextClass->is_alumni) {
                    if ($student->classroom_id === $classroom->nextClass->id) {
                        return false;
                    }
                }
                
                // Check if student was already promoted in this academic year
                $alreadyPromoted = \App\Models\StudentAcademicHistory::where('student_id', $student->id)
                    ->where('academic_year_id', $request->academic_year_id)
                    ->where('promotion_status', 'promoted')
                    ->exists();
                
                // Check if student was just promoted to this class in the current academic year
                $recentPromotion = \App\Models\StudentAcademicHistory::where('student_id', $student->id)
                    ->where('academic_year_id', $request->academic_year_id)
                    ->where('next_classroom_id', $classroom->id)
                    ->where('promotion_status', 'promoted')
                    ->exists();
                
                return !$alreadyPromoted && !$recentPromotion;
            });

            if ($validStudents->isEmpty()) {
                DB::rollBack();
                return back()->with('error', 'No valid students to promote. All selected students have already been promoted this academic year or were just promoted to this class.');
            }
            
            $skippedCount = $students->count() - $validStudents->count();
            $skippedMessage = $skippedCount > 0 ? " ({$skippedCount} student(s) skipped - already promoted this year)" : '';

            $placement = app(StudentPlacementService::class);
            $effectiveDate = $placement->defaultEffectiveDate();
            $applyNow = $placement->isEffectiveDue($effectiveDate);
            $scheduledLabel = $effectiveDate->format('j M Y');

            foreach ($validStudents as $student) {
                $oldClassroomId = $student->classroom_id;
                $oldStreamId = $student->stream_id;
                $newStreamId = null;
                $nextClassroomId = null;
                $status = $classroom->is_alumni ? 'graduated' : 'promoted';

                if (! $classroom->is_alumni) {
                    $nextClassroom = $classroom->nextClass;
                    $nextClassroomId = (int) $nextClassroom->id;
                    $newStreamId = $this->findMatchingStream($oldStreamId, $nextClassroom->id);
                }

                StudentAcademicHistory::where('student_id', $student->id)
                    ->where('is_current', true)
                    ->update(['is_current' => false]);

                $history = StudentAcademicHistory::create([
                    'student_id' => $student->id,
                    'academic_year_id' => $request->academic_year_id,
                    'term_id' => $request->term_id,
                    'classroom_id' => $oldClassroomId,
                    'stream_id' => $oldStreamId,
                    'next_classroom_id' => $nextClassroomId,
                    'next_stream_id' => $classroom->is_alumni ? null : $newStreamId,
                    'enrollment_date' => Carbon::parse($request->promotion_date),
                    'promotion_status' => $status,
                    'promotion_date' => Carbon::parse($request->promotion_date),
                    'effective_date' => $effectiveDate->toDateString(),
                    'applied_at' => null,
                    'promoted_by' => auth()->id(),
                    'notes' => $request->notes ?? 'Bulk promotion',
                    'is_current' => true,
                ]);

                if ($applyNow) {
                    $placement->applyHistory($history);
                }
            }

            DB::commit();
            
            $status = $classroom->is_alumni ? 'scheduled as alumni' : 'scheduled for promotion';
            $timing = $applyNow
                ? 'applied immediately'
                : "live class stays until {$scheduledLabel}";
            $message = count($validStudents) . " student(s) {$status} ({$timing})." . $skippedMessage;
            return redirect()->route('academics.promotions.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error promoting students: ' . $e->getMessage());
        }
    }

    /**
     * Find matching stream in the next class
     * If stream name exists in next class, use it; otherwise return null (don't keep old stream_id)
     */
    private function findMatchingStream($oldStreamId, $newClassroomId)
    {
        if (!$oldStreamId) {
            return null;
        }

        $oldStream = \App\Models\Academics\Stream::find($oldStreamId);
        if (!$oldStream) {
            return null;
        }

        // Try to find a stream with the same name in the new classroom
        $matchingStream = \App\Models\Academics\Stream::where('classroom_id', $newClassroomId)
            ->where('name', $oldStream->name)
            ->first();

        // Only return matching stream if found, otherwise null (student moves without stream)
        return $matchingStream ? $matchingStream->id : null;
    }

    /**
     * Demote a student to a previous class
     */
    public function demote(Request $request, Student $student)
    {
        $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'demotion_date' => 'required|date',
            'reason' => 'required|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $oldClassroomId = $student->classroom_id;
            $oldStreamId = $student->stream_id;
            $newClassroomId = $request->classroom_id;
            $newStreamId = $request->stream_id;

            // Validate stream belongs to new classroom if provided
            if ($newStreamId) {
                $stream = \App\Models\Academics\Stream::find($newStreamId);
                if (!$stream || $stream->classroom_id != $newClassroomId) {
                    return back()->with('error', 'Selected stream does not belong to the selected classroom.');
                }
            }

            // Demotions are current-year corrections: apply immediately.
            $demotionDate = Carbon::parse($request->demotion_date);
            $placement = app(StudentPlacementService::class);

            StudentAcademicHistory::where('student_id', $student->id)
                ->where('is_current', true)
                ->update(['is_current' => false]);

            $history = StudentAcademicHistory::create([
                'student_id' => $student->id,
                'academic_year_id' => $request->academic_year_id,
                'term_id' => $request->term_id,
                'classroom_id' => $oldClassroomId,
                'stream_id' => $oldStreamId,
                'next_classroom_id' => $newClassroomId,
                'next_stream_id' => $newStreamId,
                'enrollment_date' => $demotionDate,
                'promotion_status' => 'demoted',
                'promotion_date' => $demotionDate,
                'effective_date' => $demotionDate->toDateString(),
                'applied_at' => null,
                'promoted_by' => auth()->id(),
                'notes' => 'Demotion reason: ' . $request->reason,
                'is_current' => true,
            ]);

            $placement->applyHistory($history);

            DB::commit();
            
            return back()->with('success', 'Student demoted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error demoting student: ' . $e->getMessage());
        }
    }

    /**
     * Show alumni students
     */
    public function alumni(Request $request)
    {
        $query = Student::where('is_alumni', true)
            ->with(['parent']);

        // Search filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('middle_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('admission_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('alumni_year')) {
            $query->whereYear('alumni_date', $request->alumni_year);
        }

        $alumni = $query->orderBy('alumni_date', 'desc')
            ->orderBy('admission_number')
            ->paginate(20)
            ->withQueryString();

        // Get last class/stream from academic history for each alumni
        foreach ($alumni as $student) {
            $lastHistory = \App\Models\StudentAcademicHistory::where('student_id', $student->id)
                ->where('promotion_status', 'graduated')
                ->with(['classroom', 'stream'])
                ->orderBy('promotion_date', 'desc')
                ->first();
            
            $student->lastClassroom = $lastHistory->classroom ?? null;
            $student->lastStream = $lastHistory->stream ?? null;
        }

        $years = Student::where('is_alumni', true)
            ->selectRaw('YEAR(alumni_date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        return view('academics.promotions.alumni', compact('alumni', 'years'));
    }

    /**
     * Promote all students from a class
     */
    public function promoteAll(Request $request, Classroom $classroom)
    {
        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'promotion_date' => 'required|date',
        ]);

        if (!$classroom->nextClass && !$classroom->is_alumni) {
            return redirect()->route('academics.promotions.index')
                ->with('error', 'This class does not have a next class mapped. Please set the next class in class settings.');
        }

        // Check if this class has already been promoted in this academic year
        $alreadyPromoted = \App\Models\StudentAcademicHistory::where('classroom_id', $classroom->id)
            ->where('academic_year_id', $request->academic_year_id)
            ->where('promotion_status', 'promoted')
            ->exists();

        if ($alreadyPromoted) {
            return redirect()->route('academics.promotions.index')
                ->with('error', 'This class has already been promoted in the selected academic year. Each class can only be promoted once per academic year.');
        }

        // Get all students in this class (exclude alumni)
        $allStudents = Student::withAlumni()
            ->where('classroom_id', $classroom->id)
            ->where('is_alumni', false) // Explicitly exclude alumni
            ->get();

        // Filter out students who were just promoted to this class in the current academic year
        // or have already been promoted this year
        $students = $allStudents->filter(function($student) use ($request, $classroom) {
            // Check if student was already promoted in this academic year
            $alreadyPromoted = \App\Models\StudentAcademicHistory::where('student_id', $student->id)
                ->where('academic_year_id', $request->academic_year_id)
                ->where('promotion_status', 'promoted')
                ->exists();
            
            // Check if student was just promoted to this class in the current academic year
            $recentPromotion = \App\Models\StudentAcademicHistory::where('student_id', $student->id)
                ->where('academic_year_id', $request->academic_year_id)
                ->where('next_classroom_id', $classroom->id)
                ->where('promotion_status', 'promoted')
                ->exists();
            
            return !$alreadyPromoted && !$recentPromotion;
        });

        if ($students->isEmpty()) {
            $skippedCount = $allStudents->count() - $students->count();
            $message = 'No valid students found to promote in this class.';
            if ($skippedCount > 0) {
                $message .= " {$skippedCount} student(s) were skipped because they were already promoted this academic year or were just promoted to this class.";
            }
            return redirect()->route('academics.promotions.index')
                ->with('error', $message);
        }

        // Create request with filtered student IDs
        $request->merge(['student_ids' => $students->pluck('id')->toArray()]);

        // Use the existing promote method
        return $this->promote($request, $classroom);
    }

    /**
     * Promote the whole school in one transaction.
     * Snapshots each class's students BEFORE any moves so Grade 3→4 and Grade 4→5
     * cannot cascade the same cohort twice.
     */
    public function promoteSchool(Request $request)
    {
        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'promotion_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $academicYearId = (int) $request->academic_year_id;
        $termId = (int) $request->term_id;
        $promotionDate = Carbon::parse($request->promotion_date);
        $notes = $request->notes ?? 'Whole-school promotion';

        $classrooms = Classroom::with('nextClass')->orderBy('name')->get();

        // Snapshot student IDs per source class before any classroom_id changes.
        $snapshots = [];
        $unmapped = [];
        foreach ($classrooms as $classroom) {
            $studentIds = Student::query()
                ->where('classroom_id', $classroom->id)
                ->where('is_alumni', false)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if (empty($studentIds)) {
                continue;
            }

            if (! $classroom->nextClass && ! $classroom->is_alumni) {
                $unmapped[] = $classroom->name.' ('.count($studentIds).' students)';
                continue;
            }

            $snapshots[] = [
                'classroom' => $classroom,
                'student_ids' => $studentIds,
            ];
        }

        if (! empty($unmapped)) {
            return redirect()->route('academics.promotions.index')
                ->with('error', 'Cannot promote whole school. Map a next class (or mark alumni) for: '.implode(', ', $unmapped));
        }

        if (empty($snapshots)) {
            return redirect()->route('academics.promotions.index')
                ->with('info', 'No students found to promote.');
        }

        $promoted = 0;
        $graduated = 0;
        $skipped = 0;
        $classSummaries = [];
        $placement = app(StudentPlacementService::class);
        $effectiveDate = $placement->defaultEffectiveDate();
        $applyNow = $placement->isEffectiveDue($effectiveDate);
        $scheduledLabel = $effectiveDate->format('j M Y');

        try {
            DB::transaction(function () use (
                $snapshots,
                $academicYearId,
                $termId,
                $promotionDate,
                $notes,
                $placement,
                $effectiveDate,
                $applyNow,
                &$promoted,
                &$graduated,
                &$skipped,
                &$classSummaries
            ) {
                foreach ($snapshots as $snap) {
                    /** @var Classroom $classroom */
                    $classroom = $snap['classroom'];
                    $studentIds = $snap['student_ids'];

                    $alreadyPromotedClass = StudentAcademicHistory::where('classroom_id', $classroom->id)
                        ->where('academic_year_id', $academicYearId)
                        ->whereIn('promotion_status', ['promoted', 'graduated'])
                        ->exists();

                    if ($alreadyPromotedClass) {
                        $skipped += count($studentIds);
                        $classSummaries[] = $classroom->name.': skipped (already promoted this year)';
                        continue;
                    }

                    $students = Student::whereIn('id', $studentIds)->get();
                    $classPromoted = 0;
                    $classGraduated = 0;
                    $classSkipped = 0;

                    foreach ($students as $student) {
                        if ($student->is_alumni) {
                            $classSkipped++;
                            continue;
                        }

                        $alreadyPromoted = StudentAcademicHistory::where('student_id', $student->id)
                            ->where('academic_year_id', $academicYearId)
                            ->whereIn('promotion_status', ['promoted', 'graduated'])
                            ->exists();
                        if ($alreadyPromoted) {
                            $classSkipped++;
                            continue;
                        }

                        $oldClassroomId = $student->classroom_id;
                        $oldStreamId = $student->stream_id;
                        $newStreamId = null;
                        $nextClassroomId = null;
                        $status = 'promoted';

                        // Only graduate when THIS class is the alumni/leaving class.
                        // A next class flagged is_alumni (e.g. Grade 9) still means "move into
                        // that class" — same behaviour as per-class promote — not leave school.
                        if ($classroom->is_alumni) {
                            $status = 'graduated';
                            $classGraduated++;
                        } else {
                            $nextClassroom = $classroom->nextClass;
                            if (! $nextClassroom) {
                                $classSkipped++;
                                continue;
                            }

                            $nextClassroomId = (int) $nextClassroom->id;
                            $newStreamId = $this->findMatchingStream($oldStreamId, $nextClassroom->id);
                            $status = 'promoted';
                            $classPromoted++;
                        }

                        StudentAcademicHistory::where('student_id', $student->id)
                            ->where('is_current', true)
                            ->update(['is_current' => false]);

                        $history = StudentAcademicHistory::create([
                            'student_id' => $student->id,
                            'academic_year_id' => $academicYearId,
                            'term_id' => $termId,
                            'classroom_id' => $oldClassroomId,
                            'stream_id' => $oldStreamId,
                            'next_classroom_id' => $nextClassroomId,
                            'next_stream_id' => $status === 'graduated' ? null : $newStreamId,
                            'enrollment_date' => $promotionDate,
                            'promotion_status' => $status,
                            'promotion_date' => $promotionDate,
                            'effective_date' => $effectiveDate->toDateString(),
                            'applied_at' => null,
                            'promoted_by' => auth()->id(),
                            'notes' => $notes,
                            'is_current' => true,
                        ]);

                        if ($applyNow) {
                            $placement->applyHistory($history);
                        }
                    }

                    $promoted += $classPromoted;
                    $graduated += $classGraduated;
                    $skipped += $classSkipped;
                    $classSummaries[] = sprintf(
                        '%s: %d promoted, %d alumni, %d skipped',
                        $classroom->name,
                        $classPromoted,
                        $classGraduated,
                        $classSkipped
                    );
                }
            });
        } catch (\Throwable $e) {
            return redirect()->route('academics.promotions.index')
                ->with('error', 'Whole-school promotion failed: '.$e->getMessage());
        }

        $timing = $applyNow
            ? 'applied immediately'
            : "live classes stay until {$scheduledLabel}";
        $message = "Whole-school promotion scheduled. Promoted: {$promoted}. Alumni: {$graduated}. Skipped: {$skipped}. ({$timing})";

        return redirect()->route('academics.promotions.index')
            ->with('success', $message)
            ->with('promotion_details', $classSummaries);
    }
}
