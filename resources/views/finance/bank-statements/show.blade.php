@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'Transaction #' . $bankStatement->id,
        'icon' => 'bi bi-receipt',
        'subtitle' => 'Bank statement transaction details',
        'actions' => '<a href="' . route('finance.bank-statements.index') . '" class="btn btn-finance btn-finance-secondary"><i class="bi bi-arrow-left"></i> Back</a>'
    ])

    @include('finance.invoices.partials.alerts')

    <!-- Siblings Notification -->
    @if($bankStatement->student_id && count($siblings) > 0 && !$bankStatement->is_shared)
    <div class="alert alert-info alert-dismissible fade show finance-animate shadow-sm rounded-4 border-0 mb-4" role="alert">
        <div class="d-flex align-items-start">
            <div class="flex-shrink-0 me-3">
                <i class="bi bi-info-circle fs-4"></i>
            </div>
            <div class="flex-grow-1">
                <h5 class="alert-heading mb-2">
                    <i class="bi bi-people"></i> Siblings Detected
                </h5>
                <p class="mb-2">
                    This transaction is assigned to <strong>{{ $bankStatement->student->first_name }} {{ $bankStatement->student->last_name }}</strong> ({{ $bankStatement->student->admission_number }}), 
                    but there {{ count($siblings) === 1 ? 'is' : 'are' }} <strong>{{ count($siblings) }} sibling{{ count($siblings) === 1 ? '' : 's' }}</strong> in the same family.
                </p>
                <p class="mb-2">
                    <strong>Siblings:</strong>
                    @foreach($siblings as $sibling)
                        {{ $sibling->first_name }} {{ $sibling->last_name }} ({{ $sibling->admission_number }}){{ !$loop->last ? ', ' : '' }}
                    @endforeach
                </p>
                <p class="mb-0">
                    @if($bankStatement->isDraft())
                        <strong>You can share this payment among siblings</strong> using the form below. This allows you to allocate the payment amount across multiple students in the same family.
                    @else
                        <strong>Note:</strong> This transaction is {{ $bankStatement->status }}. To share payments, the transaction must be in draft status.
                    @endif
                </p>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="row">
        <div class="col-md-8">
            <!-- Transaction Details -->
            <div class="finance-card finance-animate mb-4 shadow-sm rounded-4 border-0">
                <div class="finance-card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Transaction Information</h5>
                    <div>
                        @if($bankStatement->status == 'draft')
                            <span class="badge bg-warning">Draft</span>
                        @elseif($bankStatement->status == 'confirmed')
                            <span class="badge bg-success">Confirmed</span>
                        @else
                            <span class="badge bg-danger">Rejected</span>
                        @endif
                    </div>
                </div>
                <div class="finance-card-body p-4">
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row mb-0">
                                <dt class="col-sm-5">Transaction Date:</dt>
                                <dd class="col-sm-7">{{ $bankStatement->transaction_date->format('d M Y') }}</dd>

                                <dt class="col-sm-5">Amount:</dt>
                                <dd class="col-sm-7">
                                    <strong class="{{ $bankStatement->transaction_type == 'credit' ? 'text-success' : 'text-danger' }}">
                                        {{ $bankStatement->transaction_type == 'credit' ? '+' : '-' }}Ksh {{ number_format($bankStatement->amount, 2) }}
                                    </strong>
                                </dd>

                                <dt class="col-sm-5">Bank Type:</dt>
                                <dd class="col-sm-7">
                                    <span class="badge bg-info">{{ strtoupper($bankStatement->bank_type ?? 'N/A') }}</span>
                                </dd>

                                <dt class="col-sm-5">Reference Number:</dt>
                                <dd class="col-sm-7"><code>{{ $bankStatement->reference_number ?? 'N/A' }}</code></dd>

                                <dt class="col-sm-5">Phone Number:</dt>
                                <dd class="col-sm-7">{{ $bankStatement->phone_number ?? 'N/A' }}</dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl class="row mb-0">
                                <dt class="col-sm-5">Description / Particulars:</dt>
                                <dd class="col-sm-7">
                                    <div class="text-break" style="word-wrap: break-word; white-space: pre-wrap;">{{ $bankStatement->description ?? 'N/A' }}</div>
                                </dd>

                                <dt class="col-sm-5">Match Status:</dt>
                                <dd class="col-sm-7">
                                    @if($bankStatement->match_status == 'matched')
                                        <span class="badge bg-success">Matched</span>
                                    @elseif($bankStatement->match_status == 'multiple_matches')
                                        <span class="badge bg-warning">Multiple Matches</span>
                                    @elseif($bankStatement->match_status == 'manual')
                                        <span class="badge bg-info">Manual</span>
                                    @else
                                        <span class="badge bg-secondary">Unmatched</span>
                                    @endif
                                    @if($bankStatement->match_confidence)
                                        <small class="text-muted">({{ number_format($bankStatement->match_confidence * 100, 0) }}%)</small>
                                    @endif
                                </dd>

                                @if($bankStatement->matched_admission_number)
                                <dt class="col-sm-5">Matched Admission:</dt>
                                <dd class="col-sm-7"><code>{{ $bankStatement->matched_admission_number }}</code></dd>
                                @endif

                                @if($bankStatement->matched_student_name)
                                <dt class="col-sm-5">Matched Name:</dt>
                                <dd class="col-sm-7">{{ $bankStatement->matched_student_name }}</dd>
                                @endif

                                @if($bankStatement->match_notes)
                                <dt class="col-sm-5">Match Notes:</dt>
                                <dd class="col-sm-7">{{ $bankStatement->match_notes }}</dd>
                                @endif
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Possible Matches -->
            @if(count($possibleMatches ?? []) > 0 && !$bankStatement->student_id)
            <div class="finance-card finance-animate mb-4 shadow-sm rounded-4 border-0">
                <div class="finance-card-header">
                    <h5 class="mb-0">Possible Matches</h5>
                    <span class="badge bg-warning">{{ count($possibleMatches) }} found</span>
                </div>
                <div class="finance-card-body p-4">
                    <p class="text-muted mb-3">Select a student to assign this transaction to:</p>
                    <div class="list-group">
                        @foreach($possibleMatches as $index => $match)
                            @php
                                $student = $match['student'];
                                $confidence = $match['confidence'] ?? 0;
                                $matchType = $match['match_type'] ?? 'unknown';
                                $matchedValue = $match['matched_value'] ?? '';
                                $studentSiblings = \App\Models\Student::where('family_id', $student->family_id)
                                    ->where('id', '!=', $student->id)
                                    ->where('archive', 0)
                                    ->where('is_alumni', false)
                                    ->get();
                            @endphp
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            <a href="{{ route('students.show', $student) }}" target="_blank">
                                                {{ $student->first_name }} {{ $student->last_name }}
                                            </a>
                                        </h6>
                                        <p class="text-muted mb-1 small">
                                            Admission: <code>{{ $student->admission_number }}</code>
                                            @if($student->classroom)
                                                | Class: {{ $student->classroom->name }}
                                            @endif
                                            @if($student->stream)
                                                | Stream: {{ $student->stream->name }}
                                            @endif
                                        </p>
                                        @if($studentSiblings->count() > 0)
                                            <p class="text-info mb-1 small">
                                                <i class="bi bi-people"></i> Has {{ $studentSiblings->count() }} sibling(s):
                                                @foreach($studentSiblings as $sib)
                                                    {{ $sib->first_name }} {{ $sib->last_name }}{{ !$loop->last ? ', ' : '' }}
                                                @endforeach
                                            </p>
                                        @endif
                                        <div class="small">
                                            <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $matchType)) }}</span>
                                            @if($matchedValue)
                                                <span class="text-muted">Matched: {{ $matchedValue }}</span>
                                            @endif
                                            <span class="badge bg-{{ $confidence >= 0.9 ? 'success' : ($confidence >= 0.8 ? 'warning' : 'secondary') }}">
                                                {{ number_format($confidence * 100, 0) }}% confidence
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ms-3">
                                        <form method="POST" action="{{ route('finance.bank-statements.update', $bankStatement) }}" class="d-inline">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="student_id" value="{{ $student->id }}">
                                            <input type="hidden" name="match_notes" value="Selected from {{ count($possibleMatches) }} possible matches ({{ $matchType }}, {{ number_format($confidence * 100, 0) }}% confidence)">
                                            <button type="submit" class="btn btn-sm btn-finance btn-finance-primary">
                                                <i class="bi bi-check-circle"></i> Select
                                            </button>
                                        </form>
                                        @if($studentSiblings->count() > 0)
                                            <button type="button" class="btn btn-sm btn-finance btn-finance-secondary mt-1" onclick="showShareModal{{ $student->id }}()">
                                                <i class="bi bi-share"></i> Share
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
            
            <!-- Manual Student Search for Rejected/Unassigned Transactions -->
            @if(!$bankStatement->student_id && in_array($bankStatement->status, ['draft', 'rejected']) && $bankStatement->match_status === 'unmatched')
            <div class="finance-card finance-animate mb-4 shadow-sm rounded-4 border-0">
                <div class="finance-card-header">
                    <h5 class="mb-0">Manual Student Assignment</h5>
                </div>
                <div class="finance-card-body p-4">
                    <p class="text-muted mb-3">Search and select a student to assign this transaction to:</p>
                    <div class="mb-3">
                        <label class="form-label">Search Student</label>
                        <input type="text" 
                               id="studentSearch" 
                               class="form-control" 
                               placeholder="Search by name or admission number..."
                               onkeyup="searchStudents()">
                    </div>
                    <div id="studentSearchResults" class="list-group" style="max-height: 400px; overflow-y: auto;">
                        <!-- Results will be populated here -->
                    </div>
                </div>
            </div>
            @endif

            <!-- Student Assignment -->
            @if($bankStatement->student)
            <div class="finance-card finance-animate mb-4 shadow-sm rounded-4 border-0">
                <div class="finance-card-header">
                    <h5 class="mb-0">Assigned Student</h5>
                </div>
                <div class="finance-card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">
                                <a href="{{ route('students.show', $bankStatement->student) }}">
                                    {{ $bankStatement->student->first_name }} {{ $bankStatement->student->last_name }}
                                </a>
                            </h6>
                            <p class="text-muted mb-0">
                                Admission: <code>{{ $bankStatement->student->admission_number }}</code>
                                @if($bankStatement->student->classroom)
                                    | Class: {{ $bankStatement->student->classroom->name }}
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Sharing Information -->
            @if($bankStatement->is_shared && $bankStatement->shared_allocations)
            <div class="finance-card finance-animate mb-4 shadow-sm rounded-4 border-0">
                <div class="finance-card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Shared Among Siblings</h5>
                    @if($bankStatement->isDraft())
                    <button type="button" class="btn btn-sm btn-finance btn-finance-primary" onclick="toggleEditAllocations()">
                        <i class="bi bi-pencil"></i> Edit Amounts
                    </button>
                    @endif
                </div>
                <div class="finance-card-body p-4">
                    <form id="editAllocationsForm" method="POST" action="{{ route('finance.bank-statements.update-allocations', $bankStatement) }}" style="display: none;">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <p class="text-muted">Total amount: <strong>Ksh {{ number_format($bankStatement->amount, 2) }}</strong></p>
                        </div>
                        @foreach($bankStatement->shared_allocations as $index => $allocation)
                            @php $student = \App\Models\Student::find($allocation['student_id']); @endphp
                            <div class="mb-3 p-3 border rounded">
                                <label class="form-label">
                                    <strong>{{ $student?->first_name }} {{ $student?->last_name }}</strong>
                                    <small class="text-muted">({{ $student?->admission_number }})</small>
                                </label>
                                <input type="hidden" name="allocations[{{ $index }}][student_id]" value="{{ $allocation['student_id'] }}">
                                <input type="number" 
                                       name="allocations[{{ $index }}][amount]" 
                                       class="form-control allocation-amount" 
                                       step="0.01" 
                                       min="0" 
                                       max="{{ $bankStatement->amount }}"
                                       value="{{ $allocation['amount'] }}"
                                       onchange="updateAllocationTotal()"
                                       required>
                            </div>
                        @endforeach
                        <div class="mb-3">
                            <strong>Remaining: <span id="allocationRemaining">Ksh {{ number_format($bankStatement->amount, 2) }}</span></strong>
                        </div>
                        <button type="submit" class="btn btn-finance btn-finance-success" id="saveAllocationsBtn">
                            <i class="bi bi-check-circle"></i> Save Changes
                        </button>
                        <button type="button" class="btn btn-finance btn-finance-secondary" onclick="toggleEditAllocations()">
                            Cancel
                        </button>
                    </form>
                    
                    <div id="viewAllocations">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Admission</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bankStatement->shared_allocations as $allocation)
                                    @php $student = \App\Models\Student::find($allocation['student_id']); @endphp
                                    <tr>
                                        <td>{{ $student?->first_name }} {{ $student?->last_name }}</td>
                                        <td><code>{{ $student?->admission_number }}</code></td>
                                        <td class="text-end">Ksh {{ number_format($allocation['amount'], 2) }}</td>
                                    </tr>
                                @endforeach
                                <tr class="table-active">
                                    <td colspan="2"><strong>Total</strong></td>
                                    <td class="text-end"><strong>Ksh {{ number_format($bankStatement->amount, 2) }}</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Payment Information -->
            @if($bankStatement->payment)
            <div class="finance-card finance-animate mb-4 shadow-sm rounded-4 border-0">
                <div class="finance-card-header">
                    <h5 class="mb-0">Created Payment</h5>
                </div>
                <div class="finance-card-body p-4">
                    <p>
                        Payment <a href="{{ route('finance.payments.show', $bankStatement->payment) }}">#{{ $bankStatement->payment->receipt_number ?? $bankStatement->payment->transaction_code }}</a>
                        has been created from this transaction.
                    </p>
                </div>
            </div>
            @endif

            <!-- Siblings (if family exists) -->
            @if($bankStatement->student_id && count($siblings) > 0 && !$bankStatement->is_shared)
            <div class="finance-card finance-animate mb-4 shadow-sm rounded-4 border-0">
                <div class="finance-card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-share"></i> Share Among Siblings
                    </h5>
                </div>
                <div class="finance-card-body p-4">
                    @if($bankStatement->isDraft())
                    <form method="POST" action="{{ route('finance.bank-statements.share', $bankStatement) }}">
                        @csrf
                        <p class="text-muted">Total amount: <strong>Ksh {{ number_format($bankStatement->amount, 2) }}</strong></p>
                        <p class="text-info mb-3">
                            <i class="bi bi-info-circle"></i> Allocate the payment amount among the siblings below. You can share with 1, 2, or 3 siblings (not all). Enter 0.00 for siblings you don't want to include. The total must equal the transaction amount.
                        </p>
                        <div id="siblingAllocations">
                            @php
                                $currentStudentBalance = $bankStatement->student ? \App\Services\StudentBalanceService::getTotalOutstandingBalance($bankStatement->student) : 0;
                            @endphp
                            @if($bankStatement->student)
                                <div class="mb-3 p-3 border rounded">
                                    <label class="form-label">
                                        <strong>{{ $bankStatement->student->first_name }} {{ $bankStatement->student->last_name }}</strong>
                                        <small class="text-muted">({{ $bankStatement->student->admission_number }})</small>
                                        <br><small class="text-danger">Balance: Ksh {{ number_format($currentStudentBalance, 2) }}</small>
                                    </label>
                                    <input type="hidden" name="allocations[0][student_id]" value="{{ $bankStatement->student->id }}">
                                    <input type="number" 
                                           name="allocations[0][amount]" 
                                           class="form-control sibling-amount" 
                                           step="0.01" 
                                           min="0" 
                                           max="{{ $bankStatement->amount }}"
                                           onchange="updateTotal()"
                                           oninput="updateTotal()"
                                           value=""
                                           placeholder="0.00 (leave 0 to exclude)">
                                    <small class="text-muted">Enter 0.00 or leave empty to exclude this student</small>
                                </div>
                            @endif
                            @foreach($siblings as $sibling)
                                @php
                                    $siblingBalance = \App\Services\StudentBalanceService::getTotalOutstandingBalance($sibling);
                                @endphp
                                <div class="mb-3 p-3 border rounded">
                                    <label class="form-label">
                                        <strong>{{ $sibling->first_name }} {{ $sibling->last_name }}</strong>
                                        <small class="text-muted">({{ $sibling->admission_number }})</small>
                                        <br><small class="text-danger">Balance: Ksh {{ number_format($siblingBalance, 2) }}</small>
                                    </label>
                                    <input type="hidden" name="allocations[{{ $bankStatement->student ? $loop->index + 1 : $loop->index }}][student_id]" value="{{ $sibling->id }}">
                                    <input type="number" 
                                           name="allocations[{{ $bankStatement->student ? $loop->index + 1 : $loop->index }}][amount]" 
                                           class="form-control sibling-amount" 
                                           step="0.01" 
                                           min="0" 
                                           max="{{ $bankStatement->amount }}"
                                           onchange="updateTotal()"
                                           oninput="updateTotal()"
                                           value=""
                                           placeholder="0.00 (leave 0 to exclude)">
                                    <small class="text-muted">Enter 0.00 or leave empty to exclude this student</small>
                                </div>
                            @endforeach
                        </div>
                        <div class="mb-3">
                            <strong>Remaining: <span id="remainingAmount">Ksh {{ number_format($bankStatement->amount, 2) }}</span></strong>
                        </div>
                        <button type="submit" class="btn btn-finance btn-finance-primary" id="shareBtn" disabled>
                            <i class="bi bi-share"></i> Share Payment
                        </button>
                    </form>
                    @else
                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-exclamation-triangle"></i> 
                        <strong>Cannot share payment:</strong> This transaction is {{ ucfirst($bankStatement->status) }}. 
                        Only draft transactions can be shared among siblings. 
                        @if($bankStatement->status === 'confirmed')
                            If you need to share this payment, you may need to reverse the payment first.
                        @endif
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <div class="col-md-4">
            <!-- Actions -->
            <div class="finance-card finance-animate mb-4 shadow-sm rounded-4 border-0">
                <div class="finance-card-header">
                    <h5 class="mb-0">Actions</h5>
                </div>
                <div class="finance-card-body p-4">
                    @if($bankStatement->isDraft())
                        @if($bankStatement->student_id || $bankStatement->is_shared)
                            <form method="POST" action="{{ route('finance.bank-statements.confirm', $bankStatement) }}" class="mb-2">
                                @csrf
                                <button type="submit" class="btn btn-finance btn-finance-success w-100">
                                    <i class="bi bi-check-circle"></i> Confirm Transaction
                                </button>
                            </form>
                        @endif

                        <a href="{{ route('finance.bank-statements.edit', $bankStatement) }}" class="btn btn-finance btn-finance-primary w-100 mb-2">
                            <i class="bi bi-pencil"></i> Edit / Match Manually
                        </a>

                        <form method="POST" action="{{ route('finance.bank-statements.reject', $bankStatement) }}" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-finance btn-finance-danger w-100" onclick="return confirm('Reject this transaction?')">
                                <i class="bi bi-x-circle"></i> Reject
                            </button>
                        </form>
                    @elseif($bankStatement->status == 'confirmed' && $bankStatement->payment)
                        <!-- Actions for confirmed transactions with payments -->
                        <form action="{{ route('finance.payments.reverse', $bankStatement->payment) }}" method="POST" class="d-inline w-100 mb-2" onsubmit="return confirm('Reverse this payment? This will undo all allocations and mark the payment as reversed.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-finance btn-finance-warning w-100">
                                <i class="bi bi-arrow-counterclockwise"></i> Reverse Payment
                            </button>
                        </form>
                        
                        <a href="{{ route('finance.payments.show', $bankStatement->payment) }}?action=transfer" class="btn btn-finance btn-finance-info w-100 mb-2">
                            <i class="bi bi-arrow-right-circle"></i> Transfer Payment
                        </a>
                        
                        @if($bankStatement->student)
                            @php
                                $siblings = \App\Models\Student::where('family_id', $bankStatement->student->family_id)
                                    ->where('id', '!=', $bankStatement->student->id)
                                    ->where('archive', 0)
                                    ->where('is_alumni', false)
                                    ->get();
                            @endphp
                            @if($siblings->count() > 0)
                                <a href="{{ route('finance.payments.show', $bankStatement->payment) }}?action=share" class="btn btn-finance btn-finance-secondary w-100 mb-2">
                                    <i class="bi bi-share"></i> Share Payment
                                </a>
                            @endif
                        @endif
                    @endif

                    @if($bankStatement->statement_file_path)
                        <a href="{{ route('finance.bank-statements.view-pdf', $bankStatement) }}" target="_blank" class="btn btn-finance btn-finance-info w-100 mb-2">
                            <i class="bi bi-file-pdf"></i> View Statement PDF
                        </a>
                        <a href="{{ route('finance.bank-statements.download-pdf', $bankStatement) }}" class="btn btn-finance btn-finance-secondary w-100 mb-2">
                            <i class="bi bi-download"></i> Download PDF
                        </a>
                    @endif
                    
                    @if($bankStatement->statement_file_path)
                        <form method="POST" action="{{ route('finance.bank-statements.reparse', $bankStatement) }}" class="mb-2" onsubmit="return confirm('Re-analyze this statement? This will delete all transactions and payments from this statement and re-parse it. This action cannot be undone.')">
                            @csrf
                            <button type="submit" class="btn btn-finance btn-finance-warning w-100">
                                <i class="bi bi-arrow-clockwise"></i> Re-Analyze Statement
                            </button>
                        </form>
                    @endif
                    
                    <form method="POST" action="{{ route('finance.bank-statements.destroy', $bankStatement) }}" onsubmit="return confirm('Delete this statement and ALL related records (transactions, payments, allocations)? This action cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-finance btn-finance-danger w-100">
                            <i class="bi bi-trash"></i> Delete Statement
                        </button>
                    </form>
                </div>
            </div>

            <!-- Statement File Info -->
            @if($bankStatement->statement_file_path)
            <div class="finance-card finance-animate mb-4 shadow-sm rounded-4 border-0">
                <div class="finance-card-header">
                    <h5 class="mb-0">Statement File</h5>
                </div>
                <div class="finance-card-body p-4">
                    <p class="text-muted small mb-0">
                        <i class="bi bi-file-pdf"></i> Bank statement PDF
                    </p>
                </div>
            </div>
            @endif
        </div>
    </div>

    <script>
        function updateTotal() {
            const amounts = Array.from(document.querySelectorAll('.sibling-amount')).map(input => parseFloat(input.value) || 0);
            const total = amounts.reduce((sum, amt) => sum + amt, 0);
            const transactionAmount = {{ $bankStatement->amount }};
            const remaining = transactionAmount - total;
            
            document.getElementById('remainingAmount').textContent = 'Ksh ' + remaining.toFixed(2);
            
            const shareBtn = document.getElementById('shareBtn');
            if (Math.abs(remaining) < 0.01 && total > 0) {
                shareBtn.disabled = false;
                shareBtn.classList.remove('btn-secondary');
                shareBtn.classList.add('btn-primary');
            } else {
                shareBtn.disabled = true;
                shareBtn.classList.remove('btn-primary');
                shareBtn.classList.add('btn-secondary');
            }
        }
        
        function toggleEditAllocations() {
            const form = document.getElementById('editAllocationsForm');
            const view = document.getElementById('viewAllocations');
            
            if (form && view) {
                if (form.style.display === 'none' || !form.style.display) {
                    form.style.display = 'block';
                    view.style.display = 'none';
                    updateAllocationTotal();
                } else {
                    form.style.display = 'none';
                    view.style.display = 'block';
                }
            }
        }
        
        function updateAllocationTotal() {
            const amounts = Array.from(document.querySelectorAll('.allocation-amount')).map(input => parseFloat(input.value) || 0);
            const total = amounts.reduce((sum, amt) => sum + amt, 0);
            const transactionAmount = {{ $bankStatement->amount }};
            const remaining = transactionAmount - total;
            
            const remainingEl = document.getElementById('allocationRemaining');
            if (remainingEl) {
                remainingEl.textContent = 'Ksh ' + remaining.toFixed(2);
            }
            
            const saveBtn = document.getElementById('saveAllocationsBtn');
            if (saveBtn) {
                if (Math.abs(remaining) < 0.01 && total > 0) {
                    saveBtn.disabled = false;
                    saveBtn.classList.remove('btn-secondary');
                    saveBtn.classList.add('btn-success');
                } else {
                    saveBtn.disabled = true;
                    saveBtn.classList.remove('btn-success');
                    saveBtn.classList.add('btn-secondary');
                }
            }
        }
    </script>
@endsection

@push('scripts')
<script>
// Student search functionality
let searchTimeout;
function searchStudents() {
    const query = document.getElementById('studentSearch').value;
    
    clearTimeout(searchTimeout);
    
    if (query.length < 2) {
        document.getElementById('studentSearchResults').innerHTML = '<div class="text-muted p-3">Enter at least 2 characters to search</div>';
        return;
    }
    
    searchTimeout = setTimeout(() => {
        fetch(`/api/students/search?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                displaySearchResults(data);
            })
            .catch(error => {
                console.error('Search error:', error);
                document.getElementById('studentSearchResults').innerHTML = '<div class="text-danger p-3">Error searching students</div>';
            });
    }, 300);
}

function displaySearchResults(students) {
    const resultsContainer = document.getElementById('studentSearchResults');
    
    if (students.length === 0) {
        resultsContainer.innerHTML = '<div class="text-muted p-3">No students found</div>';
        return;
    }
    
    let html = '';
    students.forEach(student => {
        const hasSiblings = student.siblings && student.siblings.length > 0;
        html += `
            <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">${student.first_name} ${student.last_name}</h6>
                        <p class="text-muted mb-1 small">
                            Admission: <code>${student.admission_number}</code>
                            ${student.classroom ? `| Class: ${student.classroom.name}` : ''}
                        </p>
                        ${hasSiblings ? `
                            <p class="text-info mb-1 small">
                                <i class="bi bi-people"></i> Has ${student.siblings.length} sibling(s):
                                ${student.siblings.map(s => `${s.first_name} ${s.last_name}`).join(', ')}
                            </p>
                        ` : ''}
                    </div>
                    <div class="ms-3">
                        <form method="POST" action="{{ route('finance.bank-statements.update', $bankStatement) }}" class="d-inline">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="student_id" value="${student.id}">
                            <input type="hidden" name="match_notes" value="Manually selected via search">
                            <button type="submit" class="btn btn-sm btn-finance btn-finance-primary">
                                <i class="bi bi-check-circle"></i> Select
                            </button>
                        </form>
                        ${hasSiblings ? `
                            <button type="button" class="btn btn-sm btn-finance btn-finance-secondary mt-1" onclick="showShareModalForStudent(${student.id}, '${student.first_name} ${student.last_name}', ${JSON.stringify(student.siblings).replace(/"/g, '&quot;')})">
                                <i class="bi bi-share"></i> Share
                            </button>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    });
    
    resultsContainer.innerHTML = html;
}

function showShareModalForStudent(studentId, studentName, siblings) {
    // Create modal for sharing payment with siblings
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = 'shareModal' + studentId;
    modal.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Share Payment with Siblings</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('finance.bank-statements.share', $bankStatement) }}">
                    @csrf
                    <div class="modal-body">
                        <p class="text-muted">Total amount: <strong>Ksh {{ number_format($bankStatement->amount, 2) }}</strong></p>
                        <p class="text-info mb-3">
                            <i class="bi bi-info-circle"></i> Allocate the payment amount among the siblings below. You can share with 1, 2, or 3 siblings (not all). Enter 0.00 for siblings you don't want to include.
                        </p>
                        <div class="mb-3 p-3 border rounded">
                            <label class="form-label">
                                <strong>${studentName}</strong>
                            </label>
                            <input type="hidden" name="allocations[0][student_id]" value="${studentId}">
                            <input type="number" 
                                   name="allocations[0][amount]" 
                                   class="form-control modal-sibling-amount" 
                                   step="0.01" 
                                   min="0" 
                                   max="{{ $bankStatement->amount }}"
                                   onchange="updateModalTotal()"
                                   oninput="updateModalTotal()"
                                   placeholder="0.00 (leave 0 to exclude)">
                        </div>
                        ${siblings.map((sib, idx) => `
                            <div class="mb-3 p-3 border rounded">
                                <label class="form-label">
                                    <strong>${sib.first_name} ${sib.last_name}</strong>
                                    <small class="text-muted">(${sib.admission_number})</small>
                                </label>
                                <input type="hidden" name="allocations[${idx + 1}][student_id]" value="${sib.id}">
                                <input type="number" 
                                       name="allocations[${idx + 1}][amount]" 
                                       class="form-control modal-sibling-amount" 
                                       step="0.01" 
                                       min="0" 
                                       max="{{ $bankStatement->amount }}"
                                       onchange="updateModalTotal()"
                                       oninput="updateModalTotal()"
                                       placeholder="0.00 (leave 0 to exclude)">
                            </div>
                        `).join('')}
                        <div class="mb-3">
                            <strong>Remaining: <span id="modalRemainingAmount">Ksh {{ number_format($bankStatement->amount, 2) }}</span></strong>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-finance btn-finance-primary" id="modalShareBtn" disabled>
                            <i class="bi bi-share"></i> Share Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
    
    modal.addEventListener('hidden.bs.modal', function () {
        modal.remove();
    });
}

function updateModalTotal() {
    const amounts = document.querySelectorAll('.modal-sibling-amount');
    let total = 0;
    amounts.forEach(input => {
        const value = parseFloat(input.value) || 0;
        total += value;
    });
    
    const transactionAmount = {{ $bankStatement->amount }};
    const remaining = transactionAmount - total;
    
    document.getElementById('modalRemainingAmount').textContent = `Ksh ${remaining.toFixed(2)}`;
    
    const shareBtn = document.getElementById('modalShareBtn');
    if (Math.abs(remaining) < 0.01 && total > 0) {
        shareBtn.disabled = false;
        shareBtn.classList.remove('btn-secondary');
        shareBtn.classList.add('btn-finance', 'btn-finance-primary');
    } else {
        shareBtn.disabled = true;
        shareBtn.classList.remove('btn-finance', 'btn-finance-primary');
        shareBtn.classList.add('btn-secondary');
    }
}
</script>
@endpush

