<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Transport;
use App\Models\Attendance;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function adminDashboard(Request $request)
    {
        $selectedDate = $request->query('date', Carbon::today()->toDateString());
        $classFilter = $request->query('class');
        $statusFilter = $request->query('status');
        $searchQuery = $request->query('search');

        $studentsQuery = Student::with(['attendances' => function ($query) use ($selectedDate) {
            $query->where('date', $selectedDate);
        }]);

        $filtersApplied = $classFilter || $statusFilter || $searchQuery || $selectedDate !== Carbon::today()->toDateString();

        if ($filtersApplied) {
            if ($classFilter) {
                $studentsQuery->where('class', $classFilter);
            }

            if ($statusFilter === 'present') {
                $studentsQuery->whereHas('attendances', function ($query) use ($selectedDate) {
                    $query->where('date', $selectedDate)->where('is_present', 1);
                });
            } elseif ($statusFilter === 'absent') {
                $studentsQuery->whereHas('attendances', function ($query) use ($selectedDate) {
                    $query->where('date', $selectedDate)->where('is_present', 0);
                });
            }

            if ($searchQuery) {
                $studentsQuery->where('name', 'LIKE', "%$searchQuery%");
            }

            $students = $studentsQuery->get();
        } else {
            $students = collect();
        }

        $classes = Student::distinct()->pluck('class');
        $transports = Transport::all();

        $attendanceSummary = Student::with(['attendances' => function ($query) use ($selectedDate) {
            $query->where('date', $selectedDate);
        }])->get()->groupBy('class')->map(function ($students) {
            return [
                'present' => $students->filter(fn($student) => $student->attendances->where('is_present', 1)->count())->count(),
                'absent' => $students->filter(fn($student) => $student->attendances->where('is_present', 0)->count())->count(),
            ];
        });

        return view('dashboard.admin', compact('students', 'transports', 'attendanceSummary', 'classes', 'selectedDate', 'filtersApplied'));
    }

    public function teacherDashboard(Request $request)
    {
        $selectedDate = $request->query('date', Carbon::today()->toDateString());
        $classFilter = $request->query('class');
        $statusFilter = $request->query('status');
        $searchQuery = $request->query('search');

        $studentsQuery = Student::with(['attendances' => function ($query) use ($selectedDate) {
            $query->where('date', $selectedDate);
        }]);

        $filtersApplied = $classFilter || $statusFilter || $searchQuery || $selectedDate !== Carbon::today()->toDateString();

        if ($filtersApplied) {
            if ($classFilter) {
                $studentsQuery->where('class', $classFilter);
            }

            if ($statusFilter === 'present') {
                $studentsQuery->whereHas('attendances', function ($query) use ($selectedDate) {
                    $query->where('date', $selectedDate)->where('is_present', 1);
                });
            } elseif ($statusFilter === 'absent') {
                $studentsQuery->whereHas('attendances', function ($query) use ($selectedDate) {
                    $query->where('date', $selectedDate)->where('is_present', 0);
                });
            }

            if ($searchQuery) {
                $studentsQuery->where('name', 'LIKE', "%$searchQuery%");
            }

            $students = $studentsQuery->get();
        } else {
            $students = collect();
        }

        $classes = Student::distinct()->pluck('class');
        $transports = Transport::all();

        $attendanceSummary = Student::with(['attendances' => function ($query) use ($selectedDate) {
            $query->where('date', $selectedDate);
        }])->get()->groupBy('class')->map(function ($students) {
            return [
                'present' => $students->filter(fn($student) => $student->attendances->where('is_present', 1)->count())->count(),
                'absent' => $students->filter(fn($student) => $student->attendances->where('is_present', 0)->count())->count(),
            ];
        });

        return view('dashboard.teacher', compact('students', 'transports', 'attendanceSummary', 'classes', 'selectedDate', 'filtersApplied'));
        return view('dashboard.teacher');
    }
}
