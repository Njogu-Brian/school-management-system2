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
            @php
                // Group diffs by student
                $groupedByStudent = $run->diffs->groupBy('student_id');
            @endphp
            
            @foreach($groupedByStudent as $studentId => $studentDiffs)
                @php
                    $student = $studentDiffs->first()->student;
                    // Filter out reversal diffs for calculations
                    $originalDiffs = $studentDiffs->where('action', '!=', 'reversed');
                    $studentTotalChange = $originalDiffs->sum(function($diff) {
                        return ($diff->new_amount ?? 0) - ($diff->old_amount ?? 0);
                    });
                    $studentItemsCount = $originalDiffs->count();
                    $hasReversed = $studentDiffs->contains('action', 'reversed');
                    
                    // Check if student still has items linked to this posting run
                    $hasReversableItems = false;
                    if ($student && $run->canBeReversed() && !$hasReversed) {
                        $hasReversableItems = \App\Models\InvoiceItem::whereHas('invoice', function($q) use ($student) {
                            $q->where('student_id', $student->id);
                        })
                        ->where('posting_run_id', $run->id)
                        ->exists();
                    }
                @endphp
                
                <div class="border-bottom">
                    <div class="p-3 bg-light d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">
                                {{ $student->full_name ?? 'N/A' }}
                                @if($student)
                                    <small class="text-muted">({{ $student->admission_number }})</small>
                                @endif
                            </h6>
                            <small class="text-muted">
                                {{ $studentItemsCount }} change(s) | 
                                Net Change: 
                                <strong class="{{ $studentTotalChange >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $studentTotalChange >= 0 ? '+' : '' }}Ksh {{ number_format($studentTotalChange, 2) }}
                                </strong>
                                @if($hasReversed)
                                    <span class="badge bg-warning ms-2">Partially Reversed</span>
                                @endif
                            </small>
                        </div>
                        <div>
                            @if($hasReversableItems)
                            <form action="{{ route('finance.posting.reverse-student', $run) }}" method="POST" class="d-inline" 
                                  onsubmit="return confirm('Are you sure you want to reverse posting for {{ $student->full_name }}? This will restore their invoice items to the state before this posting.');">
                                @csrf
                                <input type="hidden" name="student_id" value="{{ $student->id }}">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reverse Student
                                </button>
                            </form>
                            @elseif($hasReversed)
                            <span class="badge bg-secondary">Already Reversed</span>
                            @endif
                            <button type="button" class="btn btn-sm btn-outline-secondary" 
                                    onclick="toggleStudentDetails({{ $studentId }})">
                                <i class="bi bi-chevron-down" id="chevron-{{ $studentId }}"></i> 
                                <span id="toggle-text-{{ $studentId }}">Show Details</span>
                            </button>
                        </div>
                    </div>
                    
                    <div id="student-details-{{ $studentId }}" style="display: none;">
                        <div class="table-responsive">
                            <table class="finance-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Votehead</th>
                                        <th>Change Type</th>
                                        <th class="text-end">Old Amount</th>
                                        <th class="text-end">New Amount</th>
                                        <th class="text-end">Difference</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($studentDiffs as $diff)
                                    @php
                                        $oldAmount = $diff->old_amount ?? 0;
                                        $newAmount = $diff->new_amount ?? 0;
                                        $difference = $newAmount - $oldAmount;
                                        
                                        $badgeClass = match($diff->action) {
                                            'added' => 'bg-success',
                                            'increased' => 'bg-warning',
                                            'decreased' => 'bg-danger',
                                            'unchanged' => 'bg-secondary',
                                            'reversed' => 'bg-info',
                                            default => 'bg-info'
                                        };
                                    @endphp
                                    <tr>
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
            @endforeach
        </div>
    </div>
    
    <script>
        function toggleStudentDetails(studentId) {
            const details = document.getElementById('student-details-' + studentId);
            const chevron = document.getElementById('chevron-' + studentId);
            const toggleText = document.getElementById('toggle-text-' + studentId);
            
            if (details.style.display === 'none') {
                details.style.display = 'block';
                chevron.classList.remove('bi-chevron-down');
                chevron.classList.add('bi-chevron-up');
                toggleText.textContent = 'Hide Details';
            } else {
                details.style.display = 'none';
                chevron.classList.remove('bi-chevron-up');
                chevron.classList.add('bi-chevron-down');
                toggleText.textContent = 'Show Details';
            }
        }
    </script>
    @endif
        </div>
    </div>
  </div>
</div>
@endsection

