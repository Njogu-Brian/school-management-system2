@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'Transaction #' . $bankStatement->id,
        'icon' => 'bi bi-receipt',
        'subtitle' => 'Bank statement transaction details',
        'actions' => '<a href="' . route('finance.bank-statements.index') . '" class="btn btn-finance btn-finance-secondary"><i class="bi bi-arrow-left"></i> Back</a>'
    ])

    @include('finance.invoices.partials.alerts')

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
                                <dt class="col-sm-5">Description:</dt>
                                <dd class="col-sm-7">{{ $bankStatement->description ?? 'N/A' }}</dd>

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
                <div class="finance-card-header">
                    <h5 class="mb-0">Shared Among Siblings</h5>
                </div>
                <div class="finance-card-body p-4">
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
            @if(count($siblings) > 0 && $bankStatement->isDraft() && !$bankStatement->is_shared)
            <div class="finance-card finance-animate mb-4 shadow-sm rounded-4 border-0">
                <div class="finance-card-header">
                    <h5 class="mb-0">Share Among Siblings</h5>
                </div>
                <div class="finance-card-body p-4">
                    <form method="POST" action="{{ route('finance.bank-statements.share', $bankStatement) }}">
                        @csrf
                        <p class="text-muted">Total amount: <strong>Ksh {{ number_format($bankStatement->amount, 2) }}</strong></p>
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
                                           placeholder="0.00">
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
                                           placeholder="0.00">
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
    </script>
@endsection

