<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Services\ExpoPushService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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

    /**
     * Guest-facing active announcements for the login screen.
     */
    public function publicIndex(Request $request)
    {
        $limit = min((int) $request->input('limit', 5), 10);
        $items = Announcement::where('active', 1)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->title ?? '',
                'content' => Str::limit(trim(strip_tags($a->content ?? '')), 180),
                'created_at' => $a->created_at?->toIso8601String(),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function show(int $id)
    {
        $announcement = Announcement::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->serialize($announcement),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if ($user?->hasRole('Teacher') || $user?->hasRole('teacher')) {
            return response()->json(['success' => false, 'message' => 'Permission denied.'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'active' => 'required|boolean',
            'expires_at' => 'nullable|date',
        ]);

        $announcement = Announcement::create($validated);

        if ($announcement->active) {
            app(ExpoPushService::class)->sendAnnouncementNotification($announcement);
        }

        return response()->json([
            'success' => true,
            'message' => 'Announcement created.',
            'data' => $this->serialize($announcement),
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $user = $request->user();
        if ($user?->hasRole('Teacher') || $user?->hasRole('teacher')) {
            return response()->json(['success' => false, 'message' => 'Permission denied.'], 403);
        }

        $announcement = Announcement::findOrFail($id);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'active' => 'required|boolean',
            'expires_at' => 'nullable|date',
        ]);

        $wasInactive = ! $announcement->active;
        $announcement->update($validated);

        if ($request->boolean('active') && $wasInactive) {
            app(ExpoPushService::class)->sendAnnouncementNotification($announcement->fresh());
        }

        return response()->json([
            'success' => true,
            'message' => 'Announcement updated.',
            'data' => $this->serialize($announcement->fresh()),
        ]);
    }

    public function destroy(int $id)
    {
        $announcement = Announcement::findOrFail($id);
        $announcement->delete();

        return response()->json([
            'success' => true,
            'message' => 'Announcement deleted.',
        ]);
    }

    protected function serialize(Announcement $a): array
    {
        return [
            'id' => $a->id,
            'title' => $a->title ?? '',
            'content' => $a->content ?? '',
            'active' => (bool) $a->active,
            'expires_at' => $a->expires_at?->format('Y-m-d'),
            'created_at' => $a->created_at?->toIso8601String(),
            'updated_at' => $a->updated_at?->toIso8601String(),
        ];
    }
}
