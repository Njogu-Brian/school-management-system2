<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;

class ApiInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);

        $query = Invoice::with(['student.classroom', 'student.stream', 'items', 'term', 'academicYear'])
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
