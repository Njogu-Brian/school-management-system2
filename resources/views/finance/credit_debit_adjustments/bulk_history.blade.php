@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h3 class="mb-0">Bulk Import History</h3>
            <p class="text-muted small mb-0">Uploads and the credit/debit changes they applied</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('finance.journals.bulk.form') }}" class="btn btn-primary">
                <i class="bi bi-upload"></i> New Bulk Import
            </a>
            <a href="{{ route('finance.journals.index') }}" class="btn btn-outline-secondary">Back to Adjustments</a>
        </div>
    </div>

    @includeIf('finance.invoices.partials.alerts')

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Batch</th>
                        <th>File</th>
                        <th>Uploaded</th>
                        <th>By</th>
                        <th>Status</th>
                        <th class="text-end">Rows</th>
                        <th class="text-end">Approved</th>
                        <th class="text-end">Credits</th>
                        <th class="text-end">Debits</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($imports as $import)
                        @php
                            $statusClass = match($import->status) {
                                'completed' => 'bg-success',
                                'partial' => 'bg-warning text-dark',
                                'preview' => 'bg-info text-dark',
                                'abandoned' => 'bg-secondary',
                                default => 'bg-secondary',
                            };
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $import->batch_code }}</td>
                            <td class="small">{{ $import->original_filename ?: '—' }}</td>
                            <td class="text-nowrap small">{{ optional($import->created_at)->format('d M Y H:i') }}</td>
                            <td class="small">{{ $import->uploader?->name ?? '—' }}</td>
                            <td><span class="badge {{ $statusClass }}">{{ strtoupper($import->status) }}</span></td>
                            <td class="text-end">{{ $import->total_rows }}</td>
                            <td class="text-end">{{ $import->approved_rows }}</td>
                            <td class="text-end text-success">{{ number_format((float) $import->credit_total, 2) }}</td>
                            <td class="text-end text-danger">{{ number_format((float) $import->debit_total, 2) }}</td>
                            <td class="text-end">
                                <a href="{{ route('finance.journals.bulk.history.show', $import) }}" class="btn btn-sm btn-outline-primary">
                                    View changes
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">No bulk imports yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($imports->hasPages())
            <div class="card-footer">{{ $imports->links() }}</div>
        @endif
    </div>
</div>
@endsection
