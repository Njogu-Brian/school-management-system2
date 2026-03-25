<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Student;
use Illuminate\Http\Request;

class ApiInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);

        $query = Invoice::with(['student.classroom', 'student.stream', 'items', 'term', 'academicYear'])
            ->when(! $request->boolean('include_reversed'), function ($q) {
                $q->whereNull('reversed_at')
                    ->where(function ($qq) {
                        $qq->whereNull('status')->orWhere('status', '!=', 'reversed');
                    });
            })
            ->when($request->filled('year') || $request->filled('year_id'), fn($q) => $q->where('academic_year_id', $request->year ?? $request->year_id))
            ->when($request->filled('term') || $request->filled('term_id'), fn($q) => $q->where('term_id', $request->term ?? $request->term_id))
            ->when($request->filled('student_id'), fn($q) => $q->where('student_id', (int) $request->student_id))
            ->when($request->filled('class_id'), fn($q) => $q->whereHas('student', fn($s) => $s->where('classroom_id', $request->class_id)->where('archive', 0)->where('is_alumni', false)))
            ->when($request->filled('stream_id'), fn($q) => $q->whereHas('student', fn($s) => $s->where('stream_id', $request->stream_id)->where('archive', 0)->where('is_alumni', false)))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = '%' . addcslashes($request->search, '%_\\') . '%';
                $q->whereHas('student', fn($s) => $s->where('first_name', 'like', $search)
                    ->orWhere('last_name', 'like', $search)
                    ->orWhere('admission_number', 'like', $search));
            })
            ->latest();

        $paginated = $query->paginate($perPage);

        $data = $paginated->getCollection()->map(fn($inv) => $this->formatInvoice($inv))->values();

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

    public function show(Request $request, int $id)
    {
        $invoice = Invoice::with(['student.classroom', 'student.stream', 'items.votehead', 'term', 'academicYear'])
            ->findOrFail($id);

        $this->assertFinanceOrViewStudent($request->user(), (int) $invoice->student_id);

        return response()->json([
            'success' => true,
            'data' => $this->formatInvoiceDetail($invoice),
        ]);
    }

    protected function assertFinanceOrViewStudent($user, int $studentId): void
    {
        if (! $user) {
            abort(401);
        }
        if ($user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Finance Officer', 'Accountant'])) {
            return;
        }
        Student::findOrFail($studentId);
        if ($user->hasAnyRole(['Teacher', 'Senior Teacher', 'Supervisor'])) {
            $query = Student::where('id', $studentId)->where('archive', 0)->where('is_alumni', false);
            $user->applyTeacherStudentFilter($query);
            if (! $query->exists()) {
                abort(403, 'You do not have access to this invoice.');
            }

            return;
        }
        abort(403, 'You do not have access to this invoice.');
    }

    protected function formatInvoiceDetail(Invoice $inv): array
    {
        $base = $this->formatInvoice($inv);

        return array_merge($base, [
            'issue_date' => ($inv->issued_date ?? $inv->created_at)?->format('Y-m-d'),
            'items' => $this->formatInvoiceLineItems($inv),
            'term_name' => $inv->term->name ?? null,
            'academic_year_name' => $inv->academicYear->name ?? null,
            'notes' => $inv->notes,
        ]);
    }

    protected function formatInvoiceLineItems(Invoice $inv): array
    {
        return $inv->items
            ->filter(fn ($item) => ($item->status ?? 'active') === 'active')
            ->map(function ($item) {
                $net = (float) $item->amount - (float) ($item->discount_amount ?? 0);

                return [
                    'id' => $item->id,
                    'invoice_id' => $item->invoice_id,
                    'votehead_id' => $item->votehead_id,
                    'votehead_name' => $item->votehead->name ?? 'Item',
                    'amount' => (float) $item->amount,
                    'quantity' => 1,
                    'total' => round($net, 2),
                ];
            })
            ->values()
            ->all();
    }

    protected function formatInvoice(\App\Models\Invoice $inv): array
    {
        $student = $inv->student;
        $total = (float) ($inv->total ?? 0);
        $paid = (float) ($inv->paid_amount ?? 0);
        $balance = $total - $paid;

        return [
            'id' => $inv->id,
            'invoice_number' => $inv->invoice_number ?? (string) $inv->id,
            'student_id' => $inv->student_id,
            'student_name' => $student ? trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? '')) : null,
            'student_admission_number' => $student->admission_number ?? null,
            'term_id' => $inv->term_id,
            'academic_year_id' => $inv->academic_year_id,
            'total_amount' => $total,
            'paid_amount' => $paid,
            'balance' => $balance,
            'status' => $this->invoiceStatus($inv, $balance),
            'due_date' => $inv->due_date?->format('Y-m-d'),
            'issue_date' => $inv->created_at->format('Y-m-d'),
            'created_at' => $inv->created_at->toIso8601String(),
            'updated_at' => $inv->updated_at->toIso8601String(),
        ];
    }

    protected function invoiceStatus(\App\Models\Invoice $inv, float $balance): string
    {
        if (($inv->status ?? '') === 'reversed') return 'reversed';
        if ($balance <= 0) return 'paid';
        if ($inv->paid_amount > 0) return 'partially_paid';
        return 'issued';
    }
}
