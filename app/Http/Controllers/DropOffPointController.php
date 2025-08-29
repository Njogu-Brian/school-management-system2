<?php

namespace App\Http\Controllers;

use App\Models\DropOffPoint;
use App\Models\Route;
use Illuminate\Http\Request;
use App\Imports\DropOffPointsImport;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DropOffPointController extends Controller
{

    public function index()
    {
        $dropOffPoints = DropOffPoint::with(['route','vehicles'])->get(); // add vehicles
        return view('dropoffpoints.index', compact('dropOffPoints'));
    }
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'route_id' => 'required|exists:routes,id',
        ]);

        DropOffPoint::create($request->only('name','route_id'));
        return redirect()->route('transport.dropoffpoints.index')
            ->with('success', 'Drop-Off Point created successfully.');
    }
    public function update(Request $request, DropOffPoint $dropOffPoint)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'route_id' => 'required|exists:routes,id',
        ]);

        $dropOffPoint->update($request->only('name','route_id'));
        return redirect()->route('transport.dropoffpoints.index')
            ->with('success', 'Drop-Off Point updated successfully.');
    }
    public function destroy(DropOffPoint $dropOffPoint)
    {
        if ($dropOffPoint->assignments()->exists()) {
            return redirect()->route('transport.dropoffpoints.index')
                ->with('error', 'Cannot delete drop-off point with assigned students.');
        }

        $dropOffPoint->forceDelete();
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
            . "Duplicates (existing): {$import->skippedDuplicate}, "
            . "Skipped (route missing): {$import->skippedMissingRoute}, "
            . "Vehicle links added: {$import->vehicleLinks}.";

        return redirect()->route('transport.dropoffpoints.index')->with('success', $msg);
    }
    public function create()
    {
        $routes = Route::all();
        return view('dropoffpoints.create', compact('routes'));
    }
    public function edit(DropOffPoint $dropOffPoint)
    {
        $routes = Route::all();
        return view('dropoffpoints.edit', compact('dropOffPoint', 'routes'));
    }
    public function importForm()
    {
        return view('dropoffpoints.import');
    }
    public function template(): StreamedResponse
    {
        $headers = ['name','route_id','route_name','vehicle_ids','vehicle_regs'];
        $callback = function () use ($headers) {
            $fh = fopen('php://output', 'w');
            fputcsv($fh, $headers);
            fputcsv($fh, ['Uthiru Stage', '3', '', '1,2', '']);
            fputcsv($fh, ['Waiyaki Way Shell', '', 'CBD Route', '', 'KDC123A,KDG456B']);
            fclose($fh);
        };

        return response()->streamDownload($callback, 'dropoff_points_template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

}
