<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FixedAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiFixedAssetsController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 30), 100);
        $query = FixedAsset::with('assignedStaff')->orderBy('name');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('asset_tag', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%");
            });
        }

        $paginated = $query->paginate($perPage);
        $data = $paginated->getCollection()->map(fn (FixedAsset $a) => $this->serialize($a))->values();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $data,
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    public function show(int $id)
    {
        $asset = FixedAsset::with('assignedStaff')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->serialize($asset, true),
        ]);
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
            'status' => 'nullable|in:active,in_repair,retired,disposed',
            'assigned_staff_id' => 'nullable|exists:staff,id',
            'notes' => 'nullable|string',
        ]);

        $asset = FixedAsset::create([
            ...$validated,
            'status' => $validated['status'] ?? 'active',
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Asset registered.',
            'data' => $this->serialize($asset->load('assignedStaff'), true),
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $asset = FixedAsset::findOrFail($id);

        $validated = $request->validate([
            'asset_tag' => 'required|string|max:100|unique:fixed_assets,asset_tag,'.$asset->id,
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:active,in_repair,retired,disposed',
            'assigned_staff_id' => 'nullable|exists:staff,id',
            'notes' => 'nullable|string',
        ]);

        $asset->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Asset updated.',
            'data' => $this->serialize($asset->fresh('assignedStaff'), true),
        ]);
    }

    public function updateStatus(Request $request, int $id)
    {
        $asset = FixedAsset::findOrFail($id);

        $data = $request->validate([
            'status' => 'required|in:active,in_repair,retired,disposed',
            'notes' => 'nullable|string|max:1000',
        ]);

        $asset->status = $data['status'];
        if (! empty($data['notes'])) {
            $stamp = now()->format('Y-m-d H:i');
            $asset->notes = trim(($asset->notes ? $asset->notes."\n" : '')."[{$stamp}] {$data['notes']}");
        }
        $asset->save();

        return response()->json([
            'success' => true,
            'message' => 'Asset status updated.',
            'data' => $this->serialize($asset->fresh('assignedStaff'), true),
        ]);
    }

    protected function serialize(FixedAsset $a, bool $detailed = false): array
    {
        $payload = [
            'id' => $a->id,
            'asset_tag' => $a->asset_tag,
            'name' => $a->name,
            'category' => $a->category,
            'location' => $a->location,
            'status' => $a->status,
            'assigned_to' => $a->assignedStaff?->full_name,
        ];

        if ($detailed) {
            $payload += [
                'serial_number' => $a->serial_number,
                'purchase_date' => $a->purchase_date?->format('Y-m-d'),
                'purchase_cost' => $a->purchase_cost !== null ? (float) $a->purchase_cost : null,
                'assigned_staff_id' => $a->assigned_staff_id,
                'notes' => $a->notes,
            ];
        }

        return $payload;
    }
}
