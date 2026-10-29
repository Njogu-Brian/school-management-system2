@extends('layouts.app')

@section('content')
@php
    $money = fn ($v) => $v === null ? '—' : number_format((float) $v, 2);
    $statusClass = match($import->status) {
        'completed' => 'bg-success',
        'partial' => 'bg-warning text-dark',
        'preview' => 'bg-info text-dark',
        'abandoned' => 'bg-secondary',
        default => 'bg-secondary',
    };
@endphp
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h3 class="mb-1">{{ $import->batch_code }}</h3>
            <p class="text-muted small mb-0">
                {{ $import->original_filename ?: 'Untitled upload' }}
                · {{ optional($import->created_at)->format('d M Y H:i') }}
                @if($import->uploader)
                    · {{ $import->uploader->name }}
                @endif
            </p>
            @if($import->notes)
                <p class="small text-muted mt-1 mb-0">{{ $import->notes }}</p>
            @endif
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('finance.journals.bulk.history') }}" class="btn btn-outline-secondary">All uploads</a>
            <a href="{{ route('finance.journals.bulk.form') }}" class="btn btn-primary">New import</a>
        </div>
    </div>

    @includeIf('finance.invoices.partials.alerts')

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Status</div>
                    <div><span class="badge {{ $statusClass }}">{{ strtoupper($import->status) }}</span></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Rows</div>
                    <div class="fw-semibold">
                        {{ $import->approved_rows }} approved
                        / {{ $import->total_rows }} total
                    </div>
                    <div class="small text-muted">
                        Pending {{ $import->pending_rows }} · Failed {{ $import->failed_rows }} · Invalid {{ $import->invalid_rows }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Credits applied</div>
                    <div class="fw-semibold text-success">{{ number_format((float) $import->credit_total, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Debits applied</div>
                    <div class="fw-semibold text-danger">{{ number_format((float) $import->debit_total, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header fw-bold">Changes brought into effect</div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Line</th>
                        <th>Student</th>
                        <th>Votehead</th>
                        <th>Period</th>
                        <th>Type</th>
                        <th class="text-end">Before</th>
                        <th class="text-end">Adjustment</th>
                        <th class="text-end">New Total</th>
                        <th>Journal</th>
                        <th>Status</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($import->rows as $r)
                        @php
                            $adj = $r->adjustmentSigned();
                            $rowStatus = match($r->status) {
                                'pending' => 'bg-warning text-dark',
                                'approved' => 'bg-success',
                                'failed', 'invalid' => 'bg-danger',
                                default => 'bg-secondary',
                            };
                        @endphp
                        <tr>
                            <td>{{ $r->line }}</td>
                            <td>
                                <div class="fw-semibold">{{ $r->admission_number ?: '—' }}</div>
                                <div class="small text-muted">{{ $r->student_name ?: '—' }}</div>
                            </td>
                            <td>
                                <div>{{ $r->votehead_name ?: '—' }}</div>
                                @if($r->reason)
                                    <div class="small text-muted text-truncate" style="max-width:200px" title="{{ $r->reason }}">{{ $r->reason }}</div>
                                @endif
                            </td>
                            <td class="text-nowrap">{{ $r->year }}/T{{ $r->term }}</td>
                            <td>
                                <span class="badge {{ $r->type === 'credit' ? 'bg-info text-dark' : ($r->type === 'debit' ? 'bg-primary' : 'bg-secondary') }}">
                                    {{ $r->type_label ?: '—' }}
                                </span>
                            </td>
                            <td class="text-end text-nowrap">{{ $money($r->before_amount) }}</td>
                            <td class="text-end text-nowrap {{ $adj < 0 ? 'text-success' : ($adj > 0 ? 'text-danger' : '') }}">
                                @if($r->before_amount === null && $r->status === 'invalid')
                                    —
                                @else
                                    {{ $adj > 0 ? '+' : '' }}{{ number_format($adj, 2) }}
                                @endif
                            </td>
                            <td class="text-end text-nowrap fw-semibold">{{ $money($r->new_amount) }}</td>
                            <td class="small text-nowrap">
                                @if($r->journal)
                                    {{ $r->journal->journal_number }}
                                @else
                                    —
                                @endif
                            </td>
                            <td><span class="badge {{ $rowStatus }}">{{ strtoupper($r->status) }}</span></td>
                            <td class="small">{{ $r->message }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
