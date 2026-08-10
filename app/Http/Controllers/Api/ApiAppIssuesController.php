<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppClientIssue;
use Illuminate\Http\Request;

class ApiAppIssuesController extends Controller
{
    /**
     * POST /api/app-issues — mobile crash / client issue ingest.
     * Auth is optional (login-screen crashes may be anonymous).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'app' => 'nullable|string|in:users,admin',
            'platform' => 'nullable|string|max:32',
            'app_version' => 'nullable|string|max:64',
            'role' => 'nullable|string|max:64',
            'message' => 'required|string|max:1000',
            'stack' => 'nullable|string|max:20000',
            'component_stack' => 'nullable|string|max:20000',
            'extra' => 'nullable|array',
        ]);

        $user = $request->user();
        if (! $user && $request->bearerToken()) {
            $access = \Laravel\Sanctum\PersonalAccessToken::findToken($request->bearerToken());
            $user = $access?->tokenable instanceof \App\Models\User ? $access->tokenable : null;
        }

        $issue = AppClientIssue::create([
            'user_id' => $user?->id,
            'app' => $data['app'] ?? 'users',
            'platform' => $data['platform'] ?? null,
            'app_version' => $data['app_version'] ?? null,
            'role' => $data['role'] ?? ($user?->getRoleNames()->first()),
            'message' => $data['message'],
            'stack' => $data['stack'] ?? null,
            'component_stack' => $data['component_stack'] ?? null,
            'extra' => $data['extra'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Issue recorded.',
            'data' => ['id' => $issue->id],
        ], 201);
    }

    /**
     * GET /api/admin/app-issues
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary'])) {
            abort(403, 'You do not have permission to view app issues.');
        }

        $perPage = min(100, max(10, (int) $request->input('per_page', 40)));
        $app = $request->input('app');

        $query = AppClientIssue::query()->with('user:id,name,email')->orderByDesc('id');
        if (in_array($app, ['users', 'admin'], true)) {
            $query->where('app', $app);
        }

        $paginated = $query->paginate($perPage);
        $items = collect($paginated->items())->map(fn (AppClientIssue $i) => [
            'id' => $i->id,
            'user_id' => $i->user_id,
            'user_name' => $i->user?->name,
            'user_email' => $i->user?->email,
            'app' => $i->app,
            'platform' => $i->platform,
            'app_version' => $i->app_version,
            'role' => $i->role,
            'message' => $i->message,
            'stack' => $i->stack,
            'component_stack' => $i->component_stack,
            'extra' => $i->extra,
            'created_at' => $i->created_at?->toIso8601String(),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $items,
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }
}
