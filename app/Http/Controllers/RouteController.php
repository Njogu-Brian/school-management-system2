<?php

namespace App\Http\Controllers;

use App\Models\Route;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Vehicle;

class RouteController extends Controller
{

    public function index()
{
    $routes = Route::all();
    $students = Student::all();
    $vehicles = Vehicle::all(); // since you're using this in the form too

    return view('routes.index', compact('routes', 'students', 'vehicles'));
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

