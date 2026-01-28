@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'Posting Preview - Fee Changes',
        'icon' => 'bi bi-eye',
        'subtitle' => 'Review fee changes before committing',
        'actions' => '<a href="' . route('finance.posting.index') . '" class="btn btn-finance btn-finance-secondary"><i class="bi bi-arrow-left"></i> Back</a>'
    ])

    @include('finance.invoices.partials.alerts')
    
    <div class="finance-card finance-animate mb-3 shadow-sm rounded-4 border-0">
        <div class="finance-card-body p-4">
            @if(isset($summary))
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="finance-card shadow-sm rounded-4 border-0">
                        <div class="finance-card-body text-center p-3">
                            <h5 class="text-primary">{{ $summary['total'] }}</h5>
                            <small class="finance-muted">Total Changes</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="finance-card shadow-sm rounded-4 border-0">
                        <div class="finance-card-body text-center p-3">
                            <h5 class="text-success">{{ $summary['added'] ?? 0 }}</h5>
                            <small class="finance-muted">Added</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="finance-card shadow-sm rounded-4 border-0">
                        <div class="finance-card-body text-center p-3">
                            <h5 class="text-warning">{{ $summary['increased'] ?? 0 }}</h5>
                            <small class="finance-muted">Increased</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="finance-card shadow-sm rounded-4 border-0">
                        <div class="finance-card-body text-center p-3">
                            <h5 class="text-danger">{{ $summary['decreased'] ?? 0 }}</h5>
                            <small class="finance-muted">Decreased</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="finance-card shadow-sm rounded-4 border-0">
                        <div class="finance-card-body text-center p-3">
                            <h5 class="text-info">
                                Ksh {{ number_format($summary['total_amount_change'] ?? 0, 2) }}
                            </h5>
                            <small class="finance-muted">Net Amount Change</small>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    @if(isset($allDiffs) && $allDiffs->isNotEmpty())
    <form method="POST" action="{{ route('finance.posting.commit') }}">
        @csrf
        <input type="hidden" name="year" value="{{ $filters['year'] ?? request('year') }}">
        <input type="hidden" name="term" value="{{ $filters['term'] ?? request('term') }}">
        <input type="hidden" name="activate_now" value="1">
        <input type="hidden" name="votehead_id" value="{{ $filters['votehead_id'] ?? '' }}">
        <input type="hidden" name="class_id" value="{{ $filters['class_id'] ?? '' }}">
        <input type="hidden" name="stream_id" value="{{ $filters['stream_id'] ?? '' }}">
        <input type="hidden" name="student_id" value="{{ $filters['student_id'] ?? '' }}">
        <input type="hidden" name="student_category_id" value="{{ $filters['student_category_id'] ?? '' }}">
        <input type="hidden" name="effective_date" value="{{ $filters['effective_date'] ?? '' }}">
        
        {{-- Use JSON encoding to avoid PHP max_input_vars limit (default 1000) --}}
        {{-- With 930 transactions * 6 fields = 5,580 inputs, which exceeds the limit --}}
        <input type="hidden" name="diffs_json" value="{{ base64_encode(json_encode($allDiffs->values()->all())) }}">

        <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
            <div class="finance-card-header d-flex justify-content-between align-items-center flex-wrap">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Change Details</h5>
                        <small class="finance-muted">{{ $allDiffs->count() }} total changes across {{ $groupedDiffs->total() }} students</small>
                    </div>
                    <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
                        <label class="mb-0 small finance-muted">Per Page:</label>
                        <select class="form-select form-select-sm" style="width: auto;" onchange="changePerPage(this.value)">
                            <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                            <option value="200" {{ $perPage == 200 ? 'selected' : '' }}>200</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="finance-card-body p-0">
                <div class="table-responsive">
                    <table class="finance-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 25%;">Student</th>
                                <th style="width: 35%;">Changes Summary</th>
                                <th class="text-end" style="width: 12%;">Old Total</th>
                                <th class="text-end" style="width: 12%;">New Total</th>
                                <th class="text-end" style="width: 12%;">Difference</th>
                                <th style="width: 4%;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($groupedDiffs as $studentId => $studentDiffs)
                            @php
                                $student = \App\Models\Student::find($studentId);
                                $studentOldTotal = $studentDiffs->sum('old_amount');
                                $studentNewTotal = $studentDiffs->sum('new_amount');
                                $studentDifference = $studentNewTotal - $studentOldTotal;
                                
                                // Count changes by type
                                $addedCount = $studentDiffs->where('action', 'added')->count();
                                $increasedCount = $studentDiffs->where('action', 'increased')->count();
                                $decreasedCount = $studentDiffs->where('action', 'decreased')->count();
                                $removedCount = $studentDiffs->where('action', 'removed')->count();
                                $totalChanges = $studentDiffs->count();
                            @endphp
                            <tr class="finance-table-row">
                                <td>
                                    @if($student)
                                        <strong>{{ $student->full_name }}</strong>
                                        <br><small class="text-muted">{{ $student->admission_number }}</small>
                                    @else
                                        <strong>Student ID: {{ $studentId }}</strong>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1 mb-2">
                                        @if($addedCount > 0)
                                            <span class="badge bg-success">{{ $addedCount }} Added</span>
                                        @endif
                                        @if($increasedCount > 0)
                                            <span class="badge bg-warning">{{ $increasedCount }} Increased</span>
                                        @endif
                                        @if($decreasedCount > 0)
                                            <span class="badge bg-danger">{{ $decreasedCount }} Decreased</span>
                                        @endif
                                        @if($removedCount > 0)
                                            <span class="badge bg-info">{{ $removedCount }} Removed</span>
                                        @endif
                                    </div>
                                    <div class="small text-muted">
                                        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none" data-bs-toggle="collapse" data-bs-target="#details-{{ $studentId }}" aria-expanded="false">
                                            <i class="bi bi-chevron-down"></i> View {{ $totalChanges }} change(s)
                                        </button>
                                    </div>
                                    <div class="collapse mt-2" id="details-{{ $studentId }}">
                                        <div class="card card-body p-2 bg-light">
                                            <table class="table table-sm table-borderless mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Votehead</th>
                                                        <th>Type</th>
                                                        <th class="text-end">Old</th>
                                                        <th class="text-end">New</th>
                                                        <th class="text-end">Diff</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($studentDiffs as $diff)
                                                    @php
                                                        $votehead = \App\Models\Votehead::find($diff['votehead_id']);
                                                        $oldAmount = $diff['old_amount'] ?? 0;
                                                        $newAmount = $diff['new_amount'] ?? 0;
                                                        $difference = $newAmount - $oldAmount;
                                                        
                                                        $badgeClass = match($diff['action']) {
                                                            'added' => 'bg-success',
                                                            'increased' => 'bg-warning',
                                                            'decreased' => 'bg-danger',
                                                            'removed' => 'bg-info',
                                                            default => 'bg-secondary'
                                                        };
                                                    @endphp
                                                    <tr>
                                                        <td><small>{{ $votehead->name ?? 'Votehead #' . $diff['votehead_id'] }}</small></td>
                                                        <td><span class="badge {{ $badgeClass }} badge-sm">{{ ucfirst($diff['action']) }}</span></td>
                                                        <td class="text-end"><small>{{ $oldAmount > 0 ? 'Ksh ' . number_format($oldAmount, 2) : '—' }}</small></td>
                                                        <td class="text-end"><small>Ksh {{ number_format($newAmount, 2) }}</small></td>
                                                        <td class="text-end"><small>{{ $difference != 0 ? ($difference > 0 ? '+' : '') . number_format($difference, 2) : '0.00' }}</small></td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end">
                                    @if($studentOldTotal > 0)
                                        <strong>Ksh {{ number_format($studentOldTotal, 2) }}</strong>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <strong class="{{ $studentDifference > 0 ? 'text-success' : ($studentDifference < 0 ? 'text-danger' : '') }}">
                                        Ksh {{ number_format($studentNewTotal, 2) }}
                                    </strong>
                                </td>
                                <td class="text-end">
                                    @if($studentDifference != 0)
                                        <strong class="{{ $studentDifference > 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $studentDifference > 0 ? '+' : '' }}{{ number_format($studentDifference, 2) }}
                                        </strong>
                                    @else
                                        <span class="text-muted">0.00</span>
                                    @endif
                                </td>
                                <td></td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            @php
                                $pageDiffs = collect($groupedDiffs->items())->values()->collapse();
                                $pageOldTotal = $pageDiffs->sum('old_amount');
                                $pageNewTotal = $pageDiffs->sum('new_amount');
                                $pageDiffTotal = $pageNewTotal - $pageOldTotal;
                            @endphp
                            <tr class="finance-table-footer">
                                <th colspan="2" class="text-end">Page Totals ({{ $groupedDiffs->count() }} students):</th>
                                <th class="text-end">
                                    Ksh {{ number_format($pageOldTotal, 2) }}
                                </th>
                                <th class="text-end">
                                    Ksh {{ number_format($pageNewTotal, 2) }}
                                </th>
                                <th class="text-end">
                                    Ksh {{ number_format($pageDiffTotal, 2) }}
                                </th>
                                <th></th>
                            </tr>
                            <tr class="finance-table-footer-total">
                                <th colspan="2" class="text-end"><strong>Grand Totals (All {{ $groupedDiffs->total() }} students):</strong></th>
                                <th class="text-end">
                                    <strong>Ksh {{ number_format($allDiffs->sum('old_amount'), 2) }}</strong>
                                </th>
                                <th class="text-end">
                                    <strong>Ksh {{ number_format($allDiffs->sum('new_amount'), 2) }}</strong>
                                </th>
                                <th class="text-end">
                                    <strong>Ksh {{ number_format($allDiffs->sum('new_amount') - $allDiffs->sum('old_amount'), 2) }}</strong>
                                </th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="finance-card-footer">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> 
                            Review all changes before committing. This action will create/update invoice items.
                        </small>
                    </div>
                    <div class="col-md-6 text-end">
                        <a href="{{ route('finance.posting.index') }}" class="btn btn-finance btn-finance-secondary me-2">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                        <button type="submit" class="btn btn-finance btn-finance-primary">
                            <i class="bi bi-cloud-upload"></i> Commit Posting ({{ $allDiffs->count() }} changes)
                        </button>
                    </div>
                </div>
            </div>
            
            @if($groupedDiffs->hasPages())
            <div class="finance-card-footer border-top">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div class="small text-muted">
                        Showing {{ $groupedDiffs->firstItem() }} to {{ $groupedDiffs->lastItem() }} of {{ $groupedDiffs->total() }} students
                    </div>
                    <div>
                        {{ $groupedDiffs->appends(array_filter([
                            'year' => $filters['year'] ?? null,
                            'term' => $filters['term'] ?? null,
                            'votehead_id' => $filters['votehead_id'] ?? null,
                            'class_id' => $filters['class_id'] ?? null,
                            'stream_id' => $filters['stream_id'] ?? null,
                            'student_id' => $filters['student_id'] ?? null,
                            'student_category_id' => $filters['student_category_id'] ?? null,
                            'effective_date' => $filters['effective_date'] ?? null,
                            'per_page' => $perPage,
                        ]))->links() }}
                    </div>
                </div>
            </div>
            @endif
        </div>
    </form>
    @else
    <div class="finance-card finance-animate">
        <div class="finance-card-body">
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> No changes detected for the selected filters.
            </div>
            <a href="{{ route('finance.posting.index') }}" class="btn btn-finance btn-finance-secondary">
                <i class="bi bi-arrow-left"></i> Back to Posting
            </a>
        </div>
    </div>
    @endif
    </div>
</div>

@push('scripts')
<script>
function changePerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    url.searchParams.set('page', '1'); // Reset to first page
    window.location.href = url.toString();
}
</script>
@endpush
@endsection
