<?php

namespace App\Http\Controllers\Hostel;

use App\Http\Controllers\Controller;
use App\Models\HostelAllocation;
use App\Models\Student;
use App\Models\HostelRoom;
use App\Services\HostelService;
use Illuminate\Http\Request;

class HostelAllocationController extends Controller
{
    protected HostelService $hostelService;

    public function __construct(HostelService $hostelService)
    {
        $this->hostelService = $hostelService;
    }

    public function index(Request $request)
    {
        $query = HostelAllocation::with(['student', 'hostel', 'room']);

        if ($request->filled('hostel_id')) {
            $query->where('hostel_id', $request->hostel_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        $allocations = $query->latest('allocation_date')->paginate(20)->withQueryString();

        return view('hostel.allocations.index', compact('allocations'));
    }

    public function create(Request $request)
    {
        $studentId = $request->get('student_id');
        $hostelId = $request->get('hostel_id');

        $students = Student::orderBy('first_name')->get();
        $hostels = \App\Models\Hostel::where('is_active', true)->get();
        $rooms = $hostelId 
            ? HostelRoom::where('hostel_id', $hostelId)->where('status', 'available')->get()
            : collect();

        return view('hostel.allocations.create', compact('students', 'hostels', 'rooms', 'studentId', 'hostelId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'room_id' => 'required|exists:hostel_rooms,id',
            'bed_number' => 'nullable|string|max:50',
        ]);

        try {
            $student = Student::findOrFail($validated['student_id']);
            $room = HostelRoom::findOrFail($validated['room_id']);

            $allocation = $this->hostelService->allocateStudent(
                $student,
                $room,
                $validated['bed_number'] ?? null
            );

            return redirect()
                ->route('hostel.allocations.show', $allocation)
                ->with('success', 'Student allocated to hostel successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function show(HostelAllocation $allocation)
    {
        $allocation->load(['student', 'hostel', 'room']);
        return view('hostel.allocations.show', compact('allocation'));
    }

    public function deallocate(HostelAllocation $allocation)
    {
        try {
            $this->hostelService->deallocateStudent($allocation);

            return redirect()
                ->route('hostel.allocations.index')
                ->with('success', 'Student deallocated from hostel successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

