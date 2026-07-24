<?php

namespace App\Http\Controllers;

use App\Models\DropOffPoint;
use App\Models\StudentAssignment;
use Illuminate\Http\Request;
use App\Imports\DropOffPointsImport;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DropOffPointController extends Controller
{
    public function index()
    {
        DropOffPoint::ownMeans();

        $dropOffPoints = DropOffPoint::with(['vehicles'])
            ->withCount([
                'morningAssignments',
                'eveningAssignments',
            ])
            ->orderBy('name')
            ->get();

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
            ->orWhere('drop_off_point_id', $dropoffpoint->id)
            ->exists();

        if ($inUse || $dropoffpoint->assignments()->exists()) {
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

        $msg = "Import complete. Created: {$import->created}, Updated/Restored: {$import->updated}, "
            . "Vehicle links added: {$import->vehicleLinks}.";

        return redirect()->route('transport.dropoffpoints.index')->with('success', $msg);
    }

    public function create()
    {
        return view('dropoffpoints.create');
    }

    public function edit(DropOffPoint $dropoffpoint)
    {
        $usageCount = StudentAssignment::where('morning_drop_off_point_id', $dropoffpoint->id)
            ->orWhere('evening_drop_off_point_id', $dropoffpoint->id)
            ->count();

        return view('dropoffpoints.edit', [
            'dropOffPoint' => $dropoffpoint,
            'usageCount' => $usageCount,
        ]);
    }

    public function importForm()
    {
        return view('dropoffpoints.import');
    }

    public function template(): StreamedResponse
    {
        $headers = ['name', 'two_way_amount', 'one_way_amount', 'vehicle_ids', 'vehicle_regs'];
        $callback = function () use ($headers) {
            $fh = fopen('php://output', 'w');
            fputcsv($fh, $headers);
            fputcsv($fh, ['Kabete', '8000', '5000', '', '']);
            fputcsv($fh, ['Wangige', '6000', '3500', '', '']);
            fclose($fh);
        };

        return response()->streamDownload($callback, 'dropoff_points_template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
