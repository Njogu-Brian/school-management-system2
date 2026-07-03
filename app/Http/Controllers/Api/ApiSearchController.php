<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\FixedAsset;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\OnlineAdmission;
use App\Models\Payment;
use App\Models\Requisition;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use App\Models\VisitorLog;
use App\Models\Vehicle;
use App\Services\StudentSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Unified cross-module search for the Admin mobile app.
 */
class ApiSearchController extends Controller
{
    private const MODULE_MAP = [
        'students' => 'Students',
        'staff' => 'Staff',
        'admissions' => 'Admissions',
        'invoices' => 'Finance',
        'payments' => 'Finance',
        'visitors' => 'Operations',
        'assets' => 'Operations',
        'requisitions' => 'Operations',
        'inventory' => 'Operations',
        'announcements' => 'Communication',
        'vehicles' => 'Operations',
    ];

    public function index(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2|max:120',
            'module' => 'nullable|string|in:all,students,staff,finance,operations,communication,admissions,invoices,payments,visitors,assets,requisitions,inventory,announcements',
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        /** @var User $user */
        $user = $request->user();
        $query = trim($request->string('query'));
        $module = $request->input('module', 'all');
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 20);
        $like = '%'.addcslashes($query, '%_\\').'%';

        $modules = $this->resolveModules($module);
        $results = [];

        foreach ($modules as $key) {
            if (! $this->canSearchModule($user, $key)) {
                continue;
            }
            $hits = match ($key) {
                'students' => $this->searchStudents($user, $like, $page, $limit),
                'staff' => $this->searchStaff($like, $page, $limit),
                'admissions' => $this->searchAdmissions($like, $page, $limit),
                'invoices' => $this->searchInvoices($like, $page, $limit),
                'payments' => $this->searchPayments($like, $page, $limit),
                'visitors' => $this->searchVisitors($like, $page, $limit),
                'assets' => $this->searchAssets($like, $page, $limit),
                'requisitions' => $this->searchRequisitions($like, $page, $limit),
                'inventory' => $this->searchInventory($like, $page, $limit),
                'announcements' => $this->searchAnnouncements($like, $page, $limit),
                'vehicles' => $this->searchVehicles($like, $page, $limit),
                default => [],
            };
            $results = array_merge($results, $hits);
        }

        usort($results, fn ($a, $b) => strcmp($a['title'], $b['title']));

        $total = count($results);
        if ($module === 'all') {
            $offset = ($page - 1) * $limit;
            $results = array_slice($results, $offset, $limit);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'data' => array_values($results),
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'last_page' => max(1, (int) ceil($total / $limit)),
            ],
        ]);
    }

    public function suggest(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:1|max:80',
            'limit' => 'nullable|integer|min:1|max:10',
        ]);

        $request->merge(['page' => 1, 'limit' => (int) $request->input('limit', 6), 'module' => 'all']);
        $response = $this->index($request);
        $payload = $response->getData(true);

        return response()->json([
            'success' => true,
            'data' => array_map(fn ($row) => [
                'id' => $row['id'],
                'title' => $row['title'],
                'module' => $row['module'],
            ], $payload['data']['data'] ?? []),
        ]);
    }

    private function resolveModules(string $module): array
    {
        if ($module === 'all') {
            return ['students', 'staff', 'admissions', 'invoices', 'payments', 'visitors', 'assets', 'requisitions', 'inventory', 'announcements', 'vehicles'];
        }
        if ($module === 'finance') {
            return ['invoices', 'payments'];
        }
        if ($module === 'operations') {
            return ['visitors', 'assets', 'requisitions', 'inventory', 'vehicles'];
        }
        if ($module === 'communication') {
            return ['announcements'];
        }

        return [$module];
    }

    private function canSearchModule(User $user, string $module): bool
    {
        return match ($module) {
            'students' => $user->can('students.view') || $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Teacher', 'Senior Teacher']),
            'staff' => $user->can('people.view') || $user->can('staff.view') || $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary']),
            'admissions' => $user->can('admissions.view') || $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary']),
            'invoices', 'payments' => $user->can('finance.view') || $user->hasAnyRole(['Super Admin', 'Admin', 'Accountant', 'Finance']),
            'visitors', 'assets', 'requisitions', 'inventory', 'vehicles' => $user->can('operations.view') || $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary']),
            'announcements' => $user->can('communication.view') || $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary']),
            default => false,
        };
    }

    private function searchStudents(User $user, string $like, int $page, int $limit): array
    {
        $raw = trim(str_replace('%', '', $like));
        $q = Student::query()
            ->where('archive', 0)
            ->where('is_alumni', false);

        if ($raw !== '') {
            app(StudentSearchService::class)->applySearch($q, $raw);
        } else {
            $q->where(function ($q) use ($like) {
                $q->where('first_name', 'like', $like)
                    ->orWhere('middle_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('admission_number', 'like', $like);
            });
        }

        if ($user->hasTeacherLikeRole()) {
            $user->applyTeacherStudentFilter($q);
        }

        return $q->orderBy('first_name')
            ->paginate($limit, ['*'], 'page', $page)
            ->map(fn ($s) => [
                'id' => 'student-'.$s->id,
                'module' => 'Students',
                'title' => trim("{$s->first_name} {$s->last_name}"),
                'subtitle' => $s->admission_number,
                'route' => "students/{$s->id}",
                'metadata' => ['entity_type' => 'student', 'entity_id' => $s->id],
            ])
            ->values()
            ->all();
    }

    private function searchStaff(string $like, int $page, int $limit): array
    {
        return Staff::query()
            ->where(function ($q) use ($like) {
                $q->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('staff_id', 'like', $like)
                    ->orWhere('work_email', 'like', $like)
                    ->orWhere('personal_email', 'like', $like)
                    ->orWhere('phone_number', 'like', $like);
            })
            ->orderBy('first_name')
            ->paginate($limit, ['*'], 'page', $page)
            ->map(fn ($s) => [
                'id' => 'staff-'.$s->id,
                'module' => 'Staff',
                'title' => trim("{$s->first_name} {$s->last_name}"),
                'subtitle' => $s->staff_id ?? $s->job_title,
                'route' => "people/{$s->id}",
                'metadata' => ['entity_type' => 'staff', 'entity_id' => $s->id],
            ])
            ->values()
            ->all();
    }

    private function searchAdmissions(string $like, int $page, int $limit): array
    {
        return OnlineAdmission::query()
            ->where(function ($q) use ($like) {
                $q->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('nemis_number', 'like', $like);
            })
            ->orderByDesc('application_date')
            ->paginate($limit, ['*'], 'page', $page)
            ->map(fn ($a) => [
                'id' => 'admission-'.$a->id,
                'module' => 'Admissions',
                'title' => trim("{$a->first_name} {$a->last_name}"),
                'subtitle' => $a->application_status,
                'route' => "admissions/{$a->id}",
                'metadata' => ['entity_type' => 'admission', 'entity_id' => $a->id],
            ])
            ->values()
            ->all();
    }

    private function searchInvoices(string $like, int $page, int $limit): array
    {
        return Invoice::query()
            ->with('student')
            ->whereNull('reversed_at')
            ->whereHas('student', fn ($s) => $s->where('first_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhere('admission_number', 'like', $like))
            ->latest()
            ->paginate($limit, ['*'], 'page', $page)
            ->map(fn ($inv) => [
                'id' => 'invoice-'.$inv->id,
                'module' => 'Finance',
                'title' => 'Invoice #'.$inv->id,
                'subtitle' => $inv->student?->full_name ?? 'Balance: '.$inv->balance,
                'route' => "finance/invoices/{$inv->id}",
                'metadata' => ['entity_type' => 'invoice', 'entity_id' => $inv->id],
            ])
            ->values()
            ->all();
    }

    private function searchPayments(string $like, int $page, int $limit): array
    {
        return Payment::query()
            ->with(['student' => fn ($q) => $q->withoutGlobalScopes()])
            ->where('reversed', false)
            ->where(function ($q) use ($like) {
                $q->where('receipt_number', 'like', $like)
                    ->orWhere('transaction_code', 'like', $like)
                    ->orWhereHas('student', function ($s) use ($like) {
                        $s->withoutGlobalScopes()
                            ->where(function ($n) use ($like) {
                                $n->where('first_name', 'like', $like)
                                    ->orWhere('last_name', 'like', $like)
                                    ->orWhere('admission_number', 'like', $like);
                            });
                    });
            })
            ->latest('payment_date')
            ->paginate($limit, ['*'], 'page', $page)
            ->map(fn ($p) => [
                'id' => 'payment-'.$p->id,
                'module' => 'Finance',
                'title' => $p->receipt_number ?? 'Payment #'.$p->id,
                'subtitle' => $p->student?->full_name,
                'route' => "finance/payments/{$p->id}",
                'metadata' => ['entity_type' => 'payment', 'entity_id' => $p->id],
            ])
            ->values()
            ->all();
    }

    private function searchVisitors(string $like, int $page, int $limit): array
    {
        if (! Schema::hasTable('visitor_logs')) {
            return [];
        }

        return VisitorLog::query()
            ->where(function ($q) use ($like) {
                $q->where('visitor_name', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('purpose', 'like', $like);
            })
            ->latest('checked_in_at')
            ->paginate($limit, ['*'], 'page', $page)
            ->map(fn ($v) => [
                'id' => 'visitor-'.$v->id,
                'module' => 'Operations',
                'title' => $v->visitor_name,
                'subtitle' => $v->purpose,
                'route' => "operations/visitors/{$v->id}",
                'metadata' => ['entity_type' => 'visitor', 'entity_id' => $v->id],
            ])
            ->values()
            ->all();
    }

    private function searchAssets(string $like, int $page, int $limit): array
    {
        if (! Schema::hasTable('fixed_assets')) {
            return [];
        }

        return FixedAsset::query()
            ->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('asset_tag', 'like', $like)
                    ->orWhere('serial_number', 'like', $like);
            })
            ->orderBy('name')
            ->paginate($limit, ['*'], 'page', $page)
            ->map(fn ($a) => [
                'id' => 'asset-'.$a->id,
                'module' => 'Operations',
                'title' => $a->name,
                'subtitle' => $a->asset_tag,
                'route' => "operations/assets/{$a->id}",
                'metadata' => ['entity_type' => 'asset', 'entity_id' => $a->id],
            ])
            ->values()
            ->all();
    }

    private function searchRequisitions(string $like, int $page, int $limit): array
    {
        return Requisition::query()
            ->where(function ($q) use ($like) {
                $q->where('requisition_number', 'like', $like)
                    ->orWhere('purpose', 'like', $like)
                    ->orWhere('status', 'like', $like);
            })
            ->latest()
            ->paginate($limit, ['*'], 'page', $page)
            ->map(fn ($r) => [
                'id' => 'requisition-'.$r->id,
                'module' => 'Operations',
                'title' => $r->requisition_number ?? 'Requisition #'.$r->id,
                'subtitle' => $r->status,
                'route' => "operations/requisitions/{$r->id}",
                'metadata' => ['entity_type' => 'requisition', 'entity_id' => $r->id],
            ])
            ->values()
            ->all();
    }

    private function searchInventory(string $like, int $page, int $limit): array
    {
        return InventoryItem::query()
            ->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('category', 'like', $like)
                    ->orWhere('description', 'like', $like);
            })
            ->orderBy('name')
            ->paginate($limit, ['*'], 'page', $page)
            ->map(fn ($i) => [
                'id' => 'inventory-'.$i->id,
                'module' => 'Operations',
                'title' => $i->name,
                'subtitle' => 'Qty: '.$i->quantity,
                'route' => "operations/inventory/{$i->id}",
                'metadata' => ['entity_type' => 'inventory', 'entity_id' => $i->id],
            ])
            ->values()
            ->all();
    }

    private function searchAnnouncements(string $like, int $page, int $limit): array
    {
        return Announcement::query()
            ->where(function ($q) use ($like) {
                $q->where('title', 'like', $like)
                    ->orWhere('content', 'like', $like);
            })
            ->latest()
            ->paginate($limit, ['*'], 'page', $page)
            ->map(fn ($a) => [
                'id' => 'announcement-'.$a->id,
                'module' => 'Communication',
                'title' => $a->title,
                'subtitle' => $a->type ?? null,
                'route' => "communication/announcements/{$a->id}",
                'metadata' => ['entity_type' => 'announcement', 'entity_id' => $a->id],
            ])
            ->values()
            ->all();
    }

    private function searchVehicles(string $like, int $page, int $limit): array
    {
        if (! Schema::hasTable('vehicles')) {
            return [];
        }

        return Vehicle::query()
            ->where(function ($q) use ($like) {
                $q->where('vehicle_number', 'like', $like)
                    ->orWhere('driver_name', 'like', $like)
                    ->orWhere('make', 'like', $like)
                    ->orWhere('model', 'like', $like)
                    ->orWhere('chassis_number', 'like', $like);
            })
            ->orderBy('vehicle_number')
            ->paginate($limit, ['*'], 'page', $page)
            ->map(fn ($v) => [
                'id' => 'vehicle-'.$v->id,
                'module' => 'Operations',
                'title' => $v->vehicle_number ?? 'Vehicle #'.$v->id,
                'subtitle' => trim(($v->make ?? '').' '.($v->model ?? '')) ?: ($v->driver_name ?? null),
                'route' => "operations/transport/vehicles/{$v->id}",
                'metadata' => ['entity_type' => 'vehicle', 'entity_id' => $v->id],
            ])
            ->values()
            ->all();
    }
}
