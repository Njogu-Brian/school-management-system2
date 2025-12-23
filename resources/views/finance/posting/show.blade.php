@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    <div class="finance-card finance-animate mb-3">
        <div class="finance-card-header d-flex justify-content-between align-items-center">
            <h3 class="mb-0">
                <i class="bi bi-clock-history"></i> Posting Run #{{ $run->id }}
            </h3>
            <div>
                @if($run->canBeReversed())
                <form action="{{ route('finance.posting.reverse', $run) }}" method="POST" class="d-inline" 
                      onsubmit="return confirm('Are you sure you want to reverse this posting run? This will remove all invoice items created by this run.');">
                    @csrf
                    @method('POST')
                    <button type="submit" class="btn btn-finance btn-finance-danger">
                        <i class="bi bi-arrow-counterclockwise"></i> Reverse Run
                    </button>
                </form>
                @endif
                <a href="{{ route('finance.posting.index') }}" class="btn btn-finance btn-finance-outline">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </div>
        <div class="finance-card-body">

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="finance-card">
                <div class="finance-card-body">
                    <h6 class="finance-muted mb-3">Run Information</h6>
                    <p class="mb-2">
                        <strong>Status:</strong> 
                        <span class="badge bg-{{ $run->status === 'completed' ? 'success' : ($run->status === 'reversed' ? 'danger' : 'warning') }}">
                            {{ ucfirst($run->status) }}
                        </span>
                    </p>
                    <p class="mb-2">
                        <strong>Academic Year:</strong> {{ $run->academicYear->name ?? 'N/A' }}
                    </p>
                    <p class="mb-2">
                        <strong>Term:</strong> {{ $run->term->name ?? 'N/A' }}
                    </p>
                    <p class="mb-2">
                        <strong>Posted By:</strong> {{ $run->postedBy->name ?? 'System' }}
                    </p>
                    <p class="mb-0">
                        <strong>Posted At:</strong> {{ $run->posted_at ? $run->posted_at->format('d M Y, H:i') : 'N/A' }}
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="finance-card">
                <div class="finance-card-body">
                    <h6 class="finance-muted mb-3">Statistics</h6>
                    <p class="mb-2">
                        <strong>Items Posted:</strong> {{ $run->total_students_affected ?? $run->diffs->count() }}
                    </p>
                    <p class="mb-2">
                        <strong>Total Amount:</strong> 
                        Ksh {{ number_format($run->total_amount_posted ?? $run->diffs->sum('new_amount'), 2) }}
                    </p>
                    @if($run->is_dry_run)
                    <p class="mb-0">
                        <span class="badge bg-info">Dry Run Mode</span>
                    </p>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="finance-card">
                <div class="finance-card-body">
                    <h6 class="finance-muted mb-3">Reversal Info</h6>
                    @if($run->status === 'reversed')
                    <p class="mb-2">
                        <strong>Reversed By:</strong> {{ $run->reversedBy->name ?? 'N/A' }}
                    </p>
                    <p class="mb-0">
                        <strong>Reversed At:</strong> 
                        {{ $run->reversed_at ? $run->reversed_at->format('d M Y, H:i') : 'N/A' }}
                    </p>
                    @else
                    <p class="text-muted mb-0">Not reversed</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($run->notes)
    <div class="alert alert-info">
        <strong>Notes:</strong> {{ $run->notes }}
    </div>
    @endif

    @if($run->diffs->isNotEmpty())
    <div class="finance-card finance-animate">
        <div class="finance-card-header">
            <h5 class="mb-0">Changes in This Run</h5>
        </div>
        <div class="finance-card-body p-0">
            <div class="table-responsive">
                <table class="finance-table mb-0">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Votehead</th>
                            <th>Change Type</th>
                            <th class="text-end">Old Amount</th>
                            <th class="text-end">New Amount</th>
                            <th class="text-end">Difference</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($run->diffs as $diff)
                        @php
                            $oldAmount = $diff->old_amount ?? 0;
                            $newAmount = $diff->new_amount ?? 0;
                            $difference = $newAmount - $oldAmount;
                            
                            $badgeClass = match($diff->action) {
                                'added' => 'bg-success',
                                'increased' => 'bg-warning',
                                'decreased' => 'bg-danger',
                                'unchanged' => 'bg-secondary',
                                default => 'bg-info'
                            };
                        @endphp
                        <tr>
                            <td>
                                {{ $diff->student->first_name ?? 'N/A' }} {{ $diff->student->last_name ?? '' }}
                                @if($diff->student)
                                <br><small class="text-muted">{{ $diff->student->admission_number }}</small>
                                @endif
                            </td>
                            <td>{{ $diff->votehead->name ?? 'Votehead #' . $diff->votehead_id }}</td>
                            <td>
                                <span class="badge {{ $badgeClass }}">
                                    {{ ucfirst(str_replace('_', ' ', $diff->action)) }}
                                </span>
                            </td>
                            <td class="text-end">
                                @if($oldAmount > 0)
                                    Ksh {{ number_format($oldAmount, 2) }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">Ksh {{ number_format($newAmount, 2) }}</td>
                            <td class="text-end">
                                @if($difference != 0)
                                    <strong>{{ $difference > 0 ? '+' : '' }}{{ number_format($difference, 2) }}</strong>
                                @else
                                    <span class="text-muted">0.00</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
        </div>
    </div>
  </div>
</div>
@endsection

