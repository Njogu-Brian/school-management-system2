<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Academics\Assessment;
use App\Models\Academics\Classroom;
use App\Models\Academics\Subject;
use App\Models\CampusSeniorTeacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HeatmapController extends Controller
{
    public function show(Request $request, string $campus)
    {
        $campus = strtolower($campus);
        if (!in_array($campus, ['lower', 'upper'], true)) {
            abort(404);
        }

        $user = Auth::user();
        if ($user && $user->hasRole('Senior Teacher')) {
            $assignment = CampusSeniorTeacher::where('campus', $campus)
                ->where('senior_teacher_id', $user->id)
                ->first();
            if (!$assignment) {
                abort(403, 'You do not have access to this campus heatmap.');
            }
        }

        $weekEnding = $request->input('week_ending');

        $classroomQuery = Classroom::query();
        $classroomQuery->where(function ($query) use ($campus) {
            $query->where('campus', $campus);
            if ($campus === 'lower') {
                $query->orWhereIn('level_type', ['upper_primary', 'junior_high']);
            } else {
                $query->orWhereIn('level_type', ['preschool', 'lower_primary']);
            }
        });

        $classrooms = $classroomQuery->orderBy('name')->get();

        $assessmentQuery = Assessment::query()
            ->selectRaw('classroom_id, subject_id, AVG(score_percent) as avg_percent')
            ->whereIn('classroom_id', $classrooms->pluck('id'));

        if ($weekEnding) {
            $assessmentQuery->whereDate('week_ending', $weekEnding);
        }

        $assessmentQuery->groupBy('classroom_id', 'subject_id');

        $averages = $assessmentQuery->get()->groupBy('classroom_id');

        $subjectIds = Assessment::query()
            ->whereIn('classroom_id', $classrooms->pluck('id'))
            ->when($weekEnding, fn ($q) => $q->whereDate('week_ending', $weekEnding))
            ->distinct()
            ->pluck('subject_id');

        $subjects = Subject::whereIn('id', $subjectIds)->orderBy('name')->get();

        return view('reports.heatmaps.show', [
            'campus' => $campus,
            'weekEnding' => $weekEnding,
            'classrooms' => $classrooms,
            'subjects' => $subjects,
            'averages' => $averages,
        ]);
    }
}
