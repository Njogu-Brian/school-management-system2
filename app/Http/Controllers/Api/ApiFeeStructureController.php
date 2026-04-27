<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeeStructure;
use Illuminate\Http\Request;

/**
 * Read-only fee structures for mobile finance (parity with web index data).
 */
class ApiFeeStructureController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Finance Officer', 'Accountant'])) {
            abort(403, 'You do not have permission to view fee structures.');
        }

        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
        $query = FeeStructure::query()
            ->with(['classroom', 'term', 'academicYear', 'charges.votehead'])
            ->when($request->filled('class_id'), fn ($q) => $q->where('classroom_id', (int) $request->class_id))
            ->when($request->filled('academic_year_id'), fn ($q) => $q->where('academic_year_id', (int) $request->academic_year_id))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = '%'.addcslashes($request->search, '%_\\').'%';
                $q->where('name', 'like', $s);
            })
            ->orderByDesc('id');

        $paginated = $query->paginate($perPage);

        $rows = $paginated->getCollection()->map(function (FeeStructure $fs) {
            $total = (float) $fs->charges->sum('amount');

            return [
                'id' => $fs->id,
                'name' => $fs->name ?? 'Fee structure #'.$fs->id,
                'class_id' => (int) $fs->classroom_id,
                'class_name' => $fs->classroom?->name,
                'term_id' => $fs->term_id ? (int) $fs->term_id : null,
                'academic_year_id' => $fs->academic_year_id ? (int) $fs->academic_year_id : null,
                'total_amount' => round($total, 2),
                'status' => $fs->is_active ? 'active' : 'inactive',
                'created_at' => $fs->created_at?->toIso8601String() ?? '',
                'updated_at' => $fs->updated_at?->toIso8601String() ?? '',
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $rows,
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
