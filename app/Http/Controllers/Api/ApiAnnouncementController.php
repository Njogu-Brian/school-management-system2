<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class ApiAnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);

        $query = Announcement::where('active', 1)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });

        if ($request->filled('status')) {
            if ($request->status === 'published') {
                $query->where('active', 1);
            }
        }

        $paginated = $query->latest()->paginate($perPage);

        $data = $paginated->getCollection()->map(fn($a) => [
            'id' => $a->id,
            'title' => $a->title ?? '',
            'content' => $a->content ?? '',
            'expires_at' => $a->expires_at?->format('Y-m-d'),
            'created_at' => $a->created_at->toIso8601String(),
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
