<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Http\Request;

class ApiInventoryController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 30), 100);
        $query = InventoryItem::query()->active();

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('low_stock')) {
            $query->whereRaw('quantity <= min_stock_level');
        }

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        $paginated = $query->orderBy('name')->paginate($perPage);

        $data = $paginated->getCollection()->map(fn (InventoryItem $item) => $this->serialize($item))->values();

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

    public function show(int $id)
    {
        $item = InventoryItem::active()->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->serialize($item),
        ]);
    }

    protected function serialize(InventoryItem $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'category' => $item->category,
            'brand' => $item->brand,
            'description' => $item->description,
            'unit' => $item->unit,
            'quantity' => (float) $item->quantity,
            'min_stock_level' => (float) $item->min_stock_level,
            'unit_cost' => $item->unit_cost !== null ? (float) $item->unit_cost : null,
            'location' => $item->location,
            'is_low_stock' => $item->isLowStock(),
            'is_active' => (bool) $item->is_active,
            'updated_at' => $item->updated_at?->toIso8601String(),
        ];
    }
}
