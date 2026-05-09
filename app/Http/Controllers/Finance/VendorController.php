<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorController extends Controller
{
    public function index(): View
    {
        $vendors = Vendor::orderBy('name')->paginate(25);
        return view('finance.vendors.index', compact('vendors'));
    }

    public function create(): View
    {
        return view('finance.vendors.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'tax_pin' => 'nullable|string|max:255',
            'payable_terms' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);

        Vendor::create($validated);
        return redirect()->route('finance.vendors.index')->with('success', 'Vendor created.');
    }

    public function edit(Vendor $vendor): View
    {
        return view('finance.vendors.edit', compact('vendor'));
    }

    public function update(Request $request, Vendor $vendor): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'tax_pin' => 'nullable|string|max:255',
            'payable_terms' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);

        $vendor->update($validated);
        return redirect()->route('finance.vendors.index')->with('success', 'Vendor updated.');
    }
}
