@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'Legacy Import Batch #' . $batch->id,
        'icon' => 'bi bi-archive',
        'subtitle' => 'Review parsed terms and lines for this legacy PDF',
        'actions' => ''
    ])

    @if (session('success'))
        <div class="alert alert-success finance-animate">{{ session('success') }}</div>
    @endif

    <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mb-4">
        <div class="row gy-3">
            <div class="col-md-3">
                <div class="text-muted small">File</div>
                <div class="fw-semibold">{{ $batch->file_name }}</div>
            </div>
            <div class="col-md-2">
                <div class="text-muted small">Class</div>
                <div class="fw-semibold">{{ $batch->class_label ?? '—' }}</div>
            </div>
            <div class="col-md-2">
                <div class="text-muted small">Status</div>
                <span class="badge {{ $batch->status === 'completed' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                    {{ ucfirst($batch->status) }}
                </span>
            </div>
            <div class="col-md-2">
                <div class="text-muted small">Students</div>
                <div class="fw-semibold">{{ $batch->total_students }}</div>
            </div>
            <div class="col-md-2">
                <div class="text-muted small">Drafts</div>
                @if($batch->draft_students > 0)
                    <span class="badge bg-warning text-dark">{{ $batch->draft_students }}</span>
                @else
                    <span class="text-muted">{{ $batch->draft_students }}</span>
                @endif
            </div>
        </div>
    </div>

    <div class="finance-table-wrapper finance-animate shadow-sm rounded-4 border-0 mb-4">
        <div class="d-flex align-items-center justify-content-between px-3 pt-3">
            <h6 class="mb-0">Terms in this batch</h6>
        </div>
        <div class="table-responsive px-3 pb-3">
            <table class="finance-table align-middle">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Admission</th>
                        <th>Year</th>
                        <th>Term</th>
                        <th>Class</th>
                        <th>Status</th>
                        <th>Starting Bal</th>
                        <th>Ending Bal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batch->terms as $term)
                        <tr>
                            <td>{{ $term->student_name }}</td>
                            <td>{{ $term->admission_number }}</td>
                            <td>{{ $term->academic_year }}</td>
                            <td>{{ $term->term_name }}</td>
                            <td>{{ $term->class_label }}</td>
                            <td>
                                <span class="badge {{ $term->status === 'imported' ? 'bg-success-subtle text-success' : 'bg-warning text-dark' }}">
                                    {{ ucfirst($term->status) }}
                                </span>
                            </td>
                            <td>{{ $term->starting_balance ? number_format($term->starting_balance, 2) : '—' }}</td>
                            <td>{{ $term->ending_balance ? number_format($term->ending_balance, 2) : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="finance-empty-state">
                                    <div class="finance-empty-state-icon">📄</div>
                                    <div class="text-muted">No terms found.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="finance-table-wrapper finance-animate shadow-sm rounded-4 border-0">
        <div class="d-flex align-items-center justify-content-between px-3 pt-3">
            <h6 class="mb-0">Lines <span class="text-muted">(ordered by term then sequence)</span></h6>
        </div>
        <div class="table-responsive px-3 pb-3">
            <table class="finance-table align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Term</th>
                        <th>Date</th>
                        <th>Narration</th>
                        <th class="text-end">Dr</th>
                        <th class="text-end">Cr</th>
                        <th class="text-end">Run Bal</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lines as $line)
                        <tr class="{{ $line->confidence === 'draft' ? 'table-warning' : '' }}">
                            <td>{{ $line->sequence_no }}</td>
                            <td>{{ $line->term->student_name ?? '—' }}</td>
                            <td>{{ $line->term->term_name ?? '—' }}</td>
                            <td>{{ $line->txn_date ? $line->txn_date->format('d-M-Y') : '—' }}</td>
                            <td>{{ $line->narration_raw }}</td>
                            <td class="text-end">{{ $line->amount_dr !== null ? number_format($line->amount_dr, 2) : '—' }}</td>
                            <td class="text-end">{{ $line->amount_cr !== null ? number_format($line->amount_cr, 2) : '—' }}</td>
                            <td class="text-end">{{ $line->running_balance !== null ? number_format($line->running_balance, 2) : '—' }}</td>
                            <td>
                                <span class="badge {{ $line->confidence === 'draft' ? 'bg-warning text-dark' : 'bg-success-subtle text-success' }}">
                                    {{ ucfirst($line->confidence) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="finance-empty-state">
                                    <div class="finance-empty-state-icon">📄</div>
                                    <div class="text-muted">No lines found.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($lines->hasPages())
            <div class="px-3 pb-3">
                {{ $lines->links() }}
            </div>
        @endif
    </div>
@endsection

