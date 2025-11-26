<?php

namespace App\Http\Controllers\Hostel;

use App\Http\Controllers\Controller;
use App\Models\Hostel;
use App\Models\Staff;
use Illuminate\Http\Request;

class HostelController extends Controller
{
    public function index(Request $request)
    {
        $query = Hostel::with(['warden', 'rooms']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        $hostels = $query->latest()->paginate(20)->withQueryString();

        return view('hostel.hostels.index', compact('hostels'));
    }

    public function create()
    {
        $wardens = Staff::whereHas('user', function ($q) {
            $q->whereHas('roles', function ($r) {
                $r->whereIn('name', ['Super Admin', 'Admin', 'Teacher']);
            });
        })->get();

        return view('hostel.hostels.create', compact('wardens'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:boys,girls,mixed',
            'capacity' => 'required|integer|min:1',
            'warden_id' => 'nullable|exists:staff,id',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $hostel = Hostel::create($validated);

        return redirect()
            ->route('hostel.hostels.show', $hostel)
            ->with('success', 'Hostel created successfully.');
    }

    public function show(Hostel $hostel)
    {
        $hostel->load(['warden', 'rooms', 'allocations.student', 'fees']);
        return view('hostel.hostels.show', compact('hostel'));
    }

    public function edit(Hostel $hostel)
    {
        $wardens = Staff::all();
        return view('hostel.hostels.edit', compact('hostel', 'wardens'));
    }

    public function update(Request $request, Hostel $hostel)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:boys,girls,mixed',
            'capacity' => 'required|integer|min:1',
            'warden_id' => 'nullable|exists:staff,id',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $hostel->update($validated);

        return redirect()
            ->route('hostel.hostels.show', $hostel)
            ->with('success', 'Hostel updated successfully.');
    }
}

