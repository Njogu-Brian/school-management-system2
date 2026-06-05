<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FixedAsset;
use Illuminate\Http\Request;

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
                'notes' => $a->notes,
            ];
        }

        return $payload;
    }
}
