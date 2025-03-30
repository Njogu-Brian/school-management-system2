<?php

namespace App\Http\Controllers;

use App\Models\Route;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Vehicle;
use App\Models\DropOffPoint;
use App\Models\Trip;
class RouteController extends Controller
{

    public function index()
{
    $routes = Route::with('vehicles', 'dropOffPoints')->get();
    $students = Student::all();
    $vehicles = Vehicle::all();
    $trips = Trip::all();
    $dropOffPoints = DropOffPoint::all();

    return view('routes.index', compact('routes', 'students', 'vehicles', 'trips', 'dropOffPoints'));
}

    public function create()
    {
        return view('routes.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'area' => 'nullable|string',
        ]);

        Route::create($request->all());
        return redirect()->route('routes.index')->with('success', 'Route added.');
    }

    public function edit(Route $route)
    {
        return view('routes.edit', compact('route'));
    }

    public function update(Request $request, Route $route)
    {
        $request->validate([
            'name' => 'required|string',
            'area' => 'nullable|string',
        ]);

        $route->update($request->all());
        return redirect()->route('routes.index')->with('success', 'Route updated.');
    }

    public function destroy(Route $route)
    {
        $route->delete();
        return redirect()->route('routes.index')->with('success', 'Route deleted.');
    }
}

