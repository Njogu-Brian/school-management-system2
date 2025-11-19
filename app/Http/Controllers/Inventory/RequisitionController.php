<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Models\InventoryItem;
use App\Models\RequirementType;
use App\Models\InventoryTransaction;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RequisitionController extends Controller
{
    public function index(Request $request)
    {
        $query = Requisition::with(['requestedBy', 'approvedBy', 'items']);

        // Teachers see only their requisitions
        if (Auth::user()->hasRole('Teacher') || Auth::user()->hasRole('teacher')) {
            $query->where('requested_by', Auth::id());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $requisitions = $query->latest()->paginate(20);

        return view('inventory.requisitions.index', compact('requisitions'));
    }

    public function create()
    {
        $inventoryItems = InventoryItem::active()->orderBy('name')->get();
        $requirementTypes = RequirementType::active()->orderBy('name')->get();
        return view('inventory.requisitions.create', compact('inventoryItems', 'requirementTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:inventory,requirement',
            'purpose' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required_if:type,inventory|exists:inventory_items,id',
            'items.*.requirement_type_id' => 'required_if:type,requirement|exists:requirement_types,id',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.brand' => 'nullable|string|max:255',
            'items.*.quantity_requested' => 'required|numeric|min:0',
            'items.*.unit' => 'required|string|max:50',
            'items.*.purpose' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $requisition = Requisition::create([
                'requested_by' => Auth::id(),
                'type' => $validated['type'],
                'purpose' => $validated['purpose'],
                'status' => 'pending',
            ]);

            foreach ($validated['items'] as $itemData) {
                RequisitionItem::create([
                    'requisition_id' => $requisition->id,
                    'inventory_item_id' => $itemData['inventory_item_id'] ?? null,
                    'requirement_type_id' => $itemData['requirement_type_id'] ?? null,
                    'item_name' => $itemData['item_name'],
                    'brand' => $itemData['brand'] ?? null,
                    'quantity_requested' => $itemData['quantity_requested'],
                    'unit' => $itemData['unit'],
                    'purpose' => $itemData['purpose'] ?? null,
                ]);
            }

            DB::commit();

            ActivityLog::log('create', $requisition, "Created requisition: {$requisition->requisition_number}");

            return redirect()->route('inventory.requisitions.index')
                ->with('success', 'Requisition created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error creating requisition: ' . $e->getMessage());
        }
    }

    public function show(Requisition $requisition)
    {
        $requisition->load(['requestedBy', 'approvedBy', 'items.inventoryItem', 'items.requirementType']);
        return view('inventory.requisitions.show', compact('requisition'));
    }

    public function approve(Request $request, Requisition $requisition)
    {
        if ($requisition->status !== 'pending') {
            return back()->with('error', 'Only pending requisitions can be approved.');
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:requisition_items,id',
            'items.*.quantity_approved' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['items'] as $itemData) {
                $requisitionItem = RequisitionItem::findOrFail($itemData['id']);
                $requisitionItem->update([
                    'quantity_approved' => $itemData['quantity_approved'],
                ]);
            }

            $requisition->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            DB::commit();

            ActivityLog::log('update', $requisition, "Approved requisition: {$requisition->requisition_number}");

            return back()->with('success', 'Requisition approved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error approving requisition: ' . $e->getMessage());
        }
    }

    public function fulfill(Request $request, Requisition $requisition)
    {
        if ($requisition->status !== 'approved') {
            return back()->with('error', 'Only approved requisitions can be fulfilled.');
        }

        DB::beginTransaction();
        try {
            foreach ($requisition->items as $item) {
                if ($item->inventory_item_id && $item->quantity_approved > 0) {
                    $inventoryItem = InventoryItem::findOrFail($item->inventory_item_id);
                    
                    if ($inventoryItem->quantity < $item->quantity_approved) {
                        throw new \Exception("Insufficient stock for {$inventoryItem->name}. Available: {$inventoryItem->quantity}, Required: {$item->quantity_approved}");
                    }

                    // Create outbound transaction
                    InventoryTransaction::create([
                        'inventory_item_id' => $inventoryItem->id,
                        'user_id' => Auth::id(),
                        'requisition_id' => $requisition->id,
                        'type' => 'out',
                        'quantity' => $item->quantity_approved,
                        'notes' => "Fulfilled requisition: {$requisition->requisition_number}",
                    ]);

                    $item->update(['quantity_issued' => $item->quantity_approved]);
                }
            }

            $requisition->update([
                'status' => 'fulfilled',
                'fulfilled_at' => now(),
            ]);

            DB::commit();

            ActivityLog::log('update', $requisition, "Fulfilled requisition: {$requisition->requisition_number}");

            return back()->with('success', 'Requisition fulfilled successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error fulfilling requisition: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, Requisition $requisition)
    {
        if ($requisition->status !== 'pending') {
            return back()->with('error', 'Only pending requisitions can be rejected.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $requisition->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        ActivityLog::log('update', $requisition, "Rejected requisition: {$requisition->requisition_number}");

        return back()->with('success', 'Requisition rejected.');
    }
}
