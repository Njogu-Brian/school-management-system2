<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\Controller;
use App\Models\DriverChangeRequest;
use App\Models\Trip;
use App\Models\DropOffPoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DriverChangeRequestController extends Controller
{
    /**
     * Display driver change requests
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Drivers can only see their own requests
        if ($user->hasRole('Driver')) {
            $staff = $user->staff;
            if ($staff) {
                $requests = DriverChangeRequest::where('driver_id', $staff->id)
                    ->with(['trip.vehicle', 'requestedTrip.vehicle', 'requestedDropOffPoint', 'reviewer'])
                    ->orderBy('created_at', 'desc')
                    ->paginate(20);
            } else {
                $requests = collect();
            }
        } else {
            // Admins/Secretaries see all requests
            $requests = DriverChangeRequest::with([
                'driver.user',
                'trip.vehicle',
                'requestedTrip.vehicle',
                'requestedDropOffPoint',
                'reviewer'
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        }

        return view('transport.driver_change_requests.index', compact('requests'));
    }

    /**
     * Show form to create a change request (for drivers)
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        $staff = $user->staff;

        if (!$staff) {
            return redirect()->back()->with('error', 'Staff profile not found.');
        }

        // Get trips assigned to this driver
        $trips = Trip::where('driver_id', $staff->id)
            ->with(['vehicle'])
            ->get();

        $allTrips = Trip::with(['vehicle'])->get();
        $dropOffPoints = DropOffPoint::orderBy('name')->get();

        return view('transport.driver_change_requests.create', compact('trips', 'allTrips', 'dropOffPoints'));
    }

    /**
     * Store a new change request
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $staff = $user->staff;

        if (!$staff) {
            return redirect()->back()->with('error', 'Staff profile not found.');
        }

        $validated = $request->validate([
            'trip_id' => 'required|exists:trips,id',
            'request_type' => 'required|in:reassignment,dropoff_change',
            'requested_trip_id' => 'nullable|required_if:request_type,reassignment|exists:trips,id',
            'requested_drop_off_point_id' => 'nullable|required_if:request_type,dropoff_change|exists:drop_off_points,id',
            'reason' => 'required|string|max:1000',
        ]);

        // Verify driver is assigned to the trip
        $trip = Trip::findOrFail($validated['trip_id']);
        if ($trip->driver_id !== $staff->id) {
            return redirect()->back()->with('error', 'You are not assigned to this trip.');
        }

        $changeRequest = DriverChangeRequest::create([
            'driver_id' => $staff->id,
            'trip_id' => $validated['trip_id'],
            'request_type' => $validated['request_type'],
            'requested_trip_id' => $validated['requested_trip_id'] ?? null,
            'requested_drop_off_point_id' => $validated['requested_drop_off_point_id'] ?? null,
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        return redirect()->route('transport.driver-change-requests.index')
            ->with('success', 'Change request submitted successfully. It will be reviewed by administration.');
    }

    /**
     * Approve a change request
     */
    public function approve(DriverChangeRequest $driverChangeRequest, Request $request)
    {
        if ($driverChangeRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'This request has already been processed.');
        }

        $validated = $request->validate([
            'review_notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($driverChangeRequest, $validated) {
            $driverChangeRequest->update([
                'status' => 'approved',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'review_notes' => $validated['review_notes'] ?? null,
            ]);

            // Apply the change
            if ($driverChangeRequest->request_type === 'reassignment' && $driverChangeRequest->requested_trip_id) {
                // Move driver to new trip
                Trip::where('id', $driverChangeRequest->requested_trip_id)
                    ->update(['driver_id' => $driverChangeRequest->driver_id]);
                
                // Remove driver from old trip
                Trip::where('id', $driverChangeRequest->trip_id)
                    ->update(['driver_id' => null]);
            }

            // Note: Drop-off point changes would need to be handled through StudentAssignment
            // This is a simplified implementation
        });

        return redirect()->back()->with('success', 'Change request approved and applied successfully.');
    }

    /**
     * Reject a change request
     */
    public function reject(DriverChangeRequest $driverChangeRequest, Request $request)
    {
        if ($driverChangeRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'This request has already been processed.');
        }

        $validated = $request->validate([
            'review_notes' => 'required|string|max:1000',
        ]);

        $driverChangeRequest->update([
            'status' => 'rejected',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'review_notes' => $validated['review_notes'],
        ]);

        return redirect()->back()->with('success', 'Change request rejected.');
    }
}

