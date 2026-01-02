<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Classroom;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\Term;
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
        
        // Get students in this class with their streams
        $allStudents = Student::where('classroom_id', $classroom->id)
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

            foreach ($validStudents as $student) {
                $oldClassroomId = $student->classroom_id;
                $oldStreamId = $student->stream_id;
                $newStreamId = null;
                
                if ($classroom->is_alumni) {
                    // Mark as alumni - remove from current class and stream
                    $student->update([
                        'is_alumni' => true,
                        'alumni_date' => Carbon::parse($request->promotion_date),
                        'classroom_id' => null, // Remove from class
                        'stream_id' => null, // Remove from stream
                    ]);
                } else {
                    // Promote to next class, find matching stream
                    $nextClassroom = $classroom->nextClass;
                    $newStreamId = $this->findMatchingStream($oldStreamId, $nextClassroom->id);
                    
                    // Update student to new class and stream (removes from old class/stream)
                    $student->update([
                        'classroom_id' => $nextClassroom->id,
                        'stream_id' => $newStreamId, // Will be null if no matching stream found
                    ]);
                }

                // Mark previous academic history as not current
                \App\Models\StudentAcademicHistory::where('student_id', $student->id)
                    ->where('is_current', true)
                    ->update(['is_current' => false]);

                // Record in academic history
                \App\Models\StudentAcademicHistory::create([
                    'student_id' => $student->id,
                    'academic_year_id' => $request->academic_year_id,
                    'term_id' => $request->term_id,
                    'classroom_id' => $oldClassroomId,
                    'stream_id' => $oldStreamId,
                    'next_classroom_id' => $classroom->is_alumni ? null : $classroom->nextClass->id,
                    'next_stream_id' => $classroom->is_alumni ? null : $newStreamId,
                    'enrollment_date' => Carbon::parse($request->promotion_date),
                    'promotion_status' => $classroom->is_alumni ? 'graduated' : 'promoted',
                    'promotion_date' => Carbon::parse($request->promotion_date),
                    'promoted_by' => auth()->id(),
                    'notes' => $request->notes ?? 'Bulk promotion',
                    'is_current' => true,
                ]);
            }

            DB::commit();
            
            $status = $classroom->is_alumni ? 'marked as alumni' : 'promoted';
            $message = count($validStudents) . ' student(s) ' . $status . ' successfully.' . $skippedMessage;
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

            // Update student to new class and stream
            $student->update([
                'classroom_id' => $newClassroomId,
                'stream_id' => $newStreamId,
            ]);

            // Mark previous academic history as not current
            \App\Models\StudentAcademicHistory::where('student_id', $student->id)
                ->where('is_current', true)
                ->update(['is_current' => false]);

            // Record demotion in academic history
            \App\Models\StudentAcademicHistory::create([
                'student_id' => $student->id,
                'academic_year_id' => $request->academic_year_id,
                'term_id' => $request->term_id,
                'classroom_id' => $oldClassroomId,
                'stream_id' => $oldStreamId,
                'next_classroom_id' => $newClassroomId,
                'next_stream_id' => $newStreamId,
                'enrollment_date' => Carbon::parse($request->demotion_date),
                'promotion_status' => 'demoted',
                'promotion_date' => Carbon::parse($request->demotion_date),
                'promoted_by' => auth()->id(),
                'notes' => 'Demotion reason: ' . $request->reason,
                'is_current' => true,
            ]);

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

        // Get all students in this class
        $allStudents = Student::where('classroom_id', $classroom->id)->get();

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
}
