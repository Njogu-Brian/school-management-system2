<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Pos\Discount;
use App\Models\Academics\Classroom;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function index(Request $request)
    {
        $query = Discount::with('classroom');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->boolean('active_only')) {
            $query->valid();
        }

        $discounts = $query->latest()->paginate(30);

        ActivityLog::log('view', null, 'Viewed POS discounts list');

        return view('pos.discounts.index', compact('discounts'));
    }

    public function create()
    {
        $classrooms = Classroom::orderBy('name')->get();

        return view('pos.discounts.create', compact('classrooms'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255|unique:pos_discounts,code',
            'type' => 'required|in:percentage,fixed,bundle',
            'value' => 'required|numeric|min:0',
            'scope' => 'required|in:all,category,product,class_bundle',
            'category' => 'nullable|string|max:255',
            'product_ids' => 'nullable|array',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'min_purchase_amount' => 'nullable|numeric|min:0',
            'min_quantity' => 'nullable|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'usage_limit' => 'nullable|integer|min:1',
            'per_user_limit' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $discount = Discount::create($validated);

        ActivityLog::log('create', $discount, "Created discount: {$discount->name}");

        return redirect()->route('pos.discounts.index')
            ->with('success', 'Discount created successfully.');
    }

    public function edit(Discount $discount)
    {
        $classrooms = Classroom::orderBy('name')->get();

        return view('pos.discounts.edit', compact('discount', 'classrooms'));
    }

    public function update(Request $request, Discount $discount)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255|unique:pos_discounts,code,' . $discount->id,
            'type' => 'required|in:percentage,fixed,bundle',
            'value' => 'required|numeric|min:0',
            'scope' => 'required|in:all,category,product,class_bundle',
            'category' => 'nullable|string|max:255',
            'product_ids' => 'nullable|array',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'min_purchase_amount' => 'nullable|numeric|min:0',
            'min_quantity' => 'nullable|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'usage_limit' => 'nullable|integer|min:1',
            'per_user_limit' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $discount->update($validated);

        ActivityLog::log('update', $discount, "Updated discount: {$discount->name}");

        return redirect()->route('pos.discounts.index')
            ->with('success', 'Discount updated successfully.');
    }

    public function destroy(Discount $discount)
    {
        $discountName = $discount->name;
        $discount->delete();

        ActivityLog::log('delete', null, "Deleted discount: {$discountName}");

        return redirect()->route('pos.discounts.index')
            ->with('success', 'Discount deleted successfully.');
    }
}



