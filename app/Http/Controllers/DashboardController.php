<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Transport;
use App\Models\Attendance;
use App\Models\Academics\Classroom;
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
        }, 'classroom']);

        $filtersApplied = $classFilter || $statusFilter || $searchQuery || $selectedDate !== Carbon::today()->toDateString();

        if ($filtersApplied) {
            // ✅ Class Filter using Classroom Relationship
            if ($classFilter) {
                $studentsQuery->whereHas('classroom', function ($query) use ($classFilter) {
                    $query->where('name', $classFilter);
                });
            }

            // ✅ Status Filter
            if ($statusFilter === 'present') {
                $studentsQuery->whereHas('attendances', function ($query) use ($selectedDate) {
                    $query->where('date', $selectedDate)->where('is_present', 1);
                });
            } elseif ($statusFilter === 'absent') {
                $studentsQuery->whereHas('attendances', function ($query) use ($selectedDate) {
                    $query->where('date', $selectedDate)->where('is_present', 0);
                });
            }

            // ✅ Search Filter
            if ($searchQuery) {
                $studentsQuery->where(function ($query) use ($searchQuery) {
                    $query->where('first_name', 'LIKE', "%$searchQuery%")
                          ->orWhere('middle_name', 'LIKE', "%$searchQuery%")
                          ->orWhere('last_name', 'LIKE', "%$searchQuery%");
                });
            }

            $students = $studentsQuery->get();
        } else {
            $students = collect();
        }

        // ✅ Fix Class Fetching using Classroom Model
        $classes = Classroom::distinct()->pluck('name');
        $transports = Transport::all();

        // ✅ Fix Attendance Summary using Classroom Relationship
        $attendanceSummary = Student::with(['attendances' => function ($query) use ($selectedDate) {
            $query->where('date', $selectedDate);
        }, 'classroom'])->get()->groupBy('classroom.name')->map(function ($students) {
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
        }, 'classroom']);

        $filtersApplied = $classFilter || $statusFilter || $searchQuery || $selectedDate !== Carbon::today()->toDateString();

        if ($filtersApplied) {
            // ✅ Class Filter using Classroom Relationship
            if ($classFilter) {
                $studentsQuery->whereHas('classroom', function ($query) use ($classFilter) {
                    $query->where('name', $classFilter);
                });
            }

            // ✅ Status Filter
            if ($statusFilter === 'present') {
                $studentsQuery->whereHas('attendances', function ($query) use ($selectedDate) {
                    $query->where('date', $selectedDate)->where('is_present', 1);
                });
            } elseif ($statusFilter === 'absent') {
                $studentsQuery->whereHas('attendances', function ($query) use ($selectedDate) {
                    $query->where('date', $selectedDate)->where('is_present', 0);
                });
            }

            // ✅ Search Filter
            if ($searchQuery) {
                $studentsQuery->where(function ($query) use ($searchQuery) {
                    $query->where('first_name', 'LIKE', "%$searchQuery%")
                          ->orWhere('middle_name', 'LIKE', "%$searchQuery%")
                          ->orWhere('last_name', 'LIKE', "%$searchQuery%");
                });
            }

            $students = $studentsQuery->get();
        } else {
            $students = collect();
        }

        // ✅ Fix Class Fetching using Classroom Model
        $classes = Classroom::distinct()->pluck('name');
        $transports = Transport::all();

        // ✅ Fix Attendance Summary using Classroom Relationship
        $attendanceSummary = Student::with(['attendances' => function ($query) use ($selectedDate) {
            $query->where('date', $selectedDate);
        }, 'classroom'])->get()->groupBy('classroom.name')->map(function ($students) {
            return [
                'present' => $students->filter(fn($student) => $student->attendances->where('is_present', 1)->count())->count(),
                'absent' => $students->filter(fn($student) => $student->attendances->where('is_present', 0)->count())->count(),
            ];
        });

        return view('dashboard.teacher', compact('students', 'transports', 'attendanceSummary', 'classes', 'selectedDate', 'filtersApplied'));
    }
}
