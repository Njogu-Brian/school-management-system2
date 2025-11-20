<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryItemController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryItem::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('low_stock')) {
            $query->whereRaw('quantity <= min_stock_level');
        }

        $items = $query->orderBy('name')->paginate(30);
        $categories = InventoryItem::distinct()->pluck('category')->filter();

        ActivityLog::log('view', null, 'Viewed inventory items list');

        return view('inventory.items.index', compact('items', 'categories'));
    }

    public function create()
    {
        return view('inventory.items.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'quantity' => 'required|numeric|min:0',
            'min_stock_level' => 'nullable|numeric|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
        ]);

        $initialQuantity = $validated['quantity'];
        $validated['quantity'] = 0;

        $item = InventoryItem::create($validated);

        // Log initial stock as transaction
        if ($initialQuantity > 0) {
            InventoryTransaction::create([
                'inventory_item_id' => $item->id,
                'user_id' => auth()->id(),
                'type' => 'in',
                'quantity' => $initialQuantity,
                'unit_cost' => $validated['unit_cost'] ?? null,
                'notes' => 'Initial stock',
            ]);
        }

        ActivityLog::log('create', $item, "Created inventory item: {$item->name}");

        return redirect()->route('inventory.items.index')
            ->with('success', 'Inventory item created successfully.');
    }

    public function show(InventoryItem $item)
    {
        $item->load(['transactions.user']);
        $recentTransactions = $item->transactions()->latest()->take(20)->get();

        return view('inventory.items.show', compact('item', 'recentTransactions'));
    }

    public function edit(InventoryItem $item)
    {
        return view('inventory.items.edit', compact('item'));
    }

    public function update(Request $request, InventoryItem $item)
    {
        $oldValues = $item->toArray();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'quantity' => 'required|numeric|min:0',
            'min_stock_level' => 'nullable|numeric|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $item->update($validated);

        ActivityLog::log('update', $item, "Updated inventory item: {$item->name}", $oldValues, $item->toArray());

        return redirect()->route('inventory.items.index')
            ->with('success', 'Inventory item updated successfully.');
    }

    public function destroy(InventoryItem $item)
    {
        $name = $item->name;
        $item->delete();

        ActivityLog::log('delete', null, "Deleted inventory item: {$name}");

        return redirect()->route('inventory.items.index')
            ->with('success', 'Inventory item deleted successfully.');
    }

    public function adjustStock(Request $request, InventoryItem $item)
    {
        $validated = $request->validate([
            'type' => 'required|in:in,out,adjustment',
            'quantity' => 'required|numeric|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $transaction = InventoryTransaction::create([
            'inventory_item_id' => $item->id,
            'user_id' => auth()->id(),
            'type' => $validated['type'],
            'quantity' => $validated['quantity'],
            'unit_cost' => $validated['unit_cost'],
            'notes' => $validated['notes'],
        ]);

        // Update item quantity (handled by model boot)
        $item->refresh();

        ActivityLog::log('update', $item, "Adjusted stock for {$item->name}: {$validated['type']} {$validated['quantity']}");

        return back()->with('success', 'Stock adjusted successfully.');
    }
}
