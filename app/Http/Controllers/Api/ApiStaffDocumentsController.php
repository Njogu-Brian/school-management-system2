<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\StaffDocument;
use Illuminate\Http\Request;

class ApiStaffDocumentsController extends Controller
{
    public function index(Request $request, int $id)
    {
        $staff = Staff::findOrFail($id);
        $perPage = (int) $request->input('per_page', 30);

        $paginated = StaffDocument::query()
            ->where('staff_id', $staff->id)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $data = $paginated->getCollection()->map(fn (StaffDocument $doc) => [
            'id' => $doc->id,
            'staff_id' => $doc->staff_id,
            'document_type' => $doc->document_type,
            'title' => $doc->title,
            'description' => $doc->description,
            'file_path' => $doc->file_path,
            'file_url' => $doc->file_url,
            'expiry_date' => $doc->expiry_date?->format('Y-m-d'),
            'is_expired' => $doc->isExpired(),
            'is_expiring_soon' => $doc->isExpiringSoon(),
            'created_at' => $doc->created_at?->toIso8601String(),
            'updated_at' => $doc->updated_at?->toIso8601String(),
        ])->values();

        return response()->json([
            'success' => true,
            'data' => [
                'staff_id' => $staff->id,
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
