<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\StudentSkillGrade;
use App\Models\Academics\ReportCardSkill;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentSkillGradeController extends Controller
{
    // GET /academics/skills/grade?academic_year_id=&term_id=&classroom_id=
    public function index(Request $request)
    {
        $yearId = $request->query('academic_year_id');
        $termId = $request->query('term_id');
        $classId = $request->query('classroom_id');

        $students = collect();
        $skills = ReportCardSkill::where('is_active', true)->orderBy('name')->get();

        if ($yearId && $termId && $classId) {
            $students = Student::with('classroom','stream')
                ->where('classroom_id', $classId)
                ->orderBy('last_name')->get();

            // preload existing grades
            $grades = StudentSkillGrade::where('academic_year_id',$yearId)
                ->where('term_id',$termId)
                ->whereIn('student_id',$students->pluck('id'))
                ->get()
                ->groupBy(['student_id','report_card_skill_id']);
        } else {
            $grades = collect();
        }

        return view('academics.skills.grades.index', compact('students','skills','grades','yearId','termId','classId'));
    }

    // POST /academics/skills/grade
    public function store(Request $request)
    {
        $data = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id'          => 'required|exists:terms,id',
            'rows'             => 'required|array',
            'rows.*.student_id'=> 'required|exists:students,id',
            'rows.*.skills'    => 'required|array',
            'rows.*.skills.*.report_card_skill_id' => 'required|exists:report_card_skills,id',
            'rows.*.skills.*.grade' => 'nullable|string|max:5',
            'rows.*.skills.*.comment' => 'nullable|string|max:500',
        ]);

        foreach ($data['rows'] as $row) {
            foreach ($row['skills'] as $sg) {
                $grade = StudentSkillGrade::firstOrNew([
                    'student_id' => $row['student_id'],
                    'term_id'    => $data['term_id'],
                    'academic_year_id' => $data['academic_year_id'],
                    'report_card_skill_id' => $sg['report_card_skill_id'],
                ]);

                $grade->fill([
                    'grade'  => $sg['grade'] ?? null,
                    'comment'=> $sg['comment'] ?? null,
                    'entered_by' => $grade->exists ? $grade->entered_by : Auth::id(),
                    'updated_by' => Auth::id(),
                ])->save();
            }
        }

        return back()->with('success','Skill grades saved.');
    }
}
