<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\Controller;
use App\Models\TransportSpecialAssignment;
use App\Models\Student;
use App\Models\Vehicle;
use App\Models\Trip;
use App\Models\DropOffPoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransportSpecialAssignmentController extends Controller
{
    /**
     * Display special assignments
     */
    public function index(Request $request)
    {
        $query = TransportSpecialAssignment::with([
            'student.classroom',
            'vehicle',
            'trip.vehicle',
            'dropOffPoint',
            'approver',
            'creator'
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        $assignments = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('transport.special_assignments.index', compact('assignments'));
    }

    /**
     * Show form to create special assignment
     */
    public function create(Request $request)
    {
        $students = Student::where('archive', 0)
            ->where('is_alumni', false)
            ->orderBy('first_name')
            ->get();

        $vehicles = Vehicle::orderBy('vehicle_number')->get();
        $trips = Trip::with(['vehicle'])->get();
        $dropOffPoints = DropOffPoint::orderBy('name')->get();

        return view('transport.special_assignments.create', compact(
            'students',
            'vehicles',
            'trips',
            'dropOffPoints'
        ));
    }

    /**
     * Store special assignment
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'nullable|exists:students,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'trip_id' => 'nullable|exists:trips,id',
            'drop_off_point_id' => 'nullable|exists:drop_off_points,id',
            'assignment_type' => 'required|in:student_specific,vehicle_wide',
            'transport_mode' => 'required|in:vehicle,trip,own_means',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'reason' => 'required|string|max:1000',
        ]);

        // Validate based on transport mode
        if ($validated['transport_mode'] === 'vehicle' && !$validated['vehicle_id']) {
            return redirect()->back()->with('error', 'Vehicle is required when transport mode is vehicle.');
        }

        if ($validated['transport_mode'] === 'trip' && !$validated['trip_id']) {
            return redirect()->back()->with('error', 'Trip is required when transport mode is trip.');
        }

        if ($validated['assignment_type'] === 'student_specific' && !$validated['student_id']) {
            return redirect()->back()->with('error', 'Student is required for student-specific assignments.');
        }

        $assignment = TransportSpecialAssignment::create([
            'student_id' => $validated['student_id'] ?? null,
            'vehicle_id' => $validated['vehicle_id'] ?? null,
            'trip_id' => $validated['trip_id'] ?? null,
            'drop_off_point_id' => $validated['drop_off_point_id'] ?? null,
            'assignment_type' => $validated['assignment_type'],
            'transport_mode' => $validated['transport_mode'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'reason' => $validated['reason'],
            'status' => 'pending', // Requires approval
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('transport.special-assignments.index')
            ->with('success', 'Special assignment created. It requires approval before becoming active.');
    }

    /**
     * Approve special assignment
     */
    public function approve(TransportSpecialAssignment $transportSpecialAssignment, Request $request)
    {
        if ($transportSpecialAssignment->status !== 'pending') {
            return redirect()->back()->with('error', 'This assignment has already been processed.');
        }

        $transportSpecialAssignment->update([
            'status' => 'active',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        // Send notification if needed
        // Notification::send(...)

        return redirect()->back()->with('success', 'Special assignment approved and activated.');
    }

    /**
     * Reject special assignment
     */
    public function reject(TransportSpecialAssignment $transportSpecialAssignment, Request $request)
    {
        if ($transportSpecialAssignment->status !== 'pending') {
            return redirect()->back()->with('error', 'This assignment has already been processed.');
        }

        $transportSpecialAssignment->update([
            'status' => 'cancelled',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Special assignment rejected.');
    }

    /**
     * Cancel active assignment
     */
    public function cancel(TransportSpecialAssignment $transportSpecialAssignment)
    {
        if (!in_array($transportSpecialAssignment->status, ['active', 'pending'])) {
            return redirect()->back()->with('error', 'This assignment cannot be cancelled.');
        }

        $transportSpecialAssignment->update([
            'status' => 'cancelled',
        ]);

        return redirect()->back()->with('success', 'Special assignment cancelled.');
    }
}

