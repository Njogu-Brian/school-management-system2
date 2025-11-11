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
        
        // Get students in this class with their streams
        $students = Student::where('classroom_id', $classroom->id)
            ->with('stream')
            ->orderBy('admission_number')
            ->get();
        
        $currentYear = AcademicYear::where('is_active', true)->first();
        $currentTerm = Term::where('is_current', true)->first();
        
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

            foreach ($students as $student) {
                $oldClassroomId = $student->classroom_id;
                $oldStreamId = $student->stream_id;
                
                if ($classroom->is_alumni) {
                    // Mark as alumni
                    $student->update([
                        'is_alumni' => true,
                        'alumni_date' => Carbon::parse($request->promotion_date),
                    ]);
                } else {
                    // Promote to next class, maintain stream
                    $nextClassroom = $classroom->nextClass;
                    $student->update([
                        'classroom_id' => $nextClassroom->id,
                        // Maintain stream if it exists in the next class
                        'stream_id' => $this->findMatchingStream($oldStreamId, $nextClassroom->id),
                    ]);
                }

                // Record in academic history
                \App\Models\StudentAcademicHistory::create([
                    'student_id' => $student->id,
                    'academic_year_id' => $request->academic_year_id,
                    'term_id' => $request->term_id,
                    'classroom_id' => $oldClassroomId,
                    'stream_id' => $oldStreamId,
                    'next_classroom_id' => $classroom->is_alumni ? null : $classroom->nextClass->id,
                    'next_stream_id' => $classroom->is_alumni ? null : $student->stream_id,
                    'enrollment_date' => Carbon::parse($request->promotion_date),
                    'promotion_status' => $classroom->is_alumni ? 'graduated' : 'promoted',
                    'promotion_date' => Carbon::parse($request->promotion_date),
                    'promoted_by' => auth()->id(),
                    'notes' => $request->notes ?? 'Bulk promotion',
                ]);
            }

            DB::commit();
            
            $status = $classroom->is_alumni ? 'marked as alumni' : 'promoted';
            return redirect()->route('academics.promotions.index')
                ->with('success', count($students) . ' student(s) ' . $status . ' successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error promoting students: ' . $e->getMessage());
        }
    }

    /**
     * Find matching stream in the next class
     * If stream name exists in next class, use it; otherwise keep the same stream_id
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

        return $matchingStream ? $matchingStream->id : $oldStreamId;
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
        $students = Student::where('classroom_id', $classroom->id)->get();

        if ($students->isEmpty()) {
            return redirect()->route('academics.promotions.index')
                ->with('error', 'No students found in this class.');
        }

        // Create request with all student IDs
        $request->merge(['student_ids' => $students->pluck('id')->toArray()]);

        // Use the existing promote method
        return $this->promote($request, $classroom);
    }
}
