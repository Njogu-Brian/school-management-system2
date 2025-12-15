@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="mb-3">
                <i class="bi bi-eye"></i> Posting Preview - Fee Changes
            </h3>
            
            @include('finance.invoices.partials.alerts')
            
            @if(isset($summary))
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card border-primary">
                        <div class="card-body text-center">
                            <h5 class="text-primary">{{ $summary['total'] }}</h5>
                            <small class="text-muted">Total Changes</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card border-success">
                        <div class="card-body text-center">
                            <h5 class="text-success">{{ $summary['added'] ?? 0 }}</h5>
                            <small class="text-muted">Added</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card border-warning">
                        <div class="card-body text-center">
                            <h5 class="text-warning">{{ $summary['increased'] ?? 0 }}</h5>
                            <small class="text-muted">Increased</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card border-danger">
                        <div class="card-body text-center">
                            <h5 class="text-danger">{{ $summary['decreased'] ?? 0 }}</h5>
                            <small class="text-muted">Decreased</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-info">
                        <div class="card-body text-center">
                            <h5 class="text-info">
                                Ksh {{ number_format($summary['total_amount_change'] ?? 0, 2) }}
                            </h5>
                            <small class="text-muted">Net Amount Change</small>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    @if(isset($diffs) && $diffs->isNotEmpty())
    <form method="POST" action="{{ route('finance.posting.commit') }}">
        @csrf
        <input type="hidden" name="year" value="{{ $filters['year'] ?? request('year') }}">
        <input type="hidden" name="term" value="{{ $filters['term'] ?? request('term') }}">
        <input type="hidden" name="activate_now" value="1">
        
        @foreach($diffs as $index => $diff)
            <input type="hidden" name="diffs[{{ $index }}][student_id]" value="{{ $diff['student_id'] }}">
            <input type="hidden" name="diffs[{{ $index }}][votehead_id]" value="{{ $diff['votehead_id'] }}">
            <input type="hidden" name="diffs[{{ $index }}][old_amount]" value="{{ $diff['old_amount'] ?? 0 }}">
            <input type="hidden" name="diffs[{{ $index }}][new_amount]" value="{{ $diff['new_amount'] }}">
            <input type="hidden" name="diffs[{{ $index }}][action]" value="{{ $diff['action'] }}">
            <input type="hidden" name="diffs[{{ $index }}][origin]" value="{{ $diff['origin'] ?? 'structure' }}">
        @endforeach

        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Change Details</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Student</th>
                                <th>Votehead</th>
                                <th>Change Type</th>
                                <th class="text-end">Old Amount</th>
                                <th class="text-end">New Amount</th>
                                <th class="text-end">Difference</th>
                                <th>Origin</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($diffs as $diff)
                            @php
                                $student = \App\Models\Student::find($diff['student_id']);
                                $votehead = \App\Models\Votehead::find($diff['votehead_id']);
                                $oldAmount = $diff['old_amount'] ?? 0;
                                $newAmount = $diff['new_amount'] ?? 0;
                                $difference = $newAmount - $oldAmount;
                                
                                $badgeClass = match($diff['action']) {
                                    'added' => 'bg-success',
                                    'increased' => 'bg-warning',
                                    'decreased' => 'bg-danger',
                                    'unchanged' => 'bg-secondary',
                                    default => 'bg-info'
                                };
                                
                                $textClass = match($diff['action']) {
                                    'added' => 'text-success',
                                    'increased' => 'text-warning',
                                    'decreased' => 'text-danger',
                                    default => 'text-muted'
                                };
                            @endphp
                            <tr>
                                <td>
                                    @if($student)
                                        {{ $student->first_name }} {{ $student->last_name }}
                                        <br><small class="text-muted">{{ $student->admission_number }}</small>
                                    @else
                                        Student ID: {{ $diff['student_id'] }}
                                    @endif
                                </td>
                                <td>{{ $votehead->name ?? 'Votehead #' . $diff['votehead_id'] }}</td>
                                <td>
                                    <span class="badge {{ $badgeClass }}">
                                        {{ ucfirst(str_replace('_', ' ', $diff['action'])) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    @if($oldAmount > 0)
                                        Ksh {{ number_format($oldAmount, 2) }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end {{ $textClass }}">
                                    <strong>Ksh {{ number_format($newAmount, 2) }}</strong>
                                </td>
                                <td class="text-end {{ $textClass }}">
                                    @if($difference != 0)
                                        <strong>{{ $difference > 0 ? '+' : '' }}{{ number_format($difference, 2) }}</strong>
                                    @else
                                        <span class="text-muted">0.00</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        {{ $diff['origin'] ?? 'structure' }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="3" class="text-end">Totals:</th>
                                <th class="text-end">
                                    Ksh {{ number_format($diffs->sum('old_amount'), 2) }}
                                </th>
                                <th class="text-end">
                                    Ksh {{ number_format($diffs->sum('new_amount'), 2) }}
                                </th>
                                <th class="text-end">
                                    Ksh {{ number_format($diffs->sum('new_amount') - $diffs->sum('old_amount'), 2) }}
                                </th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> 
                            Review all changes before committing. This action will create/update invoice items.
                        </small>
                    </div>
                    <div class="col-md-6 text-end">
                        <a href="{{ route('finance.posting.index') }}" class="btn btn-secondary me-2">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-cloud-upload"></i> Commit Posting ({{ $diffs->count() }} changes)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    @else
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
            <h4 class="mt-3 text-muted">Nothing Has Changed</h4>
            <p class="text-muted mb-4">
                No fee changes detected for the selected filters. All fees are up to date.
            </p>
            <a href="{{ route('finance.posting.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Posting
            </a>
        </div>
    </div>
    @endif
</div>
@endsection
