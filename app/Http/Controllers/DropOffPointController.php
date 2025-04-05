<?php

namespace App\Http\Controllers;

use App\Models\DropOffPoint;
use App\Models\Route;
use Illuminate\Http\Request;

class DropOffPointController extends Controller
{
    public function index()
    {
        $dropOffPoints = DropOffPoint::with('route')->get();
        return view('dropoffpoints.index', compact('dropOffPoints'));
    }

    public function create()
    {
        $routes = Route::all();
        return view('dropoffpoints.create', compact('routes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'route_id' => 'required|exists:routes,id',
        ]);

        DropOffPoint::create($request->all());
        return redirect()->route('dropoffpoints.index')->with('success', 'Drop-Off Point created successfully.');
    }

    public function edit(DropOffPoint $dropOffPoint)
    {
        $routes = Route::all();
        return view('dropoffpoints.edit', compact('dropOffPoint', 'routes'));
    }

    public function update(Request $request, DropOffPoint $dropOffPoint)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'route_id' => 'required|exists:routes,id',
        ]);

        $dropOffPoint->update($request->all());
        return redirect()->route('dropoffpoints.index')->with('success', 'Drop-Off Point updated successfully.');
    }

    public function destroy(DropOffPoint $dropOffPoint)
    {
        // Check if the drop-off point has any assigned students
        if ($dropOffPoint->assignments()->exists()) {
            return redirect()->route('dropoffpoints.index')->with('error', 'Cannot delete drop-off point with assigned students.');
        }
    
        // Permanently delete using forceDelete
        $dropOffPoint->forceDelete();
        return redirect()->route('dropoffpoints.index')->with('success', 'Drop-Off Point permanently deleted successfully.');
    }
    

}
