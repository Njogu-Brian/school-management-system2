<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ForcePasswordChangeService;
use Illuminate\Http\Request;

class ApiForcePasswordChangeController extends Controller
{
    public function __construct(private ForcePasswordChangeService $service)
    {
    }

    public function targets(Request $request)
    {
        if (! $this->canManage($request)) {
            return response()->json(['success' => false, 'message' => 'Not allowed.'], 403);
        }

        $group = $request->input('group', 'staff');
        if (! in_array($group, ['staff', 'parents', 'all'], true)) {
            $group = 'staff';
        }

        $page = $this->service->serializePage(
            $this->service->queryUsers($group, $request->input('q')),
            min(50, (int) $request->input('per_page', 30))
        );

        $page->getCollection()->transform(fn ($user) => $this->service->describeUser($user));

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $page->getCollection()->values(),
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function requireChange(Request $request)
    {
        if (! $this->canManage($request)) {
            return response()->json(['success' => false, 'message' => 'Not allowed.'], 403);
        }

        $validated = $request->validate([
            'group' => 'required|in:staff,parents,all',
            'all' => 'nullable|boolean',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer|exists:users,id',
            'q' => 'nullable|string|max:120',
        ]);

        if ($request->boolean('all')) {
            $ids = $this->service->queryUsers($validated['group'], $validated['q'] ?? null)
                ->limit(5000)
                ->pluck('id')
                ->all();
        } else {
            $ids = array_map('intval', $validated['user_ids'] ?? []);
        }

        if ($ids === []) {
            return response()->json(['success' => false, 'message' => 'Select at least one user.'], 422);
        }

        $count = $this->service->requireChange($ids);

        return response()->json([
            'success' => true,
            'message' => "{$count} user(s) will change password on next login.",
            'data' => ['count' => $count],
        ]);
    }

    private function canManage(Request $request): bool
    {
        $user = $request->user();

        return $user && $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'super admin', 'admin']);
    }
}
