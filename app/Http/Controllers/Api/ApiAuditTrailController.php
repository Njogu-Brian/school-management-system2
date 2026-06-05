<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Unified audit trail for the Admin mobile app (activity_logs + audit_logs).
 */
class ApiAuditTrailController extends Controller
{
    public function index(Request $request)
    {
        $this->assertAuditAccess($request);

        $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'user_id' => 'nullable|integer',
            'module' => 'nullable|string|max:60',
            'search' => 'nullable|string|max:120',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $perPage = (int) $request->input('per_page', 30);
        $page = (int) $request->input('page', 1);
        $rows = collect();

        if (Schema::hasTable('activity_logs')) {
            $activityQuery = ActivityLog::with('user')->latest();
            if ($request->filled('user_id')) {
                $activityQuery->where('user_id', $request->user_id);
            }
            if ($request->filled('module')) {
                $module = $request->string('module');
                $activityQuery->where(function ($q) use ($module) {
                    $q->where('model_type', 'like', "%{$module}%")
                        ->orWhere('action', 'like', "%{$module}%");
                });
            }
            if ($request->filled('search')) {
                $s = '%'.addcslashes($request->search, '%_\\').'%';
                $activityQuery->where(function ($q) use ($s) {
                    $q->where('description', 'like', $s)
                        ->orWhere('action', 'like', $s);
                });
            }
            if ($request->filled('date_from')) {
                $activityQuery->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $activityQuery->whereDate('created_at', '<=', $request->date_to);
            }

            $rows = $rows->merge(
                $activityQuery->limit(500)->get()->map(fn ($log) => $this->formatActivityLog($log))
            );
        }

        if (Schema::hasTable('audit_logs')) {
            $auditQuery = AuditLog::query()->latest();
            if ($request->filled('user_id')) {
                $auditQuery->where('user_id', $request->user_id);
            }
            if ($request->filled('module')) {
                $module = $request->string('module');
                $auditQuery->where(function ($q) use ($module) {
                    $q->where('auditable_type', 'like', "%{$module}%")
                        ->orWhere('event', 'like', "%{$module}%")
                        ->orWhereJsonContains('tags', $module);
                });
            }
            if ($request->filled('search')) {
                $s = '%'.addcslashes($request->search, '%_\\').'%';
                $auditQuery->where('event', 'like', $s);
            }
            if ($request->filled('date_from')) {
                $auditQuery->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $auditQuery->whereDate('created_at', '<=', $request->date_to);
            }

            $rows = $rows->merge(
                $auditQuery->limit(500)->get()->map(fn ($log) => $this->formatAuditLog($log))
            );
        }

        $sorted = $rows->sortByDesc('timestamp')->values();
        $total = $sorted->count();
        $offset = ($page - 1) * $perPage;
        $pageItems = $sorted->slice($offset, $perPage)->values();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $pageItems,
                'current_page' => $page,
                'last_page' => max(1, (int) ceil($total / $perPage)),
                'per_page' => $perPage,
                'total' => $total,
            ],
        ]);
    }

    public function show(Request $request, string $id)
    {
        $this->assertAuditAccess($request);

        if (str_starts_with($id, 'activity-')) {
            $log = ActivityLog::with('user')->findOrFail((int) str_replace('activity-', '', $id));

            return response()->json([
                'success' => true,
                'data' => $this->formatActivityLog($log, true),
            ]);
        }

        if (str_starts_with($id, 'audit-')) {
            $log = AuditLog::findOrFail((int) str_replace('audit-', '', $id));

            return response()->json([
                'success' => true,
                'data' => $this->formatAuditLog($log, true),
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Invalid audit id.'], 404);
    }

    private function assertAuditAccess(Request $request): void
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->can('audit_logs.view') && ! $user->hasAnyRole(['Super Admin', 'Admin'])) {
            abort(403, 'You do not have permission to view the audit trail.');
        }
    }

    private function formatActivityLog(ActivityLog $log, bool $detail = false): array
    {
        $module = $this->resolveModule($log->model_type, $log->action);
        $row = [
            'id' => 'activity-'.$log->id,
            'user' => $log->user?->name ?? 'System',
            'user_id' => $log->user_id,
            'action' => $log->action,
            'module' => $module,
            'timestamp' => $log->created_at?->toIso8601String(),
            'target' => $log->description ?? $this->shortClass($log->model_type).' #'.$log->model_id,
            'source' => 'activity',
        ];
        if ($detail) {
            $row['before_values'] = $log->old_values;
            $row['after_values'] = $log->new_values;
            $row['metadata'] = [
                'model_type' => $log->model_type,
                'model_id' => $log->model_id,
                'route' => $log->route,
                'method' => $log->method,
            ];
        }

        return $row;
    }

    private function formatAuditLog(AuditLog $log, bool $detail = false): array
    {
        $module = $this->resolveModule($log->auditable_type, $log->event);
        $row = [
            'id' => 'audit-'.$log->id,
            'user' => $log->user_id ? (User::find($log->user_id)?->name ?? 'User #'.$log->user_id) : 'System',
            'user_id' => $log->user_id,
            'action' => $log->event,
            'module' => $module,
            'timestamp' => $log->created_at?->toIso8601String(),
            'target' => $this->shortClass($log->auditable_type).' #'.$log->auditable_id,
            'source' => 'audit',
        ];
        if ($detail) {
            $row['before_values'] = $log->old_values;
            $row['after_values'] = $log->new_values;
            $row['metadata'] = ['tags' => $log->tags];
        }

        return $row;
    }

    private function resolveModule(?string $modelType, ?string $action): string
    {
        $hay = strtolower(($modelType ?? '').' '.($action ?? ''));
        if (str_contains($hay, 'payment') || str_contains($hay, 'invoice') || str_contains($hay, 'finance')) {
            return 'Finance';
        }
        if (str_contains($hay, 'admission')) {
            return 'Admissions';
        }
        if (str_contains($hay, 'student')) {
            return 'Students';
        }
        if (str_contains($hay, 'staff')) {
            return 'HR';
        }
        if (str_contains($hay, 'visitor')) {
            return 'Visitors';
        }
        if (str_contains($hay, 'inventory') || str_contains($hay, 'requisition')) {
            return 'Inventory';
        }
        if (str_contains($hay, 'announcement') || str_contains($hay, 'sms')) {
            return 'Communication';
        }
        if (str_contains($hay, 'login')) {
            return 'Security';
        }
        if (str_contains($hay, 'approv')) {
            return 'Approvals';
        }

        return 'System';
    }

    private function shortClass(?string $class): string
    {
        if (! $class) {
            return 'Record';
        }
        $parts = explode('\\', $class);

        return end($parts);
    }
}
