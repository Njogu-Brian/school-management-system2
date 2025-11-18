<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\Department;
use App\Models\StaffCategory;
use App\Exports\ArrayExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StaffReportController extends Controller
{
    /**
     * Display staff reports index page
     */
    public function index()
    {
        return view('hr.reports.index');
    }

    /**
     * Export staff directory
     */
    public function exportDirectory(Request $request)
    {
        $query = Staff::with(['department', 'jobTitle', 'category', 'supervisor']);

        // Apply filters
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('category_id')) {
            $query->where('staff_category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('employment_status')) {
            $query->where('employment_status', $request->employment_status);
        }

        $staff = $query->orderBy('first_name')->orderBy('last_name')->get();

        $data = $staff->map(function ($s) {
            return [
                'Staff ID' => $s->staff_id,
                'Full Name' => $s->full_name,
                'Email' => $s->work_email,
                'Phone' => $s->phone_number,
                'ID Number' => $s->id_number,
                'Department' => $s->department?->name ?? 'N/A',
                'Job Title' => $s->jobTitle?->name ?? 'N/A',
                'Category' => $s->category?->name ?? 'N/A',
                'Status' => ucfirst($s->status),
                'Employment Status' => ucfirst(str_replace('_', ' ', $s->employment_status ?? 'N/A')),
                'Employment Type' => ucfirst(str_replace('_', ' ', $s->employment_type ?? 'N/A')),
                'Hire Date' => $s->hire_date?->format('Y-m-d') ?? 'N/A',
                'Supervisor' => $s->supervisor?->full_name ?? 'N/A',
                'KRA PIN' => $s->kra_pin ?? 'N/A',
                'NSSF' => $s->nssf ?? 'N/A',
                'NHIF' => $s->nhif ?? 'N/A',
            ];
        })->toArray();

        $headers = array_keys($data[0] ?? []);

        return Excel::download(new ArrayExport($data, $headers), 'staff_directory_' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Department-wise staff listing
     */
    public function departmentReport(Request $request)
    {
        $departments = Department::withCount('staff')
            ->with(['staff' => function($q) {
                $q->where('status', 'active')->orderBy('first_name');
            }])
            ->get();

        $data = [];
        foreach ($departments as $dept) {
            foreach ($dept->staff as $staff) {
                $data[] = [
                    'Department' => $dept->name,
                    'Staff ID' => $staff->staff_id,
                    'Full Name' => $staff->full_name,
                    'Email' => $staff->work_email,
                    'Phone' => $staff->phone_number,
                    'Job Title' => $staff->jobTitle?->name ?? 'N/A',
                    'Category' => $staff->category?->name ?? 'N/A',
                    'Hire Date' => $staff->hire_date?->format('Y-m-d') ?? 'N/A',
                ];
            }
        }

        $headers = ['Department', 'Staff ID', 'Full Name', 'Email', 'Phone', 'Job Title', 'Category', 'Hire Date'];

        return Excel::download(new ArrayExport($data, $headers), 'staff_by_department_' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Staff by category report
     */
    public function categoryReport(Request $request)
    {
        $categories = StaffCategory::withCount('staff')
            ->with(['staff' => function($q) {
                $q->where('status', 'active')->orderBy('first_name');
            }])
            ->get();

        $data = [];
        foreach ($categories as $cat) {
            foreach ($cat->staff as $staff) {
                $data[] = [
                    'Category' => $cat->name,
                    'Staff ID' => $staff->staff_id,
                    'Full Name' => $staff->full_name,
                    'Email' => $staff->work_email,
                    'Phone' => $staff->phone_number,
                    'Department' => $staff->department?->name ?? 'N/A',
                    'Job Title' => $staff->jobTitle?->name ?? 'N/A',
                ];
            }
        }

        $headers = ['Category', 'Staff ID', 'Full Name', 'Email', 'Phone', 'Department', 'Job Title'];

        return Excel::download(new ArrayExport($data, $headers), 'staff_by_category_' . date('Y-m-d') . '.xlsx');
    }

    /**
     * New hires report
     */
    public function newHiresReport(Request $request)
    {
        $startDate = $request->filled('start_date') 
            ? Carbon::parse($request->start_date) 
            : Carbon::now()->startOfYear();
        
        $endDate = $request->filled('end_date') 
            ? Carbon::parse($request->end_date) 
            : Carbon::now();

        $staff = Staff::whereBetween('hire_date', [$startDate, $endDate])
            ->with(['department', 'jobTitle', 'category'])
            ->orderBy('hire_date', 'desc')
            ->get();

        $data = $staff->map(function ($s) {
            return [
                'Staff ID' => $s->staff_id,
                'Full Name' => $s->full_name,
                'Email' => $s->work_email,
                'Phone' => $s->phone_number,
                'Department' => $s->department?->name ?? 'N/A',
                'Job Title' => $s->jobTitle?->name ?? 'N/A',
                'Category' => $s->category?->name ?? 'N/A',
                'Hire Date' => $s->hire_date?->format('Y-m-d') ?? 'N/A',
                'Employment Type' => ucfirst(str_replace('_', ' ', $s->employment_type ?? 'N/A')),
            ];
        })->toArray();

        $headers = ['Staff ID', 'Full Name', 'Email', 'Phone', 'Department', 'Job Title', 'Category', 'Hire Date', 'Employment Type'];

        return Excel::download(new ArrayExport($data, $headers), 'new_hires_' . $startDate->format('Y-m-d') . '_to_' . $endDate->format('Y-m-d') . '.xlsx');
    }

    /**
     * Terminations report
     */
    public function terminationsReport(Request $request)
    {
        $startDate = $request->filled('start_date') 
            ? Carbon::parse($request->start_date) 
            : Carbon::now()->startOfYear();
        
        $endDate = $request->filled('end_date') 
            ? Carbon::parse($request->end_date) 
            : Carbon::now();

        $staff = Staff::whereNotNull('termination_date')
            ->whereBetween('termination_date', [$startDate, $endDate])
            ->with(['department', 'jobTitle', 'category'])
            ->orderBy('termination_date', 'desc')
            ->get();

        $data = $staff->map(function ($s) {
            return [
                'Staff ID' => $s->staff_id,
                'Full Name' => $s->full_name,
                'Email' => $s->work_email,
                'Phone' => $s->phone_number,
                'Department' => $s->department?->name ?? 'N/A',
                'Job Title' => $s->jobTitle?->name ?? 'N/A',
                'Hire Date' => $s->hire_date?->format('Y-m-d') ?? 'N/A',
                'Termination Date' => $s->termination_date?->format('Y-m-d') ?? 'N/A',
                'Employment Status' => ucfirst(str_replace('_', ' ', $s->employment_status ?? 'N/A')),
            ];
        })->toArray();

        $headers = ['Staff ID', 'Full Name', 'Email', 'Phone', 'Department', 'Job Title', 'Hire Date', 'Termination Date', 'Employment Status'];

        return Excel::download(new ArrayExport($data, $headers), 'terminations_' . $startDate->format('Y-m-d') . '_to_' . $endDate->format('Y-m-d') . '.xlsx');
    }

    /**
     * Staff turnover analysis
     */
    public function turnoverAnalysis(Request $request)
    {
        $year = $request->filled('year') ? $request->year : Carbon::now()->year;

        $startOfYear = Carbon::create($year, 1, 1);
        $endOfYear = Carbon::create($year, 12, 31);

        // New hires in the year
        $newHires = Staff::whereBetween('hire_date', [$startOfYear, $endOfYear])->count();

        // Terminations in the year
        $terminations = Staff::whereNotNull('termination_date')
            ->whereBetween('termination_date', [$startOfYear, $endOfYear])
            ->count();

        // Staff at start of year
        $staffAtStart = Staff::where('hire_date', '<', $startOfYear)
            ->where(function($q) use ($startOfYear) {
                $q->whereNull('termination_date')
                  ->orWhere('termination_date', '>=', $startOfYear);
            })
            ->count();

        // Average staff during year
        $avgStaff = ($staffAtStart + ($staffAtStart + $newHires - $terminations)) / 2;

        // Turnover rate
        $turnoverRate = $avgStaff > 0 ? ($terminations / $avgStaff) * 100 : 0;

        $data = [
            [
                'Year' => $year,
                'Staff at Start' => $staffAtStart,
                'New Hires' => $newHires,
                'Terminations' => $terminations,
                'Staff at End' => $staffAtStart + $newHires - $terminations,
                'Average Staff' => round($avgStaff, 2),
                'Turnover Rate (%)' => round($turnoverRate, 2),
            ]
        ];

        $headers = ['Year', 'Staff at Start', 'New Hires', 'Terminations', 'Staff at End', 'Average Staff', 'Turnover Rate (%)'];

        return Excel::download(new ArrayExport($data, $headers), 'staff_turnover_analysis_' . $year . '.xlsx');
    }
}

