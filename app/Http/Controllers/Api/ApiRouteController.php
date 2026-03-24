<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Route as TransportRouteModel;
use Illuminate\Http\Request;

class ApiRouteController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);

        $query = TransportRouteModel::query();

        if ($request->filled('search')) {
            $search = '%' . addcslashes($request->search, '%_\\') . '%';
            $query->where('name', 'like', $search);
        }

        $paginated = $query->orderBy('name')->paginate($perPage);

        $data = $paginated->getCollection()->map(fn($r) => [
            'id' => $r->id,
            'name' => $r->name ?? '',
            'description' => $r->area ?? null,
            'status' => 'active',
        ])->values();

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
}
