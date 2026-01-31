@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'Transaction #' . $bankStatement->id,
        'icon' => 'bi bi-receipt',
        'subtitle' => ($isC2B ?? false) ? 'M-PESA C2B transaction details' : 'Bank statement transaction details',
        'actions' => '<a href="' . route('finance.bank-statements.index') . '" class="btn btn-finance btn-finance-secondary"><i class="bi bi-arrow-left"></i> Back</a>'
    ])

    @include('finance.invoices.partials.alerts')

    <!-- Payment Conflict Alert -->
    @if(session('payment_conflict'))
        @php
            $conflict = session('payment_conflict');
            $conflictingPayments = collect($conflict['conflicting_payments'])->map(function($p) {
                return \App\Models\Payment::with('student')->find($p['id']);
            })->filter();
        @endphp
        <div class="alert alert-warning alert-dismissible fade show finance-animate shadow-sm rounded-4 border-0 mb-4" role="alert">
            <div class="d-flex align-items-start">
                <div class="flex-shrink-0 me-3">
                    <i class="bi bi-exclamation-triangle fs-4"></i>
                </div>
                <div class="flex-grow-1">
                    <h5 class="alert-heading mb-2">
                        <i class="bi bi-exclamation-circle"></i> Payment Conflict Detected
                    </h5>
                    <p class="mb-2">
                        A payment already exists with the same transaction code <strong>{{ $conflict['transaction_code'] }}</strong> for one or more students in this transaction.
                    </p>
                    <p class="mb-2">
                        <strong>Conflicting Payment(s):</strong>
                    </p>
                    <ul class="mb-3">
                        @foreach($conflictingPayments as $payment)
                            <li>
                                <strong>#{{ $payment->receipt_number ?? $payment->transaction_code }}</strong>
                                @if($payment->student)
                                    - {{ $payment->student->full_name }} ({{ $payment->student->admission_number }})
                                @endif
                                - Ksh {{ number_format($payment->amount, 2) }}
                                @if($payment->reversed)
                                    <span class="badge bg-danger">Reversed</span>
                                @else
                                    <span class="badge bg-success">Active</span>
                                @endif
                                @if($payment->payment_date)
                                    <br><small class="text-muted">Date: {{ \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') }}</small>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    <p class="mb-0">
                        <button type="button" class="btn btn-finance btn-finance-warning" data-bs-toggle="modal" data-bs-target="#paymentConflictModal">
                            <i class="bi bi-gear"></i> Resolve Conflict
                        </button>
                    </p>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Siblings Notification -->
    @if($bankStatement->student_id && count($siblings) > 0 && !($bankStatement->is_shared ?? false))
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
                    This transaction is assigned to <strong>{{ ($rawTransaction->student ?? null) ? $rawTransaction->student->full_name : 'N/A' }}</strong> ({{ ($rawTransaction->student ?? null) ? $rawTransaction->student->admission_number : 'N/A' }}), 
                    but there {{ count($siblings) === 1 ? 'is' : 'are' }} <strong>{{ count($siblings) }} sibling{{ count($siblings) === 1 ? '' : 's' }}</strong> in the same family.
                </p>
                <p class="mb-2">
                    <strong>Siblings:</strong>
                    @foreach($siblings as $sibling)
                        {{ $sibling->full_name }} ({{ $sibling->admission_number }}){{ !$loop->last ? ', ' : '' }}
                    @endforeach
                </p>
                <p class="mb-0">
                    @if($bankStatement->status === 'draft')
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
                        @php
                            // Ensure status is correct if payment exists
                            $displayStatus = $bankStatement->status;
                            if (($bankStatement->payment_id && ($bankStatement->payment_created ?? false)) && $displayStatus !== 'rejected') {
                                $displayStatus = 'confirmed';
                            }
                        @endphp
                        @if($displayStatus == 'draft')
                            <span class="badge bg-warning">Draft</span>
                        @elseif($displayStatus == 'confirmed')
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
                                <dd class="col-sm-7">{{ ($bankStatement->transaction_date instanceof \Carbon\Carbon) ? $bankStatement->transaction_date->format('d M Y') : \Carbon\Carbon::parse($bankStatement->transaction_date)->format('d M Y') }}</dd>

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

                                @if(isset($bankStatement->matched_admission_number) && $bankStatement->matched_admission_number)
                                <dt class="col-sm-5">Matched Admission:</dt>
                                <dd class="col-sm-7"><code>{{ $bankStatement->matched_admission_number }}</code></dd>
                                @endif

                                @if(isset($bankStatement->matched_student_name) && $bankStatement->matched_student_name)
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
                                // Handle both formats: bank statement (has 'student' object) and C2B (has 'student_id')
                                if (isset($match['student']) && is_object($match['student'])) {
                                    // Bank statement format - has student object
                                    $student = $match['student'];
                                    $confidence = $match['confidence'] ?? 0;
                                    $matchType = $match['match_type'] ?? 'unknown';
                                    $matchedValue = $match['matched_value'] ?? '';
                                } elseif (isset($match['student_id'])) {
                                    // C2B format - has student_id, need to load student
                                    $student = \App\Models\Student::find($match['student_id']);
                                    $confidence = $match['confidence'] ?? ($match['confidence'] ?? 0) / 100; // C2B uses 0-100, convert to 0-1
                                    $matchType = $match['reason'] ?? 'unknown';
                                    $matchedValue = $match['admission_number'] ?? $match['student_name'] ?? '';
                                } else {
                                    // Skip if neither format
                                    continue;
                                }
                                
                                // Skip if student not found
                                if (!$student) {
                                    continue;
                                }
                                
                                // Get siblings via family_id ONLY (not through siblings() relationship)
                                // Limit to 10 siblings max to prevent display issues with bad data
                                $studentSiblings = collect();
                                if ($student->family_id) {
                                    $studentSiblings = \App\Models\Student::where('family_id', $student->family_id)
                                        ->where('id', '!=', $student->id)
                                        ->where('archive', 0)
                                        ->where('is_alumni', false)
                                        ->limit(10) // Safety limit
                                        ->get();
                                }
                            @endphp
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            <a href="{{ route('students.show', $student) }}" target="_blank">
                                                {{ $student->full_name }}
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
                                                    {{ $sib->full_name }}{{ !$loop->last ? ', ' : '' }}
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
                                        <form method="POST" action="{{ route('finance.bank-statements.update', $bankStatement->id) }}" class="d-inline">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="student_id" value="{{ $student->id }}">
                                            <input type="hidden" name="match_notes" value="Selected from {{ count($possibleMatches) }} possible matches ({{ $matchType }}, {{ number_format($confidence * 100, 0) }}% confidence)">
                                            <button type="submit" class="btn btn-sm btn-finance btn-finance-primary">
                                                <i class="bi bi-check-circle"></i> Select
                                            </button>
                                        </form>
                                        @if($studentSiblings->count() > 0)
                                            <button type="button" class="btn btn-sm btn-finance btn-finance-secondary mt-1" onclick="showShareModalForStudent({{ $student->id }}, {{ json_encode($student->full_name) }}, {{ json_encode($studentSiblings->map(function($s) { return ['id' => $s->id, 'full_name' => $s->full_name, 'admission_number' => $s->admission_number]; })->toArray()) }})">
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
            @if(!$bankStatement->student_id && in_array($bankStatement->status, ['draft', 'rejected', 'unmatched']) && $bankStatement->match_status === 'unmatched' && !($bankStatement->is_shared ?? false))
            <div class="finance-card finance-animate mb-4 shadow-sm rounded-4 border-0">
                <div class="finance-card-header">
                    <h5 class="mb-0">Manual Student Assignment or Share Among Siblings</h5>
                </div>
                <div class="finance-card-body p-4">
                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="assign-tab" data-bs-toggle="tab" data-bs-target="#assign-pane" type="button" role="tab">
                                <i class="bi bi-person-plus"></i> Assign to Student
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="share-tab" data-bs-toggle="tab" data-bs-target="#share-pane" type="button" role="tab">
                                <i class="bi bi-share"></i> Share Among Siblings
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content">
                        <!-- Assign to Single Student Tab -->
                        <div class="tab-pane fade show active" id="assign-pane" role="tabpanel">
                            <p class="text-muted mb-3">Search and select a student to assign this transaction to:</p>
                            <form method="POST" action="{{ route('finance.bank-statements.update', $bankStatement->id) }}" id="assignStudentForm">
                                @csrf
                                @method('PUT')
                                @include('partials.student_live_search', [
                                    'hiddenInputId' => 'assign_student_id',
                                    'displayInputId' => 'assignStudentSearch',
                                    'resultsId' => 'assignStudentResults',
                                    'enableButtonId' => 'assignStudentBtn',
                                    'placeholder' => 'Search by name or admission number...',
                                    'includeAlumniArchived' => true
                                ])
                                <input type="hidden" name="match_notes" value="Manually assigned">
                                <div class="mt-3">
                                    <button type="submit" class="btn btn-finance btn-finance-primary" id="assignStudentBtn" disabled>
                                        <i class="bi bi-check-circle"></i> Assign to Student
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Share Among Siblings Tab -->
                        <div class="tab-pane fade" id="share-pane" role="tabpanel">
                            <p class="text-muted mb-3">Search for a student to find their siblings, then share the payment amount among them:</p>
                            <div class="mb-3">
                                @include('partials.student_live_search', [
                                    'hiddenInputId' => 'share_student_id',
                                    'displayInputId' => 'shareStudentSearch',
                                    'resultsId' => 'shareStudentResults',
                                    'placeholder' => 'Search by name or admission number...',
                                    'includeAlumniArchived' => true
                                ])
                            </div>
                            <div id="shareStudentSearchResults" class="list-group mb-3" style="max-height: 300px; overflow-y: auto; display: none;">
                                <!-- Sibling results will be populated here -->
                            </div>
                            
                            <!-- Share Form (hidden until student selected) -->
                            <div id="shareFormContainer" style="display: none;">
                                <form method="POST" action="{{ route('finance.bank-statements.share', $bankStatement->id) }}">
                                    @csrf
                                    <p class="text-muted">Total amount: <strong>Ksh {{ number_format($bankStatement->amount, 2) }}</strong></p>
                                    <p class="text-info mb-3">
                                        <i class="bi bi-info-circle"></i> Allocate the payment amount among the siblings below. Enter 0.00 for siblings you don't want to include. The total must equal the transaction amount.
                                    </p>
                                    <div id="shareSiblingAllocations">
                                        <!-- Will be populated by JavaScript -->
                                    </div>
                                    <div class="mb-3">
                                        <strong>Remaining: <span id="shareRemainingAmount">Ksh {{ number_format($bankStatement->amount, 2) }}</span></strong>
                                    </div>
                                    <button type="submit" class="btn btn-finance btn-finance-primary" id="shareSubmitBtn" disabled>
                                        <i class="bi bi-share"></i> Share Payment
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Student Assignment -->
            @if($rawTransaction->student ?? null)
            <div class="finance-card finance-animate mb-4 shadow-sm rounded-4 border-0">
                <div class="finance-card-header">
                    <h5 class="mb-0">Assigned Student</h5>
                </div>
                <div class="finance-card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">
                                <a href="{{ route('students.show', $rawTransaction->student->id) }}">
                                    {{ $rawTransaction->student->full_name }}
                                </a>
                            </h6>
                            <p class="text-muted mb-0">
                                Admission: <code>{{ $rawTransaction->student->admission_number }}</code>
                                @if($rawTransaction->student->classroom)
                                    | Class: {{ $rawTransaction->student->classroom->name }}
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Linked Payment -->
            @php
                $linkedPayment = null;
                // First, check payment_id directly (most reliable)
                if ($bankStatement->payment_id) {
                    $linkedPayment = \App\Models\Payment::with(['student'])->find($bankStatement->payment_id);
                }
                
                // Also check the raw transaction's payment_id (in case normalized data is stale)
                if (!$linkedPayment && isset($rawTransaction->payment_id)) {
                    $linkedPayment = \App\Models\Payment::with(['student'])->find($rawTransaction->payment_id);
                }
                
                // Also check for payments by reference number (for sibling sharing)
                if (!$linkedPayment && $bankStatement->reference_number) {
                    $linkedPayment = \App\Models\Payment::where('transaction_code', $bankStatement->reference_number)
                        ->where('reversed', false)
                        ->whereNull('deleted_at')
                        ->with(['student'])
                        ->first();
                }
                
                // For C2B, also check by trans_id
                if (!$linkedPayment && $isC2B && isset($rawTransaction->trans_id)) {
                    $linkedPayment = \App\Models\Payment::where('transaction_code', $rawTransaction->trans_id)
                        ->where('reversed', false)
                        ->whereNull('deleted_at')
                        ->with(['student'])
                        ->first();
                }
                
                // Also check by reference from raw transaction (for bank statements)
                if (!$linkedPayment && !$isC2B && isset($rawTransaction->reference_number)) {
                    $linkedPayment = \App\Models\Payment::where('transaction_code', $rawTransaction->reference_number)
                        ->where('reversed', false)
                        ->whereNull('deleted_at')
                        ->with(['student'])
                        ->first();
                }
                
                // Check for sibling payments (shared transaction code)
                $siblingPayments = collect();
                $refToCheck = $bankStatement->reference_number ?? ($rawTransaction->reference_number ?? null);
                if ($refToCheck) {
                    $siblingPayments = \App\Models\Payment::where('transaction_code', 'LIKE', $refToCheck . '-%')
                        ->where('reversed', false)
                        ->whereNull('deleted_at')
                        ->with(['student'])
                        ->get();
                }
            @endphp
            @if($linkedPayment || $siblingPayments->isNotEmpty())
            <div class="finance-card finance-animate mb-4 shadow-sm rounded-4 border-0" style="border-left: 4px solid #28a745 !important;">
                <div class="finance-card-header d-flex justify-content-between align-items-center" style="background-color: #d4edda;">
                    <h5 class="mb-0">
                        <i class="bi bi-receipt-cutoff text-success"></i> Linked Payment(s)
                    </h5>
                    @if($linkedPayment && !$linkedPayment->reversed)
                        <span class="badge bg-success">Active</span>
                    @elseif($linkedPayment && $linkedPayment->reversed)
                        <span class="badge bg-warning">Reversed</span>
                    @endif
                </div>
                <div class="finance-card-body p-4" style="background-color: #f8f9fa;">
                    @if($linkedPayment)
                    <div class="mb-3 p-3 border rounded" style="background-color: #fff;">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="flex-grow-1">
                                <h6 class="mb-2">
                                    <a href="{{ route('finance.payments.show', $linkedPayment->id) }}" class="text-primary font-weight-bold" style="text-decoration: underline; font-size: 16px;">
                                        <i class="bi bi-receipt"></i> Receipt: {{ $linkedPayment->receipt_number ?? 'N/A' }}
                                    </a>
                                </h6>
                                <div class="mb-2" style="color: #495057;">
                                    <strong>Narration:</strong> 
                                    <span style="color: #212529;">{{ $linkedPayment->narration ?? 'No narration provided' }}</span>
                                </div>
                                <div class="mb-2" style="color: #495057;">
                                    <strong>Payment Date:</strong> 
                                    <span style="color: #212529;">
                                        <i class="bi bi-calendar"></i> {{ $linkedPayment->payment_date ? $linkedPayment->payment_date->format('d M Y') : 'No date' }}
                                        @if($linkedPayment->payment_date)
                                            <small class="text-muted">({{ $linkedPayment->payment_date->format('H:i') }})</small>
                                        @endif
                                    </span>
                                </div>
                                <div class="mb-2" style="color: #495057;">
                                    <strong>Amount:</strong> 
                                    <span style="color: #28a745; font-weight: 600; font-size: 16px;">
                                        KES {{ number_format($linkedPayment->amount, 2) }}
                                    </span>
                                </div>
                                @if($linkedPayment->student)
                                <div class="mb-2" style="color: #495057;">
                                    <strong>Student:</strong> 
                                    <a href="{{ route('students.show', $linkedPayment->student->id) }}" class="text-info">
                                        {{ $linkedPayment->student->full_name }} ({{ $linkedPayment->student->admission_number }})
                                    </a>
                                </div>
                                @endif
                                @if($linkedPayment->transaction_code)
                                <div class="mb-2" style="color: #495057;">
                                    <strong>Transaction Code:</strong> 
                                    <code style="background-color: #e9ecef; padding: 2px 6px; border-radius: 3px;">{{ $linkedPayment->transaction_code }}</code>
                                </div>
                                @endif
                                @if($linkedPayment->reversed)
                                <div class="alert alert-warning mb-0 mt-2" style="font-size: 12px;">
                                    <i class="bi bi-exclamation-triangle"></i> This payment was reversed on {{ $linkedPayment->reversed_at ? $linkedPayment->reversed_at->format('d M Y H:i') : 'N/A' }}
                                </div>
                                @endif
                            </div>
                            <div class="ms-3">
                                <a href="{{ route('finance.payments.show', $linkedPayment->id) }}" class="btn btn-sm btn-success">
                                    <i class="bi bi-eye"></i> View Payment
                                </a>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($siblingPayments->isNotEmpty())
                    <div class="mt-3">
                        <h6 class="mb-2" style="color: #495057;">
                            <i class="bi bi-people"></i> Sibling Payments ({{ $siblingPayments->count() }})
                        </h6>
                        @foreach($siblingPayments as $siblingPayment)
                        <div class="mb-2 p-2 border rounded" style="background-color: #fff;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="flex-grow-1">
                                    <a href="{{ route('finance.payments.show', $siblingPayment->id) }}" class="text-primary font-weight-bold" style="text-decoration: underline;">
                                        <i class="bi bi-receipt"></i> {{ $siblingPayment->receipt_number ?? 'N/A' }}
                                    </a>
                                    <div class="small text-muted mt-1">
                                        {{ $siblingPayment->narration ?? 'No narration' }}
                                    </div>
                                    <div class="small text-muted">
                                        <i class="bi bi-calendar"></i> {{ $siblingPayment->payment_date ? $siblingPayment->payment_date->format('d M Y') : 'No date' }}
                                        | KES {{ number_format($siblingPayment->amount, 2) }}
                                        @if($siblingPayment->student)
                                            | {{ $siblingPayment->student->full_name }}
                                        @endif
                                    </div>
                                </div>
                                <a href="{{ route('finance.payments.show', $siblingPayment->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    @if(!$linkedPayment && $siblingPayments->isEmpty())
                    <p class="text-muted mb-0">No linked payments found</p>
                    @endif
                </div>
            </div>
            @endif

            <!-- Sharing Information -->
            @if(($bankStatement->is_shared ?? false) && ($bankStatement->shared_allocations ?? null))
            <div class="finance-card finance-animate mb-4 shadow-sm rounded-4 border-0">
                <div class="finance-card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Shared Among Siblings</h5>
                    @if(($bankStatement->status === 'draft' || $bankStatement->status === 'confirmed') && $bankStatement->status !== 'rejected')
                    <button type="button" class="btn btn-sm btn-finance btn-finance-primary" onclick="toggleEditAllocations()">
                        <i class="bi bi-pencil"></i> Edit Amounts
                    </button>
                    @endif
                </div>
                <div class="finance-card-body p-4">
                    <form id="editAllocationsForm" method="POST" action="{{ route('finance.bank-statements.update-allocations', $bankStatement->id) }}" style="display: none;">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="version" value="{{ $bankStatement->version ?? 0 }}">
                        <div class="mb-3">
                            <p class="text-muted">Total amount: <strong>Ksh {{ number_format($bankStatement->amount, 2) }}</strong></p>
                            @if($bankStatement->status === 'confirmed' && $bankStatement->payment_created)
                                <p class="text-info small mb-0">
                                    <i class="bi bi-info-circle"></i> 
                                    Editing will update the related payment amounts. If a payment amount decreases, excess allocations will be removed.
                                </p>
                            @elseif($bankStatement->status === 'confirmed' && !$bankStatement->payment_created)
                                <p class="text-info small mb-0">
                                    <i class="bi bi-info-circle"></i> 
                                    Editing allocations. After saving, you can create the payment using the "Create Payment" button.
                                </p>
                            @endif
                        </div>
                        @foreach(($bankStatement->shared_allocations ?? []) as $index => $allocation)
                            @php $student = \App\Models\Student::find($allocation['student_id']); @endphp
                            <div class="mb-3 p-3 border rounded">
                                <label class="form-label">
                                    <strong>{{ $student?->full_name }}</strong>
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
                                        <td>{{ $student?->full_name }}</td>
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
            @if($activePayments->isNotEmpty() || $reversedPayments->isNotEmpty())
            <div class="finance-card finance-animate mb-4 shadow-sm rounded-4 border-0">
                <div class="finance-card-header">
                    <h5 class="mb-0">Created Payment(s)</h5>
                </div>
                <div class="finance-card-body p-4">
                    {{-- Reversed first (history), then active / newly created below --}}
                    @if($reversedPayments->isNotEmpty())
                        <div class="alert alert-warning mb-4">
                            <h6 class="alert-heading mb-2">
                                <i class="bi bi-exclamation-triangle"></i> Reversed Payment(s)
                            </h6>
                            <ul class="list-unstyled mb-0">
                                @foreach($reversedPayments as $payment)
                                    <li class="mb-2">
                                        <del class="text-muted">
                                            <a href="{{ route('finance.payments.show', $payment) }}" class="text-decoration-none text-muted">
                                                #{{ $payment->receipt_number ?? $payment->transaction_code }}
                                            </a>
                                        </del>
                                        @if($payment->student)
                                            - {{ $payment->student->full_name }} ({{ $payment->student->admission_number }})
                                        @endif
                                        - Ksh {{ number_format($payment->amount, 2) }}
                                        <br>
                                        <small class="text-muted">
                                            <strong>Reversed:</strong> {{ $payment->reversed_at ? \Carbon\Carbon::parse($payment->reversed_at)->format('d M Y H:i') : 'N/A' }}
                                            @if($payment->reversal_reason)
                                                | <strong>Reason:</strong> {{ $payment->reversal_reason }}
                                            @endif
                                        </small>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if($activePayments->isNotEmpty())
                        <div class="mb-0">
                            <h6 class="text-success mb-2">
                                <i class="bi bi-check-circle"></i> Active / Newly Created Payment(s)
                            </h6>
                            <ul class="list-unstyled mb-0">
                                @foreach($activePayments as $payment)
                                    <li class="mb-2">
                                        <a href="{{ route('finance.payments.show', $payment) }}" class="text-decoration-none">
                                            <strong>#{{ $payment->receipt_number ?? $payment->transaction_code }}</strong>
                                        </a>
                                        @if($payment->student)
                                            - {{ $payment->student->full_name }} ({{ $payment->student->admission_number }})
                                        @endif
                                        - Ksh {{ number_format($payment->amount, 2) }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
            @elseif($bankStatement->status === 'confirmed' && !$bankStatement->payment_created)
            <div class="finance-card finance-animate mb-4 shadow-sm rounded-4 border-0">
                <div class="finance-card-header">
                    <h5 class="mb-0">Transaction Status</h5>
                </div>
                <div class="finance-card-body p-4">
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle"></i> 
                        <strong>Unallocated Uncollected:</strong> This transaction is confirmed but no payment has been created. 
                        The related payment may have been reversed, or payment creation is pending.
                    </div>
                </div>
            </div>
            @endif

            <!-- Siblings (if family exists) -->
            @if($bankStatement->student_id && count($siblings) > 0 && !($bankStatement->is_shared ?? false))
            <div class="finance-card finance-animate mb-4 shadow-sm rounded-4 border-0">
                <div class="finance-card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-share"></i> Share Among Siblings
                    </h5>
                </div>
                <div class="finance-card-body p-4">
                    @php
                        // Check if there are any active (non-reversed) payments
                        $hasActivePayments = $activePayments->isNotEmpty();
                        // Allow sharing if: draft, or confirmed without active payments, or not rejected
                        $canShare = $bankStatement->status === 'draft' 
                            || ($bankStatement->status === 'confirmed' && !$hasActivePayments)
                            || ($bankStatement->status !== 'confirmed' && $bankStatement->status !== 'rejected' && !$hasActivePayments);
                    @endphp
                    @if($canShare)
                    <form method="POST" action="{{ route('finance.bank-statements.share', $bankStatement->id) }}">
                        @csrf
                        <p class="text-muted">Total amount: <strong>Ksh {{ number_format($bankStatement->amount, 2) }}</strong></p>
                        <p class="text-info mb-3">
                            <i class="bi bi-info-circle"></i> Allocate the payment amount among the siblings below. You can share with 1, 2, or 3 siblings (not all). Enter 0.00 for siblings you don't want to include. The total must equal the transaction amount.
                        </p>
                        <div id="siblingAllocations">
                            @php
                                $isSwimming = \Illuminate\Support\Facades\Schema::hasColumn('bank_statement_transactions', 'is_swimming_transaction') 
                                    && $bankStatement->is_swimming_transaction;
                                
                                if ($isSwimming) {
                                    // Get swimming wallet balance
                                    $currentStudentWallet = ($rawTransaction->student ?? null)
                                        ? \App\Models\SwimmingWallet::getOrCreateForStudent($rawTransaction->student->id) 
                                        : null;
                                    $currentStudentBalance = $currentStudentWallet ? $currentStudentWallet->balance : 0;
                                    $balanceLabel = 'Swimming Balance';
                                } else {
                                    // Get fee balance
                                    $currentStudentBalance = ($rawTransaction->student ?? null)
                                        ? \App\Services\StudentBalanceService::getTotalOutstandingBalance($rawTransaction->student) 
                                        : 0;
                                    $balanceLabel = 'Balance';
                                }
                            @endphp
                            @if($rawTransaction->student ?? null)
                                <div class="mb-3 p-3 border rounded">
                                    <label class="form-label">
                                        <strong>{{ $rawTransaction->student->full_name }}</strong>
                                        <small class="text-muted">({{ $rawTransaction->student->admission_number }})</small>
                                        <br><small class="text-danger">{{ $balanceLabel }}: Ksh {{ number_format($currentStudentBalance, 2) }}</small>
                                    </label>
                                    <input type="hidden" name="allocations[0][student_id]" value="{{ $rawTransaction->student->id }}">
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
                                    if ($isSwimming) {
                                        // Get swimming wallet balance
                                        $siblingWallet = \App\Models\SwimmingWallet::getOrCreateForStudent($sibling->id);
                                        $siblingBalance = $siblingWallet->balance;
                                        $siblingBalanceLabel = 'Swimming Balance';
                                    } else {
                                        // Get fee balance
                                        $siblingBalance = \App\Services\StudentBalanceService::getTotalOutstandingBalance($sibling);
                                        $siblingBalanceLabel = 'Balance';
                                    }
                                @endphp
                                <div class="mb-3 p-3 border rounded">
                                    <label class="form-label">
                                        <strong>{{ $sibling->full_name }}</strong>
                                        <small class="text-muted">({{ $sibling->admission_number }})</small>
                                        <br><small class="text-danger">{{ $siblingBalanceLabel }}: Ksh {{ number_format($siblingBalance, 2) }}</small>
                                    </label>
                                    <input type="hidden" name="allocations[{{ ($rawTransaction->student ?? null) ? $loop->index + 1 : $loop->index }}][student_id]" value="{{ $sibling->id }}">
                                    <input type="number" 
                                           name="allocations[{{ ($rawTransaction->student ?? null) ? $loop->index + 1 : $loop->index }}][amount]" 
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
                        @if($bankStatement->status === 'confirmed' && $activePayments->isNotEmpty())
                            This transaction is confirmed and has active (non-reversed) payment(s). To share this payment, you must first reverse the existing payment(s).
                        @elseif($bankStatement->status === 'rejected')
                            Rejected transactions cannot be shared. Please assign a student first.
                        @else
                            Only draft transactions or confirmed transactions without active payments can be shared among siblings.
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
                    @if($bankStatement->status === 'draft')
                        @if($bankStatement->student_id || ($bankStatement->is_shared ?? false))
                            <form method="POST" action="{{ route('finance.bank-statements.confirm', $bankStatement->id) }}" class="mb-2">
                                @csrf
                                <button type="submit" class="btn btn-finance btn-finance-success w-100">
                                    <i class="bi bi-check-circle"></i> Confirm Transaction
                                </button>
                            </form>
                        @endif

                        <a href="{{ route('finance.bank-statements.edit', $bankStatement->id) }}" class="btn btn-finance btn-finance-primary w-100 mb-2">
                            <i class="bi bi-pencil"></i> Edit / Match Manually
                        </a>
                    @elseif($bankStatement->status == 'confirmed' && !$bankStatement->payment_created)
                        <!-- Actions for confirmed transactions without payments (unallocated uncollected) -->
                        @if($bankStatement->student_id || ($bankStatement->is_shared ?? false))
                            <form method="POST" action="{{ route('finance.bank-statements.create-payment', $bankStatement->id) }}" class="mb-2" onsubmit="return confirm('Create payment for this transaction? This will allocate the payment to student invoices.');">
                                @csrf
                                <button type="submit" class="btn btn-finance btn-finance-success w-100">
                                    <i class="bi bi-cash-coin"></i> Create Payment
                                </button>
                            </form>
                        @endif
                    @endif

                    @if($bankStatement->status !== 'rejected')
                    <button type="button" class="btn btn-finance btn-finance-danger w-100 mb-2" data-bs-toggle="modal" data-bs-target="#rejectTransactionModal">
                        <i class="bi bi-x-circle"></i> Reject
                    </button>
                    @endif

                    @php
                        $isSwimming = $bankStatement->is_swimming_transaction ?? false;
                        $hasAllocations = false;
                        if ($isSwimming) {
                            if (!$isC2B && \Illuminate\Support\Facades\Schema::hasTable('swimming_transaction_allocations')) {
                                $hasAllocations = \App\Models\SwimmingTransactionAllocation::where('bank_statement_transaction_id', $bankStatement->id)
                                    ->where('status', '!=', \App\Models\SwimmingTransactionAllocation::STATUS_REVERSED)
                                    ->exists();
                            } elseif ($isC2B && $bankStatement->payment_id) {
                                // For C2B, check if payment exists (indicates allocation)
                                $hasAllocations = true;
                            }
                        }
                    @endphp
                    @if($isSwimming && !$hasAllocations && $bankStatement->status !== 'rejected')
                        <form method="POST" action="{{ route('finance.bank-statements.unmark-swimming', $bankStatement->id) }}{{ isset($isC2B) && $isC2B ? '?type=c2b' : '?type=bank' }}" class="mb-2" onsubmit="return confirm('Revert this transaction from swimming? It will be treated as a regular fee payment again.')">
                            @csrf
                            <button type="submit" class="btn btn-finance btn-finance-warning w-100" title="Revert to regular payments (unmark as swimming)">
                                <i class="bi bi-arrow-return-left"></i> Revert to Regular Payments
                            </button>
                        </form>
                    @endif
                    
                    @if($bankStatement->statement_file_path)
                        @if(!($isC2B ?? false) && $bankStatement->statement_file_path)
                            <a href="{{ route('finance.bank-statements.view-pdf', $bankStatement->id) }}" target="_blank" class="btn btn-finance btn-finance-info w-100 mb-2">
                                <i class="bi bi-file-pdf"></i> View Statement PDF
                            </a>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Reject Transaction Confirmation Modal -->
            @if($bankStatement->status !== 'rejected')
            <div class="modal fade" id="rejectTransactionModal" tabindex="-1" aria-labelledby="rejectTransactionModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title" id="rejectTransactionModalLabel">
                                <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>Confirm Reject Transaction
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-3">Are you sure you want to <strong>reject</strong> this transaction? This will reset it to <strong>unassigned</strong> so you can match and allocate again.</p>
                            <div class="alert alert-warning mb-0">
                                <strong><i class="bi bi-exclamation-circle me-1"></i> Please note:</strong>
                                <ul class="mb-0 mt-2 ps-3">
                                    <li>Any <strong>associated payment(s) will be reversed</strong> and removed.</li>
                                    <li><strong>Matching and confirmation</strong> will be undone.</li>
                                    <li>All <strong>allocations to siblings</strong> or <strong>sharing among multiple students</strong> will be cleared.</li>
                                    <li>The transaction will move to <strong>unassigned</strong> with no matches. You must <strong>manually match</strong>, <strong>allocate</strong>, <strong>confirm</strong>, and then <strong>create payment</strong> to proceed.</li>
                                </ul>
                            </div>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-finance btn-finance-secondary" data-bs-dismiss="modal">Cancel</button>
                            <form method="POST" action="{{ route('finance.bank-statements.reject', $bankStatement->id) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-finance btn-finance-danger">
                                    <i class="bi bi-x-circle"></i> Confirm Reject
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Payment Conflict Resolution Modal -->
            @if(session('payment_conflict'))
                @php
                    $conflict = session('payment_conflict');
                    $conflictingPayments = collect($conflict['conflicting_payments'])->map(function($p) {
                        return \App\Models\Payment::with('student')->find($p['id']);
                    })->filter();
                @endphp
                <div class="modal fade" id="paymentConflictModal" tabindex="-1" aria-labelledby="paymentConflictModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header border-0 pb-0">
                                <h5 class="modal-title" id="paymentConflictModalLabel">
                                    <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Resolve Payment Conflict
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="mb-3">
                                    A payment conflict was detected. Please choose how to resolve it (existing payment linking is recommended):
                                </p>
                                <div class="alert alert-info mb-4">
                                    <strong>Transaction Code:</strong> <code>{{ $conflict['transaction_code'] }}</code><br>
                                    <strong>Affected Student(s):</strong> 
                                    @foreach($conflictingPayments as $payment)
                                        @if($payment->student)
                                            {{ $payment->student->full_name }} ({{ $payment->student->admission_number }}){{ !$loop->last ? ', ' : '' }}
                                        @endif
                                    @endforeach
                                </div>

                                <h6 class="mb-3">Conflicting Payment(s):</h6>
                                <div class="table-responsive mb-4">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Receipt #</th>
                                                <th>Student</th>
                                                <th>Amount</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($conflictingPayments as $payment)
                                                <tr>
                                                    <td><code>{{ $payment->receipt_number ?? $payment->transaction_code }}</code></td>
                                                    <td>
                                                        @if($payment->student)
                                                            {{ $payment->student->full_name }}<br>
                                                            <small class="text-muted">{{ $payment->student->admission_number }}</small>
                                                        @else
                                                            <span class="text-muted">N/A</span>
                                                        @endif
                                                    </td>
                                                    <td>Ksh {{ number_format($payment->amount, 2) }}</td>
                                                    <td>
                                                        @if($payment->payment_date)
                                                            {{ \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') }}
                                                        @else
                                                            <span class="text-muted">N/A</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($payment->reversed)
                                                            <span class="badge bg-danger">Reversed</span>
                                                        @else
                                                            <span class="badge bg-success">Active</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <h6 class="mb-3">Choose an action:</h6>
                                <div class="list-group">
                                    @foreach($conflictingPayments as $payment)
                                        @if($payment->student)
                                            <div class="list-group-item">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <div>
                                                        <strong>{{ $payment->student->full_name }}</strong> ({{ $payment->student->admission_number }})<br>
                                                        <small class="text-muted">Receipt: {{ $payment->receipt_number ?? $payment->transaction_code }}</small>
                                                    </div>
                                                    <div class="text-end">
                                                        <strong>Ksh {{ number_format($payment->amount, 2) }}</strong><br>
                                                        @if($payment->reversed)
                                                            <span class="badge bg-danger">Reversed</span>
                                                        @else
                                                            <span class="badge bg-success">Active</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="btn-group w-100" role="group">
                                                    @if(!$payment->reversed)
                                                        <form method="POST" action="{{ route('finance.bank-statements.resolve-conflict.reverse', $bankStatement->id) }}" class="flex-fill">
                                                            @csrf
                                                            <input type="hidden" name="payment_id" value="{{ $payment->id }}">
                                                            <input type="hidden" name="student_id" value="{{ $payment->student_id }}">
                                                            <button type="submit" class="btn btn-finance btn-finance-warning w-100" onclick="return confirm('Reverse this payment and create a new one? This will undo all allocations.')">
                                                                <i class="bi bi-arrow-counterclockwise"></i> Reverse & Create New
                                                            </button>
                                                        </form>
                                                        <form method="POST" action="{{ route('finance.bank-statements.resolve-conflict.keep', $bankStatement->id) }}" class="flex-fill">
                                                            @csrf
                                                            <input type="hidden" name="payment_id" value="{{ $payment->id }}">
                                                            <button type="submit" class="btn btn-finance btn-finance-success w-100">
                                                                <i class="bi bi-check-circle"></i> Keep Existing
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                            <div class="modal-footer border-0 pt-0">
                                <button type="button" class="btn btn-finance btn-finance-secondary" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Statement File Info -->
            @if(!($isC2B ?? false) && $bankStatement->statement_file_path)
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
        @php
            $isSwimmingForJS = \Illuminate\Support\Facades\Schema::hasColumn('bank_statement_transactions', 'is_swimming_transaction') 
                && $bankStatement->is_swimming_transaction;
        @endphp
        const isSwimmingTransaction = @json($isSwimmingForJS);
        
        // Auto-show payment conflict modal if conflict exists
        @if(session('payment_conflict'))
            document.addEventListener('DOMContentLoaded', function() {
                const conflictModal = new bootstrap.Modal(document.getElementById('paymentConflictModal'));
                conflictModal.show();
            });
        @endif
        
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

        document.addEventListener('DOMContentLoaded', function () {
            const shareInputs = Array.from(document.querySelectorAll('.sibling-amount'));
            const shareBtn = document.getElementById('shareBtn');
            if (shareInputs.length > 0 && shareBtn) {
                const hasValue = shareInputs.some(input => parseFloat(input.value || 0) > 0);
                if (!hasValue) {
                    shareInputs[0].value = {{ $bankStatement->amount }};
                }
                updateTotal();
            }
        });
        
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
// The student_live_search partial already handles enabling the button via enableButtonId

// Share functionality
let selectedShareStudent = null;
let selectedShareSiblings = [];

// Handle student selection for assigning to single student
window.addEventListener('student-selected', async function(event) {
    const student = event.detail;
    const assignHiddenInput = document.getElementById('assign_student_id');
    const shareHiddenInput = document.getElementById('share_student_id');
    
    // Check if this is from the assign tab
    if (assignHiddenInput && assignHiddenInput.value == student.id) {
        await showStudentBalanceForAssign(student);
    }
    
    // Check if this is from the share tab
    if (shareHiddenInput && shareHiddenInput.value == student.id) {
        selectStudentForShare(student);
    }
});

// Show balance for single student assignment
async function showStudentBalanceForAssign(student) {
    // Check if balance container exists, if not create it
    let balanceContainer = document.getElementById('assignStudentBalance');
    if (!balanceContainer) {
        const assignForm = document.getElementById('assignStudentForm');
        if (assignForm) {
            // Create balance display element
            balanceContainer = document.createElement('div');
            balanceContainer.id = 'assignStudentBalance';
            balanceContainer.className = 'mt-3 p-3 border rounded bg-light';
            
            // Insert after the student search input
            const searchInput = document.getElementById('assignStudentSearch');
            if (searchInput && searchInput.parentElement) {
                searchInput.parentElement.parentElement.appendChild(balanceContainer);
            }
        }
    }
    
    if (!balanceContainer) return;
    
    // Show loading state
    balanceContainer.innerHTML = '<small class="text-muted">Loading balance...</small>';
    
    try {
        const url = `{{ route('finance.bank-statements.student-balance', ['student' => ':id']) }}`.replace(':id', student.id) + 
                   (isSwimmingTransaction ? '?swimming=1' : '');
        const response = await fetch(url);
        const data = await response.json();
        
        const balance = data.balance || 0;
        const label = data.label || 'Balance';
        
        balanceContainer.innerHTML = `
            <small class="text-muted">
                <strong>${student.full_name}</strong> 
                (${student.admission_number || 'N/A'})
                <br>
                <span class="text-danger">${label}: Ksh ${parseFloat(balance).toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
            </small>
        `;
    } catch (error) {
        console.error('Error fetching balance:', error);
        balanceContainer.innerHTML = '<small class="text-muted text-danger">Unable to load balance</small>';
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function selectStudentForShare(student) {
    selectedShareStudent = student;
    
    // Get siblings from search result (already included in the API response)
    selectedShareSiblings = student.siblings || [];
    
    populateShareForm();
    
    // Show the share form container
    const formContainer = document.getElementById('shareFormContainer');
    if (formContainer) {
        formContainer.style.display = 'block';
        formContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

async function populateShareForm() {
    const container = document.getElementById('shareSiblingAllocations');
    const formContainer = document.getElementById('shareFormContainer');
    const transactionAmount = {{ $bankStatement->amount }};
    
    if (!selectedShareStudent) {
        return;
    }
    
    // Show loading state
    container.innerHTML = '<p class="text-muted">Loading balances...</p>';
    
    // Fetch balances for all students (selected student + siblings)
    const allStudents = [selectedShareStudent, ...selectedShareSiblings];
    const balancePromises = allStudents.map(async (student) => {
        try {
            const url = `{{ route('finance.bank-statements.student-balance', ['student' => ':id']) }}`.replace(':id', student.id) + 
                       (isSwimmingTransaction ? '?swimming=1' : '');
            const response = await fetch(url);
            const data = await response.json();
            return {
                student: student,
                balance: data.balance || 0,
                label: data.label || 'Balance'
            };
        } catch (error) {
            console.error('Error fetching balance for student:', student.id, error);
            return {
                student: student,
                balance: 0,
                label: isSwimmingTransaction ? 'Swimming Balance' : 'Balance'
            };
        }
    });
    
    const studentsWithBalances = await Promise.all(balancePromises);
    
    // Build form with selected student and siblings
    let html = '';
    let index = 0;
    
    // Add selected student and siblings with balances
    studentsWithBalances.forEach(({student, balance, label}) => {
        html += `
            <div class="mb-3 p-3 border rounded">
                <label class="form-label">
                    <strong>${student.full_name || ''}</strong>
                    <small class="text-muted">(${student.admission_number || 'N/A'})</small>
                    <br><small class="text-danger">${label}: Ksh ${parseFloat(balance || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</small>
                </label>
                <input type="hidden" name="allocations[${index}][student_id]" value="${student.id}">
                <input type="number" 
                       name="allocations[${index}][amount]" 
                       class="form-control share-sibling-amount" 
                       step="0.01" 
                       min="0" 
                       max="${transactionAmount}"
                       onchange="updateShareTotal()"
                       oninput="updateShareTotal()"
                       value=""
                       placeholder="0.00 (leave 0 to exclude)">
                <small class="text-muted">Enter 0.00 or leave empty to exclude this student</small>
            </div>
        `;
        index++;
    });
    
    container.innerHTML = html;
    formContainer.style.display = 'block';
    updateShareTotal();
}

function updateShareTotal() {
    const amounts = Array.from(document.querySelectorAll('.share-sibling-amount')).map(input => parseFloat(input.value) || 0);
    const total = amounts.reduce((sum, amt) => sum + amt, 0);
    const transactionAmount = {{ $bankStatement->amount }};
    const remaining = transactionAmount - total;
    
    document.getElementById('shareRemainingAmount').textContent = 'Ksh ' + remaining.toFixed(2);
    
    const shareBtn = document.getElementById('shareSubmitBtn');
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

// Old displaySearchResults function removed - using student_live_search partial now

async function showShareModalForStudent(studentId, studentName, siblings) {
    // Ensure siblings is an array
    if (!Array.isArray(siblings)) {
        siblings = [];
    }
    
    // Fetch balances for main student and siblings
    const allStudents = [
        {id: studentId, name: studentName},
        ...siblings.map(s => ({id: s.id, name: s.full_name || '', admission: s.admission_number}))
    ];
    
    const balancePromises = allStudents.map(async (student) => {
        try {
            const url = `{{ route('finance.bank-statements.student-balance', ['student' => ':id']) }}`.replace(':id', student.id) + 
                       (isSwimmingTransaction ? '?swimming=1' : '');
            const response = await fetch(url);
            const data = await response.json();
            return {
                ...student,
                balance: data.balance || 0,
                label: data.label || 'Balance'
            };
        } catch (error) {
            return {
                ...student,
                balance: 0,
                label: isSwimmingTransaction ? 'Swimming Balance' : 'Balance'
            };
        }
    });
    
    const studentsWithBalances = await Promise.all(balancePromises);
    
    // Create modal for sharing payment with siblings
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = 'shareModal' + studentId;
    
    const mainStudent = studentsWithBalances[0];
    const siblingsWithBalances = studentsWithBalances.slice(1);
    
    modal.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Share Payment with Siblings</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('finance.bank-statements.share', $bankStatement->id) }}">
                    @csrf
                    <div class="modal-body">
                        <p class="text-muted">Total amount: <strong>Ksh {{ number_format($bankStatement->amount, 2) }}</strong></p>
                        <p class="text-info mb-3">
                            <i class="bi bi-info-circle"></i> Allocate the payment amount among the siblings below. You can share with 1, 2, or 3 siblings (not all). Enter 0.00 for siblings you don't want to include.
                        </p>
                        <div class="mb-3 p-3 border rounded">
                            <label class="form-label">
                                <strong>${mainStudent.name}</strong>
                                <br><small class="text-danger">${mainStudent.label}: Ksh ${parseFloat(mainStudent.balance || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</small>
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
                        ${siblingsWithBalances.map((sib, idx) => {
                            const sibAdmission = sib.admission || '';
                            return `
                            <div class="mb-3 p-3 border rounded">
                                <label class="form-label">
                                    <strong>${sib.name}</strong>
                                    ${sibAdmission ? `<small class="text-muted">(${sibAdmission})</small>` : ''}
                                    <br><small class="text-danger">${sib.label}: Ksh ${parseFloat(sib.balance || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</small>
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
                            `;
                        }).join('')}
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

    const firstInput = modal.querySelector('.modal-sibling-amount');
    if (firstInput) {
        firstInput.value = {{ $bankStatement->amount }};
        updateModalTotal();
    }
    
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

