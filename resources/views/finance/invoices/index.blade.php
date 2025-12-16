@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0">
                    <i class="bi bi-file-text"></i> Invoices
                </h3>
                <div>
                    <a href="{{ route('finance.invoices.print', request()->only(['year','term','votehead_id','class_id','stream_id','student_id'])) }}"
                       target="_blank"
                       class="btn btn-outline-secondary">
                       <i class="bi bi-printer"></i> Print Bulk PDF
                    </a>
                    <a href="{{ route('finance.invoices.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Create Invoice
                    </a>
                </div>
            </div>
        </div>
    </div>

    @includeIf('finance.invoices.partials.alerts')

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('finance.invoices.index') }}" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Year</label>
                    <input type="number" 
                           class="form-control" 
                           name="year" 
                           value="{{ request('year', now()->year) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Term</label>
                    <select name="term" class="form-select">
                        <option value="">All Terms</option>
                        @for($i=1;$i<=3;$i++)
                            <option value="{{ $i }}" {{ request('term') == $i ? 'selected':'' }}>Term {{ $i }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Votehead</label>
                    <select name="votehead_id" class="form-select">
                        <option value="">All Voteheads</option>
                        @foreach($voteheads ?? [] as $vh)
                            <option value="{{ $vh->id }}" {{ request('votehead_id')==$vh->id ? 'selected':'' }}>{{ $vh->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Class</label>
                    <select name="class_id" class="form-select">
                        <option value="">All Classes</option>
                        @foreach($classrooms ?? [] as $c)
                            <option value="{{ $c->id }}" {{ request('class_id')==$c->id ? 'selected':'' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Stream</label>
                    <select name="stream_id" class="form-select">
                        <option value="">All Streams</option>
                        @foreach($streams ?? [] as $s)
                            <option value="{{ $s->id }}" {{ request('stream_id')==$s->id ? 'selected':'' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="unpaid" {{ request('status') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                        <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Partial</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="{{ route('finance.invoices.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Invoices Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Invoice #</th>
                            <th>Student</th>
                            <th>Class/Stream</th>
                            <th>Year/Term</th>
                            <th class="text-end">Subtotal</th>
                            <th class="text-end">Discount</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end">Balance</th>
                            <th>Status</th>
                            <th>Due Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $inv)
                        @php
                            // Recalculate invoice to ensure totals are up to date
                            if (!$inv->relationLoaded('items') || $inv->items->isEmpty()) {
                                $inv->load('items');
                            }
                            
                            // Calculate subtotal (before discounts) - sum of all item amounts
                            $subtotal = $inv->items->sum('amount') ?? 0;
                            
                            // Calculate total discounts
                            $itemDiscounts = $inv->items->sum('discount_amount') ?? 0;
                            $invoiceDiscount = $inv->discount_amount ?? 0;
                            $totalDiscount = $itemDiscounts + $invoiceDiscount;
                            
                            // Total after discounts (should match $inv->total if calculated correctly)
                            $totalAfterDiscount = $subtotal - $totalDiscount;
                            
                            // Use the invoice's calculated balance (which should be total - paid)
                            // If balance seems wrong, recalculate
                            if (abs($inv->balance - ($totalAfterDiscount - ($inv->paid_amount ?? 0))) > 0.01) {
                                $inv->recalculate();
                                $inv->refresh();
                            }
                            
                            $balance = $inv->balance ?? ($totalAfterDiscount - ($inv->paid_amount ?? 0));
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $inv->invoice_number }}</strong>
                            </td>
                            <td>
                                {{ $inv->student->first_name ?? 'Unknown' }} {{ $inv->student->last_name ?? '' }}
                                <br><small class="text-muted">{{ $inv->student->admission_number ?? '—' }}</small>
                            </td>
                            <td>
                                {{ $inv->student->classroom->name ?? '—' }}
                                @if($inv->student->stream)
                                    / {{ $inv->student->stream->name }}
                                @endif
                            </td>
                            <td>
                                {{ $inv->academicYear->name ?? $inv->year ?? '—' }}
                                @if($inv->term)
                                    / {{ $inv->term->name ?? 'Term ' . $inv->term }}
                                @endif
                            </td>
                            <td class="text-end">
                                <span class="text-muted">Ksh {{ number_format($subtotal, 2) }}</span>
                            </td>
                            <td class="text-end">
                                @if($totalDiscount > 0)
                                    <span class="text-success">-Ksh {{ number_format($totalDiscount, 2) }}</span>
                                @else
                                    <span class="text-muted">Ksh 0.00</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <strong>Ksh {{ number_format($totalAfterDiscount, 2) }}</strong>
                            </td>
                            <td class="text-end">
                                <span class="text-success">Ksh {{ number_format($inv->paid_amount ?? 0, 2) }}</span>
                            </td>
                            <td class="text-end">
                                <span class="text-{{ $balance > 0 ? 'danger' : 'success' }}">
                                    Ksh {{ number_format($balance, 2) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $inv->status === 'paid' ? 'success' : ($inv->status === 'partial' ? 'warning' : 'danger') }}">
                                    {{ ucfirst($inv->status) }}
                                </span>
                                @if($inv->isOverdue())
                                    <span class="badge bg-danger ms-1">Overdue</span>
                                @endif
                            </td>
                            <td>
                                @if($inv->due_date)
                                    {{ \Carbon\Carbon::parse($inv->due_date)->format('d M Y') }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('finance.invoices.show', $inv) }}" 
                                       class="btn btn-outline-primary" 
                                       title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('finance.invoices.print_single', $inv) }}" 
                                       class="btn btn-outline-secondary" 
                                       target="_blank"
                                       title="Print">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="12" class="text-center py-4">
                                <p class="text-muted mb-0">No invoices found.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($invoices->isNotEmpty())
                    @php
                        $totalSubtotal = $invoices->sum(function($i) { return $i->items->sum('amount') ?? $i->total; });
                        $totalDiscounts = $invoices->sum(function($i) { 
                            return ($i->items->sum('discount_amount') ?? 0) + ($i->discount_amount ?? 0); 
                        });
                        $totalAfterDiscount = $totalSubtotal - $totalDiscounts;
                        $totalPaid = $invoices->sum('paid_amount');
                        $totalBalance = $invoices->sum(function($i) { 
                            return $i->balance ?? (($i->items->sum('amount') ?? $i->total) - (($i->items->sum('discount_amount') ?? 0) + ($i->discount_amount ?? 0)) - ($i->paid_amount ?? 0)); 
                        });
                    @endphp
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="4" class="text-end">Totals:</th>
                            <th class="text-end">Ksh {{ number_format($totalSubtotal, 2) }}</th>
                            <th class="text-end">
                                @if($totalDiscounts > 0)
                                    <span class="text-success">-Ksh {{ number_format($totalDiscounts, 2) }}</span>
                                @else
                                    <span class="text-muted">Ksh 0.00</span>
                                @endif
                            </th>
                            <th class="text-end">Ksh {{ number_format($totalAfterDiscount, 2) }}</th>
                            <th class="text-end">Ksh {{ number_format($totalPaid, 2) }}</th>
                            <th class="text-end">Ksh {{ number_format($totalBalance, 2) }}</th>
                            <th colspan="3"></th>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
        @if($invoices->hasPages())
        <div class="card-footer">
            {{ $invoices->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
