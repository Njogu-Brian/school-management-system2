<?php

namespace App\Http\Controllers;

use App\Models\DropOffPoint;
use App\Models\StudentAssignment;
use App\Services\TransportFeeService;
use Illuminate\Http\Request;
use App\Imports\DropOffPointsImport;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DropOffPointController extends Controller
{
    public function index()
    {
        DropOffPoint::ownMeans();

        $dropOffPoints = DropOffPoint::orderBy('name')->get();

        // Students using + vehicles serving each point come from trip assignments
        // (morning/evening legs), not the unused drop_off_point_vehicle pivot.
        $usageByPoint = [];
        $assignments = StudentAssignment::query()
            ->with(['morningTrip.vehicle', 'eveningTrip.vehicle'])
            ->where(function ($q) {
                $q->whereNotNull('morning_drop_off_point_id')
                    ->orWhereNotNull('evening_drop_off_point_id');
            })
            ->get();

        foreach ($assignments as $assignment) {
            if ($assignment->morning_drop_off_point_id) {
                $pid = (int) $assignment->morning_drop_off_point_id;
                $usageByPoint[$pid] ??= [
                    'student_ids' => [],
                    'morning' => 0,
                    'evening' => 0,
                    'vehicles' => [],
                ];
                $usageByPoint[$pid]['student_ids'][$assignment->student_id] = true;
                $usageByPoint[$pid]['morning']++;
                $vehicle = $assignment->morningTrip?->vehicle;
                if ($vehicle) {
                    $label = $vehicle->vehicle_number
                        ?? $vehicle->registration_number
                        ?? ('Vehicle #'.$vehicle->id);
                    $usageByPoint[$pid]['vehicles'][$vehicle->id] = $label;
                }
            }

            if ($assignment->evening_drop_off_point_id) {
                $pid = (int) $assignment->evening_drop_off_point_id;
                $usageByPoint[$pid] ??= [
                    'student_ids' => [],
                    'morning' => 0,
                    'evening' => 0,
                    'vehicles' => [],
                ];
                $usageByPoint[$pid]['student_ids'][$assignment->student_id] = true;
                $usageByPoint[$pid]['evening']++;
                $vehicle = $assignment->eveningTrip?->vehicle;
                if ($vehicle) {
                    $label = $vehicle->vehicle_number
                        ?? $vehicle->registration_number
                        ?? ('Vehicle #'.$vehicle->id);
                    $usageByPoint[$pid]['vehicles'][$vehicle->id] = $label;
                }
            }
        }

        foreach ($dropOffPoints as $point) {
            $stats = $usageByPoint[(int) $point->id] ?? null;
            $point->students_using_count = $stats ? count($stats['student_ids']) : 0;
            $point->morning_users_count = $stats['morning'] ?? 0;
            $point->evening_users_count = $stats['evening'] ?? 0;
            $point->trip_vehicles = collect($stats['vehicles'] ?? [])->values();
        }

        return view('dropoffpoints.index', compact('dropOffPoints'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'two_way_amount' => 'nullable|numeric|min:0',
            'one_way_amount' => 'nullable|numeric|min:0',
        ]);

        DropOffPoint::create([
            'name' => $request->name,
            'two_way_amount' => $request->two_way_amount,
            'one_way_amount' => $request->one_way_amount,
        ]);

        return redirect()->route('transport.dropoffpoints.index')
            ->with('success', 'Drop-Off Point created successfully.');
    }

    public function update(Request $request, DropOffPoint $dropoffpoint)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'two_way_amount' => 'nullable|numeric|min:0',
            'one_way_amount' => 'nullable|numeric|min:0',
        ]);

        $dropoffpoint->update([
            'name' => $request->name,
            'two_way_amount' => $request->two_way_amount,
            'one_way_amount' => $request->one_way_amount,
        ]);

        return redirect()->route('transport.dropoffpoints.index')
            ->with('success', 'Drop-Off Point updated successfully. Recalculate transport fees for affected classes, then run Post Pending Fees.');
    }

    public function destroy(DropOffPoint $dropoffpoint)
    {
        if ($dropoffpoint->isOwnMeans()) {
            return redirect()->route('transport.dropoffpoints.index')
                ->with('error', 'Cannot delete the system OWN MEANS drop-off point.');
        }

        $inUse = StudentAssignment::where('morning_drop_off_point_id', $dropoffpoint->id)
            ->orWhere('evening_drop_off_point_id', $dropoffpoint->id)
            ->exists();

        if ($inUse) {
            return redirect()->route('transport.dropoffpoints.index')
                ->with('error', 'Cannot delete drop-off point with assigned students.');
        }

        $dropoffpoint->forceDelete();

        return redirect()->route('transport.dropoffpoints.index')
            ->with('success', 'Drop-Off Point permanently deleted successfully.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv,txt',
        ]);

        $import = new DropOffPointsImport();
        Excel::import($import, $request->file('file'));

        $msg = "Import complete. Created: {$import->created}, Updated/Restored: {$import->updated}.";

        return redirect()->route('transport.dropoffpoints.index')->with('success', $msg);
    }

    public function create()
    {
        return view('dropoffpoints.create');
    }

    public function edit(DropOffPoint $dropoffpoint)
    {
        $usageCount = StudentAssignment::query()
            ->where(function ($q) use ($dropoffpoint) {
                $q->where('morning_drop_off_point_id', $dropoffpoint->id)
                    ->orWhere('evening_drop_off_point_id', $dropoffpoint->id);
            })
            ->distinct()
            ->count('student_id');

        return view('dropoffpoints.edit', [
            'dropOffPoint' => $dropoffpoint,
            'usageCount' => $usageCount,
        ]);
    }

    public function importForm()
    {
        return view('dropoffpoints.import');
    }

    /**
     * Find or create a drop-off point by name (used by trip assign / student drop-offs).
     */
    public function resolve(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $name = trim($validated['name']);
        $existed = DropOffPoint::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists()
            || DropOffPoint::nameIsOwnMeans($name);

        $point = TransportFeeService::resolveDropOffPoint($name);
        if (!$point) {
            return response()->json(['message' => 'Could not resolve drop-off point.'], 422);
        }

        return response()->json([
            'id' => $point->id,
            'name' => $point->name,
            'created' => !$existed,
            'own_means' => $point->isOwnMeans(),
        ]);
    }

    public function template(): StreamedResponse
    {
        $headers = ['name', 'two_way_amount', 'one_way_amount'];
        $callback = function () use ($headers) {
            $fh = fopen('php://output', 'w');
            fputcsv($fh, $headers);
            fputcsv($fh, ['Kabete', '8000', '5000']);
            fputcsv($fh, ['Wangige', '6000', '3500']);
            fclose($fh);
        };

        return response()->streamDownload($callback, 'dropoff_points_template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
