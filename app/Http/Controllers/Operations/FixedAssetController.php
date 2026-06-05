<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\FixedAsset;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FixedAssetController extends Controller
{
    public function index(Request $request)
    {
        $query = FixedAsset::with('assignedStaff')->orderBy('name');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $assets = $query->paginate(30);

        return view('operations.assets.index', compact('assets'));
    }

    public function create()
    {
        $staff = Staff::orderBy('first_name')->get();

        return view('operations.assets.create', compact('staff'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_tag' => 'required|string|max:100|unique:fixed_assets,asset_tag',
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,in_repair,retired,disposed',
            'assigned_staff_id' => 'nullable|exists:staff,id',
            'notes' => 'nullable|string',
        ]);

        FixedAsset::create([
            ...$validated,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('operations.assets.index')
            ->with('success', 'Asset registered successfully.');
    }
}
