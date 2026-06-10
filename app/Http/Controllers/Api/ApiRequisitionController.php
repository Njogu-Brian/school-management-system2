<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Inventory\RequisitionController as WebRequisitionController;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApiRequisitionController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 20), 100);
        $query = Requisition::with(['requestedBy', 'approvedBy', 'items']);

        if (Auth::user()?->hasRole('Teacher') || Auth::user()?->hasRole('teacher')) {
            $query->where('requested_by', Auth::id());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        $paginated = $query->latest()->paginate($perPage);
        $data = $paginated->getCollection()->map(fn (Requisition $r) => $this->serialize($r, false))->values();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $data,
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:inventory,requirement',
            'purpose' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'nullable|exists:inventory_items,id',
            'items.*.requirement_type_id' => 'nullable|exists:requirement_types,id',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.brand' => 'nullable|string|max:255',
            'items.*.quantity_requested' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string|max:50',
            'items.*.purpose' => 'nullable|string',
        ]);

        $requisition = DB::transaction(function () use ($validated) {
            $requisition = Requisition::create([
                'requested_by' => Auth::id(),
                'type' => $validated['type'],
                'purpose' => $validated['purpose'] ?? null,
                'status' => 'pending',
                'requested_at' => now(),
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

            return $requisition;
        });

        return response()->json([
            'success' => true,
            'message' => 'Requisition submitted.',
            'data' => $this->serialize($requisition->fresh(['requestedBy', 'approvedBy', 'items']), true),
        ], 201);
    }

    public function show(int $id)
    {
        $requisition = Requisition::with([
            'requestedBy',
            'approvedBy',
            'items.inventoryItem',
            'items.requirementType',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->serialize($requisition, true),
        ]);
    }

    public function approve(Request $request, int $id)
    {
        $requisition = Requisition::with('items')->findOrFail($id);

        if ($requisition->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending requisitions can be approved.',
            ], 422);
        }

        $items = $request->input('items');
        if (! is_array($items) || count($items) === 0) {
            $items = $requisition->items->map(fn ($item) => [
                'id' => $item->id,
                'quantity_approved' => $item->quantity_requested,
            ])->all();
        }

        $request->merge(['items' => $items]);

        $web = app(WebRequisitionController::class);
        $web->approve($request, $requisition);

        return response()->json([
            'success' => true,
            'message' => 'Requisition approved.',
            'data' => $this->serialize($requisition->fresh(['requestedBy', 'approvedBy', 'items']), true),
        ]);
    }

    public function reject(Request $request, int $id)
    {
        $requisition = Requisition::findOrFail($id);
        $request->validate(['rejection_reason' => 'required|string|max:1000']);

        if ($requisition->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending requisitions can be rejected.',
            ], 422);
        }

        app(WebRequisitionController::class)->reject($request, $requisition);

        return response()->json([
            'success' => true,
            'message' => 'Requisition rejected.',
            'data' => $this->serialize($requisition->fresh(['requestedBy', 'approvedBy', 'items']), true),
        ]);
    }

    protected function serialize(Requisition $r, bool $includeItems): array
    {
        $payload = [
            'id' => $r->id,
            'requisition_number' => $r->requisition_number,
            'type' => $r->type,
            'purpose' => $r->purpose,
            'status' => $r->status,
            'rejection_reason' => $r->rejection_reason,
            'requested_by' => $r->requestedBy?->name,
            'approved_by' => $r->approvedBy?->name,
            'requested_at' => $r->requested_at?->toIso8601String(),
            'approved_at' => $r->approved_at?->toIso8601String(),
            'fulfilled_at' => $r->fulfilled_at?->toIso8601String(),
            'can_approve' => $r->status === 'pending',
            'can_reject' => $r->status === 'pending',
        ];

        if ($includeItems) {
            $payload['items'] = $r->items->map(fn ($item) => [
                'id' => $item->id,
                'item_name' => $item->item_name,
                'brand' => $item->brand,
                'quantity_requested' => (float) $item->quantity_requested,
                'quantity_approved' => $item->quantity_approved !== null ? (float) $item->quantity_approved : null,
                'quantity_issued' => $item->quantity_issued !== null ? (float) $item->quantity_issued : null,
                'unit' => $item->unit,
                'inventory_item_id' => $item->inventory_item_id,
                'requirement_type_id' => $item->requirement_type_id,
            ])->values();
        }

        return $payload;
    }
}
