<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiAppAdoptionController extends Controller
{
    protected function assertAdmin(Request $request): void
    {
        $user = $request->user();
        if (! $user || ! $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary'])) {
            abort(403, 'You do not have permission to view app adoption.');
        }
    }

    /**
     * GET /api/admin/app-adoption
     * audience=staff|parents, status=never|used|active|all, days=7, q=
     */
    public function index(Request $request)
    {
        $this->assertAdmin($request);

        $audience = $request->input('audience', 'staff');
        $status = $request->input('status', 'all');
        $days = max(1, min(90, (int) $request->input('days', 7)));
        $q = trim((string) $request->input('q', ''));
        $perPage = min(100, max(10, (int) $request->input('per_page', 40)));

        $query = User::query()->with('roles');

        if ($audience === 'parents') {
            $query->whereNotNull('parent_id')
                ->whereHas('roles', fn ($r) => $r->whereIn('name', ['Parent', 'Guardian']));
        } else {
            $staffUserIds = Staff::query()
                ->whereNotNull('user_id')
                ->pluck('user_id')
                ->unique()
                ->filter()
                ->values()
                ->all();
            $query->where(function ($w) use ($staffUserIds) {
                $w->whereIn('id', $staffUserIds)
                    ->orWhereHas('roles', fn ($r) => $r->whereIn('name', [
                        'Teacher', 'teacher', 'Senior Teacher', 'senior teacher',
                        'Admin', 'Super Admin', 'Secretary', 'Accountant', 'Finance', 'Driver',
                    ]));
            });
        }

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone_number', 'like', "%{$q}%");
            });
        }

        if ($status === 'never') {
            $query->whereNull('last_login_at');
        } elseif ($status === 'used') {
            $query->whereNotNull('last_login_at');
        } elseif ($status === 'active') {
            $query->where('last_seen_at', '>=', now()->subDays($days));
        }

        $paginated = $query->orderByRaw('last_seen_at IS NULL')
            ->orderByDesc('last_seen_at')
            ->orderBy('name')
            ->paginate($perPage);

        $userIds = collect($paginated->items())->pluck('id')->all();
        $activeTokenIds = [];
        if ($userIds !== []) {
            $activeTokenIds = DB::table('personal_access_tokens')
                ->where('tokenable_type', User::class)
                ->whereIn('tokenable_id', $userIds)
                ->where(function ($w) {
                    $w->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->pluck('tokenable_id')
                ->unique()
                ->all();
        }
        $activeSet = array_flip($activeTokenIds);

        $staffByUser = [];
        if ($audience !== 'parents' && $userIds !== []) {
            $staffByUser = Staff::query()
                ->whereIn('user_id', $userIds)
                ->get(['id', 'user_id', 'staff_id', 'first_name', 'last_name'])
                ->keyBy('user_id');
        }

        $rows = collect($paginated->items())->map(function (User $u) use ($activeSet, $staffByUser, $audience) {
            $staff = $staffByUser[$u->id] ?? null;
            $roleNames = $u->getRoleNames()->values()->all();

            return [
                'user_id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone_number,
                'roles' => $roleNames,
                'parent_id' => $u->parent_id,
                'staff_id' => $staff?->id,
                'employee_number' => $staff?->staff_id,
                'last_login_at' => $u->last_login_at?->toIso8601String(),
                'last_seen_at' => $u->last_seen_at?->toIso8601String(),
                'has_active_token' => isset($activeSet[$u->id]),
                'audience' => $audience,
            ];
        });

        $summaryQuery = clone $query;
        // Rebuild summary without status filter for counts
        $base = User::query();
        if ($audience === 'parents') {
            $base->whereNotNull('parent_id')
                ->whereHas('roles', fn ($r) => $r->whereIn('name', ['Parent', 'Guardian']));
        } else {
            $staffUserIds = Staff::query()->whereNotNull('user_id')->pluck('user_id')->unique()->filter()->values()->all();
            $base->where(function ($w) use ($staffUserIds) {
                $w->whereIn('id', $staffUserIds)
                    ->orWhereHas('roles', fn ($r) => $r->whereIn('name', [
                        'Teacher', 'teacher', 'Senior Teacher', 'senior teacher',
                        'Admin', 'Super Admin', 'Secretary', 'Accountant', 'Finance', 'Driver',
                    ]));
            });
        }

        $summary = [
            'never' => (clone $base)->whereNull('last_login_at')->count(),
            'used' => (clone $base)->whereNotNull('last_login_at')->count(),
            'active' => (clone $base)->where('last_seen_at', '>=', now()->subDays($days))->count(),
            'total' => (clone $base)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $rows,
                'summary' => $summary,
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }
}
