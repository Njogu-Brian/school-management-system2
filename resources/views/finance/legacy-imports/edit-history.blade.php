@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'Edit History - Batch #' . $batch->id,
        'icon' => 'bi bi-clock-history',
        'subtitle' => 'Track all edits made to legacy import transactions',
        'actions' => ''
    ])

    <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mb-4 p-4">
        <div class="row gy-3 align-items-center">
            <div class="col-md-4">
                <div class="text-muted small">File</div>
                <div class="fw-semibold text-truncate" style="max-width: 100%;">{{ $batch->file_name }}</div>
            </div>
            <div class="col-md-2">
                <div class="text-muted small">Class</div>
                <div class="fw-semibold">{{ $batch->class_label ?? '—' }}</div>
            </div>
            <div class="col-md-2">
                <div class="text-muted small">Total Edits</div>
                <div class="fw-semibold">{{ $editHistory->total() }}</div>
            </div>
            <div class="col-md-4 text-end">
                <a href="{{ route('finance.legacy-imports.show', $batch) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Import
                </a>
            </div>
        </div>
    </div>

    @if($editHistory->isEmpty())
        <div class="finance-empty-state">
            <div class="finance-empty-state-icon">📝</div>
            <div class="text-muted">No edits have been made to this import batch yet.</div>
        </div>
    @else
        <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mb-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date & Time</th>
                                <th>Edited By</th>
                                <th>Transaction</th>
                                <th>Student/Term</th>
                                <th>Changed Fields</th>
                                <th>Before</th>
                                <th>After</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($editHistory as $edit)
                                @php
                                    $line = $edit->line;
                                    $term = $line->term ?? null;
                                    $changedFields = $edit->changed_fields ?? [];
                                @endphp
                                <tr>
                                    <td>
                                        <div class="small text-muted">{{ $edit->created_at->format('M d, Y') }}</div>
                                        <div class="small">{{ $edit->created_at->format('H:i:s') }}</div>
                                    </td>
                                    <td>
                                        {{ $edit->editedBy->name ?? 'System' }}
                                    </td>
                                    <td>
                                        <div class="small">
                                            <strong>Seq:</strong> {{ $line->sequence_no }}<br>
                                            <strong>Date:</strong> {{ $line->txn_date?->toDateString() ?? 'N/A' }}
                                        </div>
                                    </td>
                                    <td>
                                        @if($term)
                                            <div class="small">
                                                <strong>{{ $term->student_name ?? 'N/A' }}</strong><br>
                                                {{ $term->academic_year }} - {{ $term->term_name }}
                                            </div>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @foreach($changedFields as $field)
                                            <span class="badge bg-info text-dark me-1 mb-1">{{ ucfirst(str_replace('_', ' ', $field)) }}</span>
                                        @endforeach
                                    </td>
                                    <td>
                                        <div class="small">
                                            @foreach($changedFields as $field)
                                                @php
                                                    $beforeValue = $edit->before_values[$field] ?? null;
                                                    $displayValue = $beforeValue;
                                                    if ($field === 'txn_date' && $beforeValue) {
                                                        try {
                                                            $displayValue = \Carbon\Carbon::parse($beforeValue)->format('Y-m-d');
                                                        } catch (\Exception $e) {
                                                            $displayValue = $beforeValue;
                                                        }
                                                    } elseif (in_array($field, ['amount_dr', 'amount_cr', 'running_balance']) && $beforeValue !== null) {
                                                        $displayValue = number_format((float)$beforeValue, 2);
                                                    } elseif ($field === 'narration_raw') {
                                                        $displayValue = \Illuminate\Support\Str::limit($beforeValue, 50);
                                                    }
                                                @endphp
                                                <div class="mb-2">
                                                    <strong class="text-muted">{{ ucfirst(str_replace('_', ' ', $field)) }}:</strong><br>
                                                    <span class="text-danger fw-semibold">{{ $displayValue ?? 'N/A' }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small">
                                            @foreach($changedFields as $field)
                                                @php
                                                    $afterValue = $edit->after_values[$field] ?? null;
                                                    $displayValue = $afterValue;
                                                    if ($field === 'txn_date' && $afterValue) {
                                                        try {
                                                            $displayValue = \Carbon\Carbon::parse($afterValue)->format('Y-m-d');
                                                        } catch (\Exception $e) {
                                                            $displayValue = $afterValue;
                                                        }
                                                    } elseif (in_array($field, ['amount_dr', 'amount_cr', 'running_balance'])) {
                                                        if ($afterValue !== null && $afterValue !== '') {
                                                            $displayValue = number_format((float)$afterValue, 2);
                                                        } else {
                                                            $displayValue = '0.00';
                                                        }
                                                    } elseif ($field === 'narration_raw') {
                                                        $displayValue = $afterValue ? \Illuminate\Support\Str::limit($afterValue, 50) : 'N/A';
                                                    }
                                                @endphp
                                                <div class="mb-2">
                                                    <strong class="text-muted">{{ ucfirst(str_replace('_', ' ', $field)) }}:</strong><br>
                                                    <span class="text-success fw-semibold">{{ $displayValue ?? 'N/A' }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td>
                                        <form action="{{ route('finance.legacy-imports.edit-history.revert', $edit) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to revert this edit? This will restore the original values and create a new edit history entry.');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-warning" title="Revert this edit">
                                                <i class="bi bi-arrow-counterclockwise"></i> Revert
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white">
                {{ $editHistory->links() }}
            </div>
        </div>
    @endif
@endsection

@push('styles')
<style>
    .finance-empty-state {
        text-align: center;
        padding: 3rem 1rem;
    }
    .finance-empty-state-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
    }
</style>
@endpush

