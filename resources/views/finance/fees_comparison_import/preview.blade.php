@extends('layouts.app')

@push('styles')
    @include('finance.partials.styles')
    <style>
        .fees-comparison-page { background: var(--fin-bg); min-height: 100vh; padding: 20px 0; }
        .comparison-stat-card {
            background: var(--fin-surface);
            border: 1px solid var(--fin-border);
            border-radius: 14px;
            padding: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.25s ease;
        }
        .comparison-stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 12px -1px rgba(0, 0, 0, 0.1); }
        .comparison-stat-value { font-size: 1.5rem; font-weight: 700; }
        .comparison-stat-label { font-size: 0.85rem; color: var(--fin-muted); font-weight: 600; }
        .comparison-table-wrapper {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 14px;
            border: 1px solid var(--fin-border);
        }
        .comparison-table {
            width: max-content;
            min-width: 100%;
            border-collapse: collapse;
            color: var(--fin-text);
            table-layout: auto;
        }
        .comparison-table thead th {
            background: color-mix(in srgb, var(--fin-primary) 8%, #fff 92%);
            border-bottom: 1px solid var(--fin-border);
            font-weight: 700;
            padding: 12px 14px;
            white-space: nowrap;
        }
        .comparison-table td, .comparison-table th { padding: 10px 14px; border-bottom: 1px solid var(--fin-border); vertical-align: middle; }
        .comparison-table tbody tr:hover { background: color-mix(in srgb, var(--fin-primary) 4%, #fff 96%); }
        .row-missing { background: rgba(220, 53, 69, 0.08) !important; }
        .row-family-mismatch { background: rgba(253, 126, 20, 0.08) !important; }
        .row-amount-diff { background: rgba(255, 193, 7, 0.12) !important; }
        .row-in-system-only { background: rgba(13, 202, 240, 0.08) !important; }
        .row-ok { }
        .family-note-cell { font-size: 0.875rem; color: var(--fin-muted); max-width: 280px; }
    </style>
@endpush

@section('content')
<div class="finance-page fees-comparison-page">
    <div class="finance-shell">
        @include('finance.partials.header', [
            'title' => 'Fees Comparison Preview',
            'icon' => 'bi bi-clipboard2-check',
            'subtitle' => "Compare import vs system for {$year} Term {$term}. Saved preview — open a student's fee statement and use Back to comparison to return here.",
            'actions' => '<a href="' . route('finance.fees-comparison-import.index') . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-arrow-left"></i> Back to Import</a>'
        ])

        @if(empty($preview))
            <div class="alert alert-danger alert-dismissible fade show finance-animate" role="alert">
                <strong><i class="bi bi-exclamation-triangle"></i> No data to preview.</strong>
                The file may be empty or column names don’t match. Use the template and try again.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @else
            @if($hasIssues)
                <div class="alert alert-warning alert-dismissible fade show finance-animate" role="alert">
                    <strong><i class="bi bi-exclamation-triangle"></i> Issues detected.</strong>
                    Review the table below. Missing students, amount differences, and family total mismatches are highlighted.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @else
                <div class="alert alert-success alert-dismissible fade show finance-animate" role="alert">
                    <strong><i class="bi bi-check-circle"></i> All comparisons match.</strong>
                    No missing students, amount differences, or family total mismatches.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- Summary --}}
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="comparison-stat-card">
                        <div class="comparison-stat-value">{{ $summary['total'] }}</div>
                        <div class="comparison-stat-label">Total rows</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="comparison-stat-card border-success">
                        <div class="comparison-stat-value text-success">{{ $summary['ok'] }}</div>
                        <div class="comparison-stat-label">Match</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="comparison-stat-card border-danger">
                        <div class="comparison-stat-value text-danger">{{ $summary['missing_student'] }}</div>
                        <div class="comparison-stat-label">Missing</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="comparison-stat-card border-warning">
                        <div class="comparison-stat-value text-warning">{{ $summary['amount_differs'] }}</div>
                        <div class="comparison-stat-label">Amount diff</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="comparison-stat-card border-warning">
                        <div class="comparison-stat-value text-warning">{{ $summary['family_total_mismatch'] }}</div>
                        <div class="comparison-stat-label">Family mismatch</div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="comparison-stat-card border-info">
                        <div class="comparison-stat-value text-info">{{ $summary['in_system_only'] }}</div>
                        <div class="comparison-stat-label">System only</div>
                    </div>
                </div>
            </div>
            @if(($summary['allocation_diff_families'] ?? 0) > 0)
                <div class="alert alert-info border-0 mb-4">
                    <i class="bi bi-people me-2"></i>
                    <strong>{{ $summary['allocation_diff_families'] }}</strong> families have <strong>matching family totals</strong> but <strong>individual allocations differ</strong> between system and import.
                </div>
            @endif

            {{-- Comparison table (grouped by family, individual invoices/payments, then family total) --}}
            @php $previewGrouped = $previewGrouped ?? []; @endphp
            <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
                <div class="finance-card-header d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-table"></i>
                        <span>Comparison Results</span>
                    </div>
                    <div class="d-flex gap-2">
                        <span class="badge bg-success">{{ $summary['ok'] }} OK</span>
                        @if($summary['missing_student'] + $summary['amount_differs'] + $summary['family_total_mismatch'] > 0)
                            <span class="badge bg-warning text-dark">{{ $summary['missing_student'] + $summary['amount_differs'] + $summary['family_total_mismatch'] }} issues</span>
                        @endif
                    </div>
                </div>
                <div class="finance-card-body p-0">
                    <div class="comparison-table-wrapper">
                        <table class="comparison-table align-middle">
                            <thead>
                                <tr>
                                    <th>Admission #</th>
                                    <th>Student</th>
                                    <th>Class</th>
                                    <th>Phone</th>
                                    <th class="text-end">Invoice bal.</th>
                                    <th class="text-end">Swimming bal.</th>
                                    <th class="text-end">System invoiced (incl. BBF)</th>
                                    <th class="text-end">System paid</th>
                                    <th class="text-end">Import paid</th>
                                    <th class="text-end">Difference</th>
                                    <th>Status</th>
                                    <th>Family / Sibling note</th>
                                    @if(!empty($previewId))
                                    <th class="text-center">Fee statement</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($previewGrouped as $groupKey => $group)
                                    @php
                                        $rows = $group['rows'] ?? [];
                                        $isFamily = ($group['family_id'] ?? null) && count($rows) > 1;
                                    @endphp
                                    @if($isFamily)
                                        <tr class="family-header-row" style="background: color-mix(in srgb, var(--fin-primary) 6%, #fff 94%);">
                                            <td colspan="{{ !empty($previewId) ? 13 : 12 }}" class="fw-bold py-2">
                                                <i class="bi bi-people me-1"></i> Family — {{ count($rows) }} children
                                            </td>
                                        </tr>
                                    @endif
                                    @foreach($rows as $row)
                                        @php
                                            $status = $row['status'] ?? 'ok';
                                            $rowClass = match($status) {
                                                'missing_student' => 'row-missing',
                                                'family_total_mismatch' => 'row-family-mismatch',
                                                'amount_differs' => 'row-amount-diff',
                                                'in_system_only' => 'row-in-system-only',
                                                default => 'row-ok',
                                            };
                                        @endphp
                                        <tr class="{{ $rowClass }}">
                                            <td><strong>{{ $row['admission_number'] }}</strong></td>
                                            <td>{{ $row['student_name'] }}</td>
                                            <td>{{ $row['classroom'] ?? '—' }}</td>
                                            <td class="text-nowrap">{{ $row['parent_phone'] ?? '—' }}</td>
                                            <td class="text-end">
                                                @if(isset($row['system_invoice_balance']) && $row['system_invoice_balance'] !== null)
                                                    KES {{ number_format($row['system_invoice_balance'], 2) }}
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if(isset($row['system_swimming_balance']) && $row['system_swimming_balance'] !== null)
                                                    KES {{ number_format($row['system_swimming_balance'], 2) }}
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if(isset($row['system_total_invoiced']) && $row['system_total_invoiced'] !== null)
                                                    KES {{ number_format($row['system_total_invoiced'], 2) }}
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if(isset($row['system_total_paid']) && $row['system_total_paid'] !== null)
                                                    <strong>KES {{ number_format($row['system_total_paid'], 2) }}</strong>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if(isset($row['import_total_paid']))
                                                    <strong>KES {{ number_format($row['import_total_paid'], 2) }}</strong>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if(isset($row['difference']) && $row['difference'] !== null)
                                                    <span class="{{ $row['difference'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                        {{ $row['difference'] >= 0 ? '+' : '' }}KES {{ number_format($row['difference'], 2) }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($status === 'ok')
                                                    <span class="badge bg-success">Match</span>
                                                @elseif($status === 'missing_student')
                                                    <span class="badge bg-danger">Missing</span>
                                                @elseif($status === 'amount_differs')
                                                    <span class="badge bg-warning text-dark">Amount differs</span>
                                                @elseif($status === 'family_total_mismatch')
                                                    <span class="badge bg-warning text-dark">Family mismatch</span>
                                                @elseif($status === 'in_system_only')
                                                    <span class="badge bg-info">System only</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ $status }}</span>
                                                @endif
                                                @if(!empty($row['message']))
                                                    <br><small class="text-muted">{{ $row['message'] }}</small>
                                                @endif
                                            </td>
                                            <td class="family-note-cell">
                                                @if(!empty($row['family_note']))
                                                    <span class="text-info">{{ $row['family_note'] }}</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            @if(!empty($previewId))
                                            <td class="text-center">
                                                @if(!empty($row['student_id']))
                                                    <a href="{{ route('finance.student-statements.show', ['student' => $row['student_id'], 'year' => $year, 'term' => $term, 'comparison_preview_id' => $previewId]) }}" class="btn btn-sm btn-finance btn-finance-outline" title="Open fee statement and return to this comparison">
                                                        <i class="bi bi-file-text"></i> Statement
                                                    </a>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                    @if($isFamily)
                                        <tr class="family-total-row fw-bold" style="background: color-mix(in srgb, var(--fin-primary) 4%, #fff 96%);">
                                            <td colspan="3" class="text-end">Family total (payments)</td>
                                            <td><span class="text-muted">—</span></td>
                                            <td class="text-end"><span class="text-muted">—</span></td>
                                            <td class="text-end"><span class="text-muted">—</span></td>
                                            <td class="text-end"><span class="text-muted">—</span></td>
                                            <td class="text-end">KES {{ number_format($group['system_paid_total'] ?? 0, 2) }}</td>
                                            <td class="text-end">KES {{ number_format($group['import_paid_total'] ?? 0, 2) }}</td>
                                            <td colspan="{{ !empty($previewId) ? 4 : 3 }}"></td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                            @if(count($preview) > 0)
                            <tfoot>
                                <tr class="fw-bold">
                                    <td colspan="6" class="text-end">Totals</td>
                                    <td class="text-end">KES {{ number_format(collect($preview)->sum(fn($r) => (float)($r['system_total_invoiced'] ?? 0)), 2) }}</td>
                                    <td class="text-end">KES {{ number_format(collect($preview)->sum(fn($r) => (float)($r['system_total_paid'] ?? 0)), 2) }}</td>
                                    <td class="text-end">KES {{ number_format(collect($preview)->sum(fn($r) => (float)($r['import_total_paid'] ?? 0)), 2) }}</td>
                                    <td colspan="{{ !empty($previewId) ? 4 : 3 }}"></td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
                <div class="finance-card-body border-top d-flex justify-content-between align-items-center">
                    <p class="text-muted small mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        System totals use total fees invoice (including balance brought forward) and total paid for {{ $year }} Term {{ $term }}. Archived and alumni students are excluded. No changes are made from this view.
                    </p>
                    <a href="{{ route('finance.fees-comparison-import.index') }}" class="btn btn-finance btn-finance-outline">
                        <i class="bi bi-arrow-left"></i> Back to Import
                    </a>
                </div>
            </div>

            {{-- Legend --}}
            <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mt-4">
                <div class="finance-card-header">
                    <i class="bi bi-palette"></i>
                    <span>Legend</span>
                </div>
                <div class="finance-card-body p-4">
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2"><span class="badge bg-success">Match</span> — System and import amounts agree.</li>
                                <li class="mb-2"><span class="badge bg-danger">Missing</span> — In import but student not found in system.</li>
                                <li class="mb-2"><span class="badge bg-warning text-dark">Amount differs</span> — System paid ≠ import total fees paid.</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2"><span class="badge bg-warning text-dark">Family mismatch</span> — Sibling family total in system ≠ family total in import.</li>
                                <li class="mb-2"><span class="badge bg-info">System only</span> — In system but not in import file.</li>
                                <li class="mb-0"><strong>Family total matches; individual allocations differ</strong> — Sibling family totals agree, but at least one child’s allocation differs.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
