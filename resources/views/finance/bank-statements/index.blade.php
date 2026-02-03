@extends('layouts.app')

@push('styles')
<style>
    /* Fix for sticky header overlapping transactions table in bank statements view */
    /* The sticky .app-header (position: sticky, top: 0, z-index: 950) was covering the transactions table */
    
    /* Ensure the transactions table section is properly positioned and visible */
    /* Using ID selector ensures this only affects this specific view */
    #bank-statements-transactions-section {
        position: relative;
        z-index: 1;
        margin-top: 0;
        scroll-margin-top: 100px; /* Add scroll margin to account for sticky header when scrolling to table */
    }
</style>
@endpush

@section('content')
    @include('finance.partials.header', [
        'title' => 'Bank Statements & Transactions',
        'icon' => 'bi bi-bank',
        'subtitle' => 'View statements, transactions, and upload new statements',
        'actions' => '<a href="' . route('finance.bank-statements.statements') . '" class="btn btn-finance btn-finance-info"><i class="bi bi-folder2-open"></i> View Imported Statements</a>
                      <a href="' . route('finance.bank-statements.create') . '" class="btn btn-finance btn-finance-primary"><i class="bi bi-upload"></i> Upload Statement</a>'
    ])

    @include('finance.invoices.partials.alerts')
    
    @if(session('receipt_ids'))
        <script>
            window.addEventListener('load', function() {
                const receiptIds = @json(session('receipt_ids'));
                if (!Array.isArray(receiptIds) || receiptIds.length === 0) {
                    return;
                }
                
                let receiptUrl = '';
                if (receiptIds.length === 1) {
                    receiptUrl = '{{ route("finance.payments.receipt.view", ":id") }}'.replace(':id', receiptIds[0]);
                } else {
                    const params = new URLSearchParams();
                    params.set('payment_ids', receiptIds.join(','));
                    receiptUrl = '{{ route("finance.payments.bulk-print") }}' + '?' + params.toString();
                }
                
                const popup = window.open(
                    receiptUrl,
                    'ReceiptWindow',
                    'width=800,height=900,scrollbars=yes,resizable=yes,toolbar=no,menubar=no,location=no,status=no'
                );
                
                if (!popup || popup.closed || typeof popup.closed == 'undefined') {
                    alert('Popup blocked. Please allow popups for this site to view receipts automatically.');
                    window.open(receiptUrl, '_blank');
                } else {
                    popup.focus();
                }
            });
        </script>
    @endif

    <!-- Statement Filter Notice -->
    @if(request('statement_file'))
        <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-info-circle"></i> 
            <strong>Filtered by Statement:</strong> Showing transactions from a specific uploaded statement file.
            <a href="{{ route('finance.bank-statements.index') }}" class="btn btn-sm btn-outline-info ms-2">
                <i class="bi bi-x-circle"></i> Clear Filter
            </a>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Summary Card - Only show for 'all', 'swimming', and 'archived' views -->
    @if(in_array($view ?? 'all', ['all', 'swimming', 'archived']) && isset($totalAmount))
    <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="mb-0">
                        @if(($view ?? 'all') === 'archived')
                            Total Archived Amount (Money IN Only)
                        @elseif(($view ?? 'all') === 'swimming')
                            Total Swimming Amount
                        @else
                            Total Parsed Amount
                        @endif
                    </h5>
                    <p class="text-muted mb-0">Total for {{ $totalCount ?? 0 }} transaction(s)</p>
                </div>
                <div class="col-md-6 text-end">
                    <h3 class="mb-0 text-success">Ksh {{ number_format($totalAmount ?? 0, 2) }}</h3>
                    <small class="text-muted">
                        @if(($view ?? 'all') === 'archived')
                            Credit transactions only
                        @elseif(($view ?? 'all') === 'swimming')
                            Swimming transactions
                        @else
                            Compare with statement total
                        @endif
                    </small>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- View Tabs -->
    <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mb-4">
        <div class="card-body p-0">
            <ul class="nav nav-tabs nav-tabs-finance" role="tablist">
                <li class="nav-item">
                    <a class="nav-link {{ ($view ?? 'all') == 'all' ? 'active' : '' }}" href="{{ route('finance.bank-statements.index', ['view' => 'all'] + request()->except('view')) }}">
                        All <span class="badge bg-secondary">{{ $counts['all'] ?? 0 }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ ($view ?? 'all') == 'auto-assigned' ? 'active' : '' }}" href="{{ route('finance.bank-statements.index', ['view' => 'auto-assigned'] + request()->except('view')) }}">
                        Auto Assigned <span class="badge bg-success">{{ $counts['auto-assigned'] ?? 0 }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ ($view ?? 'all') == 'manual-assigned' ? 'active' : '' }}" href="{{ route('finance.bank-statements.index', ['view' => 'manual-assigned'] + request()->except('view')) }}">
                        Manual Assigned <span class="badge bg-info">{{ $counts['manual-assigned'] ?? 0 }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ ($view ?? 'all') == 'draft' ? 'active' : '' }}" href="{{ route('finance.bank-statements.index', ['view' => 'draft'] + request()->except('view')) }}">
                        Draft <span class="badge bg-warning">{{ $counts['draft'] ?? 0 }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ ($view ?? 'all') == 'unassigned' ? 'active' : '' }}" href="{{ route('finance.bank-statements.index', ['view' => 'unassigned'] + request()->except('view')) }}">
                        Unassigned <span class="badge bg-secondary">{{ $counts['unassigned'] ?? 0 }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ ($view ?? 'all') == 'confirmed' ? 'active' : '' }}" href="{{ route('finance.bank-statements.index', ['view' => 'confirmed'] + request()->except('view')) }}">
                        Confirmed <span class="badge bg-primary">{{ $counts['confirmed'] ?? 0 }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ ($view ?? 'all') == 'collected' ? 'active' : '' }}" href="{{ route('finance.bank-statements.index', ['view' => 'collected'] + request()->except('view')) }}">
                        Collected <span class="badge bg-success">{{ $counts['collected'] ?? 0 }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ ($view ?? 'all') == 'duplicate' ? 'active' : '' }}" href="{{ route('finance.bank-statements.index', ['view' => 'duplicate'] + request()->except('view')) }}">
                        Duplicate <span class="badge bg-danger">{{ $counts['duplicate'] ?? 0 }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ ($view ?? 'all') == 'archived' ? 'active' : '' }}" href="{{ route('finance.bank-statements.index', ['view' => 'archived'] + request()->except('view')) }}">
                        Archived <span class="badge bg-secondary">{{ $counts['archived'] ?? 0 }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ ($view ?? 'all') == 'swimming' ? 'active' : '' }}" href="{{ route('finance.bank-statements.index', ['view' => 'swimming'] + request()->except('view')) }}">
                        <i class="bi bi-water"></i> Swimming <span class="badge bg-info">{{ $counts['swimming'] ?? 0 }}</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Filters -->
    <div class="finance-filter-card finance-animate shadow-sm rounded-4 border-0 mb-4">
        <form method="GET" action="{{ route('finance.bank-statements.index') }}" class="row g-3">
            <div class="col-md-3">
                <label class="finance-form-label">Status</label>
                <select name="status" class="finance-form-select">
                    <option value="">All Statuses</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="finance-form-label">Match Status</label>
                <select name="match_status" class="finance-form-select">
                    <option value="">All</option>
                    <option value="matched" {{ request('match_status') == 'matched' ? 'selected' : '' }}>Matched</option>
                    <option value="unmatched" {{ request('match_status') == 'unmatched' ? 'selected' : '' }}>Unmatched</option>
                    <option value="multiple_matches" {{ request('match_status') == 'multiple_matches' ? 'selected' : '' }}>Multiple Matches</option>
                    <option value="manual" {{ request('match_status') == 'manual' ? 'selected' : '' }}>Manual</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="finance-form-label">Swimming Transaction</label>
                <select name="is_swimming" class="finance-form-select">
                    <option value="">All Transactions</option>
                    <option value="1" {{ request('is_swimming') == '1' ? 'selected' : '' }}>Swimming Only</option>
                    <option value="0" {{ request('is_swimming') == '0' ? 'selected' : '' }}>Non-Swimming Only</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="finance-form-label">Bank Account</label>
                <select name="bank_account_id" class="finance-form-select">
                    <option value="">All Accounts</option>
                    @foreach($bankAccounts as $account)
                        <option value="{{ $account->id }}" {{ request('bank_account_id') == $account->id ? 'selected' : '' }}>
                            {{ $account->name }} ({{ $account->account_number }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="finance-form-label">Search</label>
                <input type="text" name="search" class="finance-form-control" placeholder="Description, reference, phone..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <label class="finance-form-label">From Date</label>
                <input type="date" name="date_from" class="finance-form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-3">
                <label class="finance-form-label">To Date</label>
                <input type="date" name="date_to" class="finance-form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-finance btn-finance-primary w-100">
                    <i class="bi bi-search"></i> Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Bulk Actions -->
    <div class="d-flex justify-content-between align-items-center mb-3 gap-2" id="bulkActionsContainer">
        <div class="d-flex gap-2">
            <form id="bulkConfirmForm" method="POST" action="{{ route('finance.bank-statements.bulk-confirm') }}">
                @csrf
                <div id="bulkTransactionIdsContainer"></div>
                <button type="button" class="btn btn-finance btn-finance-success" onclick="bulkConfirm()" id="bulkConfirmBtn" style="display: none;">
                    <i class="bi bi-check-circle"></i> Confirm Selected
                </button>
            </form>
            
            <form id="bulkSwimmingForm" method="POST" action="{{ route('finance.bank-statements.bulk-mark-swimming') }}">
                @csrf
                <div id="bulkSwimmingTransactionIdsContainer"></div>
                <button type="button" class="btn btn-finance btn-finance-info" onclick="bulkMarkSwimming()" id="bulkSwimmingBtn" style="display: none;">
                    <i class="bi bi-water"></i> Mark as Swimming (Draft/Confirmed)
                </button>
            </form>
            
            <form id="bulkTransferToSwimmingForm" method="POST" action="{{ route('finance.bank-statements.bulk-transfer-to-swimming') }}">
                @csrf
                <div id="bulkTransferToSwimmingTransactionIdsContainer"></div>
                <button type="button" class="btn btn-finance btn-finance-warning" onclick="bulkTransferToSwimming()" id="bulkTransferToSwimmingBtn" style="display: none;">
                    <i class="bi bi-arrow-right-circle"></i> Transfer to Swimming
                </button>
            </form>
            
            <form id="bulkTransferFromSwimmingForm" method="POST" action="{{ route('finance.bank-statements.bulk-transfer-from-swimming') }}">
                @csrf
                <div id="bulkTransferFromSwimmingTransactionIdsContainer"></div>
                <button type="button" class="btn btn-finance btn-finance-success" onclick="bulkTransferFromSwimming()" id="bulkTransferFromSwimmingBtn" style="display: none;">
                    <i class="bi bi-arrow-left-circle"></i> Transfer from Swimming
                </button>
            </form>
            
            <form id="reprocessSwimmingForm" method="POST" action="{{ route('finance.bank-statements.reprocess-swimming') }}" onsubmit="return confirm('Reprocess all confirmed swimming transactions that haven\'t been allocated yet? This will credit student wallets.');">
                @csrf
                <button type="submit" class="btn btn-finance btn-finance-info" title="Reprocess confirmed swimming transactions to credit wallets">
                    <i class="bi bi-arrow-clockwise"></i> Reprocess Swimming Transactions
                </button>
            </form>
            
            @if(request('view') == 'unassigned' || (!request('view') || request('view') == 'all'))
            <form id="bulkArchiveForm" method="POST" action="{{ route('finance.bank-statements.bulk-archive') }}">
                @csrf
                <div id="bulkArchiveTransactionIdsContainer"></div>
                <button type="button" class="btn btn-finance btn-finance-secondary" onclick="bulkArchive()" id="bulkArchiveBtn" style="display: none;">
                    <i class="bi bi-archive"></i> Archive Selected Unmatched
                </button>
            </form>
            @endif
        </div>
        
        <div class="d-flex gap-2">
            <form id="autoAssignForm" method="POST" action="{{ route('finance.bank-statements.auto-assign') }}">
                @csrf
                <div id="autoAssignTransactionIdsContainer"></div>
                <button type="button" class="btn btn-finance btn-finance-primary" onclick="autoAssign()" id="autoAssignBtn">
                    <i class="bi bi-magic"></i> Auto-Assign (Create Payments for Confirmed)
                </button>
            </form>
            <form method="POST" action="{{ route('finance.bank-statements.reconcile-payments') }}" onsubmit="return confirm('Reconcile payment links for all bank statement transactions?');">
                @csrf
                <button type="submit" class="btn btn-finance btn-finance-secondary">
                    <i class="bi bi-arrow-repeat"></i> Reconcile Payments
                </button>
            </form>
        </div>
    </div>

    <!-- Transactions Table -->
    <div id="bank-statements-transactions-section" class="finance-table-wrapper finance-animate shadow-sm rounded-4 border-0">
        <div class="card-header d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0"><i class="bi bi-table me-2"></i>Transactions</h5>
            <button type="button" class="btn btn-sm btn-finance btn-finance-outline" id="refreshBtn" onclick="refreshTransactions()">
                <i class="bi bi-arrow-clockwise" id="refreshIcon"></i> Refresh Now
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover finance-table">
                <thead>
                    <tr>
                        <th width="40">
                            @if(in_array(request('view'), ['draft', 'auto-assigned', 'manual-assigned', 'confirmed', 'collected', 'unassigned', 'all', 'swimming']) || !request('view'))
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </th>
                        <th>Date</th>
                        <th>
                            <div class="d-flex align-items-center gap-2">
                                <span>Amount</span>
                                @php
                                    $currentSort = request('sort', 'date');
                                    $sortParams = request()->except(['sort', 'page']);
                                @endphp
                                <div class="btn-group btn-group-sm" role="group" style="font-size: 0.7rem;">
                                    <a href="{{ route('finance.bank-statements.index', array_merge($sortParams, ['sort' => 'date'])) }}" 
                                       class="btn btn-sm {{ $currentSort === 'date' || $currentSort === '' ? 'btn-primary' : 'btn-outline-secondary' }}"
                                       title="Sort by Date (Newest First)"
                                       style="padding: 0.2rem 0.5rem;">
                                        <i class="bi bi-calendar-event"></i>
                                    </a>
                                    <a href="{{ route('finance.bank-statements.index', array_merge($sortParams, ['sort' => 'amount_desc'])) }}" 
                                       class="btn btn-sm {{ $currentSort === 'amount_desc' ? 'btn-primary' : 'btn-outline-secondary' }}"
                                       title="Sort by Amount (Highest to Lowest)"
                                       style="padding: 0.2rem 0.5rem;">
                                        <i class="bi bi-sort-down"></i>
                                    </a>
                                    <a href="{{ route('finance.bank-statements.index', array_merge($sortParams, ['sort' => 'amount_asc'])) }}" 
                                       class="btn btn-sm {{ $currentSort === 'amount_asc' ? 'btn-primary' : 'btn-outline-secondary' }}"
                                       title="Sort by Amount (Lowest to Highest)"
                                       style="padding: 0.2rem 0.5rem;">
                                        <i class="bi bi-sort-up"></i>
                                    </a>
                                </div>
                            </div>
                        </th>
                        <th>Description</th>
                        <th>Reference</th>
                        <th>Phone</th>
                        <th>Student</th>
                        <th>Match Status</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        @php
                            // Detect transaction type
                            $isC2B = $transaction instanceof \App\Models\MpesaC2BTransaction;
                            $isBank = $transaction instanceof \App\Models\BankStatementTransaction;
                            
                            // Normalize fields for both types
                            $txnDate = $isC2B ? ($transaction->trans_time ?? $transaction->created_at) : ($transaction->transaction_date ?? $transaction->created_at);
                            $txnAmount = $isC2B ? $transaction->trans_amount : $transaction->amount;
                            $txnDescription = $isC2B ? ($transaction->bill_ref_number ?? 'M-PESA Payment') : $transaction->description;
                            $txnReference = $isC2B ? $transaction->trans_id : ($transaction->reference_number ?? 'N/A');
                            $txnPhone = $isC2B ? ($transaction->formatted_phone ?? $transaction->msisdn) : $transaction->phone_number;
                            $txnStatus = $isC2B ? ($transaction->status === 'processed' ? 'confirmed' : ($transaction->status === 'failed' ? 'rejected' : 'draft')) : $transaction->status;
                            $txnMatchStatus = $isC2B ? ($transaction->allocation_status === 'auto_matched' ? 'matched' : ($transaction->allocation_status === 'manually_allocated' ? 'manual' : 'unmatched')) : $transaction->match_status;
                            $txnMatchConfidence = $isC2B ? ($transaction->match_confidence ?? 0) : ($transaction->match_confidence ?? 0);
                            $txnIsDuplicate = $isC2B ? $transaction->is_duplicate : $transaction->is_duplicate;
                            $txnIsArchived = $isC2B ? false : ($transaction->is_archived ?? false);
                            $txnPaymentCreated = $isC2B ? ($transaction->payment_id !== null) : ($transaction->payment_created ?? false);
                            $txnIsSwimming = $isC2B ? ($transaction->is_swimming_transaction ?? false) : ($transaction->is_swimming_transaction ?? false);
                            $txnStudentId = $transaction->student_id;
                            $txnIsShared = $isC2B ? false : ($transaction->is_shared ?? false);
                            $txnSharedAllocations = $isC2B ? [] : ($transaction->shared_allocations ?? []);
                            $txnAllocationStatus = $isC2B ? ($transaction->allocation_status ?? 'unallocated') : null;
                            $activeTotal = (float) ($transaction->active_payment_total ?? 0);
                            $isFullyCollected = $txnStatus === 'confirmed' && $activeTotal >= ($txnAmount - 0.01);
                            $isPartiallyCollected = $txnStatus === 'confirmed' && $activeTotal > 0.01 && $activeTotal < ($txnAmount - 0.01);
                            
                            // Permission checks
                            $canConfirm = $txnStatus === 'draft' 
                                && !$txnIsDuplicate 
                                && !$txnIsArchived
                                && ($txnStudentId || $txnIsShared);
                            $canArchive = $txnMatchStatus === 'unmatched'
                                && !$txnIsArchived
                                && !$txnIsDuplicate
                                && !$txnStudentId
                                && !$isC2B; // C2B transactions can't be archived
                            // For C2B, check if swimming column exists
                            $c2bCanSwim = $isC2B ? \Illuminate\Support\Facades\Schema::hasColumn('mpesa_c2b_transactions', 'is_swimming_transaction') : true;
                            $txnAllocationStatus = $isC2B ? ($transaction->allocation_status ?? 'unallocated') : null;
                            
                            $canTransferToSwimming = $txnStatus === 'confirmed' 
                                && $txnPaymentCreated 
                                && !$txnIsSwimming
                                && !$txnIsDuplicate
                                && !$txnIsArchived
                                && ($txnStudentId || $txnIsShared)
                                && $c2bCanSwim;
                            $canTransferFromSwimming = $txnStatus === 'confirmed' 
                                && $txnIsSwimming
                                && !$txnIsDuplicate
                                && !$txnIsArchived
                                && ($txnStudentId || $txnIsShared)
                                && $c2bCanSwim;
                            // Cannot mark as swimming if transaction has a linked fee payment (same as: fee payments cannot be used for swimming)
                            $hasLinkedFeePayment = false;
                            if ($transaction->payment_id) {
                                $linkedPayment = $transaction->payment ?? \App\Models\Payment::find($transaction->payment_id);
                                $hasLinkedFeePayment = $linkedPayment && !$linkedPayment->reversed;
                            } else {
                                $hasLinkedFeePayment = $txnPaymentCreated;
                            }
                            // For C2B, allow marking if has student_id or is unallocated; for bank, allow if has student/shared or unmatched
                            $c2bCanMark = $isC2B ? ($txnStudentId || $txnAllocationStatus === 'unallocated') : true;
                            $bankCanMark = !$isC2B ? ($txnStudentId || $txnIsShared || $txnMatchStatus === 'unmatched' || $txnMatchStatus === 'multiple_matches') : true;
                            $canMarkAsSwimming = (($txnStatus === 'draft' || $txnStatus === 'confirmed') || ($isC2B && in_array($txnAllocationStatus, ['unallocated', 'auto_matched', 'manually_allocated'])))
                                && !$txnIsSwimming
                                && !$txnIsDuplicate
                                && !$txnIsArchived
                                && !$hasLinkedFeePayment
                                && (($isC2B && $c2bCanMark) || (!$isC2B && $bankCanMark))
                                && $c2bCanSwim;
                            $canSelectDraftUnmatched = $txnStatus === 'draft'
                                && ($txnMatchStatus === 'unmatched' || $txnMatchStatus === 'multiple_matches')
                                && !$txnIsDuplicate
                                && !$txnIsArchived
                                && !$txnIsSwimming;
                        @endphp
                        <tr>
                            <td>
                                @if($canConfirm || $canArchive || $canTransferToSwimming || $canTransferFromSwimming || $canMarkAsSwimming || $canSelectDraftUnmatched)
                                    <input type="checkbox" class="transaction-checkbox" value="{{ $transaction->id }}" data-txn-type="{{ $isC2B ? 'c2b' : 'bank' }}" onchange="updateBulkIds()" data-can-confirm="{{ $canConfirm ? '1' : '0' }}" data-can-archive="{{ $canArchive ? '1' : '0' }}" data-can-transfer-swimming="{{ $canTransferToSwimming ? '1' : '0' }}" data-can-transfer-from-swimming="{{ $canTransferFromSwimming ? '1' : '0' }}" data-can-mark-swimming="{{ ($canMarkAsSwimming || $canSelectDraftUnmatched) ? '1' : '0' }}">
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($isC2B)
                                    <span class="badge bg-success mb-1 d-block" style="font-size: 0.7rem;">C2B</span>
                                @endif
                                {{ $txnDate instanceof \Carbon\Carbon ? $txnDate->format('d M Y') : \Carbon\Carbon::parse($txnDate)->format('d M Y') }}
                                @if($isC2B && $transaction->trans_time)
                                    <br><small class="text-muted">{{ $transaction->trans_time->format('h:i A') }}</small>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <strong class="text-success">
                                        +Ksh {{ number_format($txnAmount, 2) }}
                                    </strong>
                                    @if($txnIsSwimming)
                                        <span class="badge bg-info" title="Swimming Transaction">
                                            <i class="bi bi-water"></i>
                                        </span>
                                    @endif
                                    @if($isC2B)
                                        <span class="badge bg-primary" title="Real-time M-PESA Transaction">
                                            <i class="bi bi-broadcast"></i>
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="text-break" style="max-width: 300px; word-wrap: break-word; white-space: pre-wrap;" title="{{ $txnDescription }}">
                                    {{ $txnDescription }}
                                    @if($isC2B && $transaction->first_name)
                                        <br><small class="text-muted">Payer: {{ $transaction->full_name }}</small>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <code>{{ $txnReference }}</code>
                                @if($isC2B && $transaction->bill_ref_number && $transaction->bill_ref_number !== $transaction->trans_id)
                                    <br><small class="text-muted">Ref: {{ $transaction->bill_ref_number }}</small>
                                @endif
                            </td>
                            <td>{{ $txnPhone ?? 'N/A' }}</td>
                            <td>
                                @if($txnIsDuplicate)
                                    <span class="text-danger">
                                        <i class="bi bi-exclamation-triangle"></i> Duplicate
                                        @if(!$isC2B && $transaction->duplicateOfPayment)
                                            <br><small>Payment: {{ $transaction->duplicateOfPayment->receipt_number ?? $transaction->duplicateOfPayment->transaction_code }}</small>
                                        @endif
                                    </span>
                                @elseif($txnIsShared && !empty($txnSharedAllocations))
                                    <div class="text-primary">
                                        <i class="bi bi-people"></i> <strong>Shared Payment</strong>
                                        <br><small class="text-info">({{ count($txnSharedAllocations) }} sibling{{ count($txnSharedAllocations) === 1 ? '' : 's' }})</small>
                                    </div>
                                    @foreach($txnSharedAllocations as $allocation)
                                        @php $student = \App\Models\Student::find($allocation['student_id']); @endphp
                                        @if($student)
                                            <div class="mt-1">
                                                <a href="{{ route('students.show', $student) }}" class="text-decoration-none">
                                                    {{ $student->full_name }}
                                                    <br><small class="text-muted">{{ $student->admission_number }}</small>
                                                </a>
                                                <br><small class="text-success fw-bold">Ksh {{ number_format($allocation['amount'], 2) }}</small>
                                            </div>
                                        @endif
                                    @endforeach
                                @elseif($txnStudentId)
                                    @php
                                        $student = $transaction->student;
                                        $siblings = [];
                                        if ($student && $student->family_id) {
                                            $siblings = \App\Models\Student::where('family_id', $student->family_id)
                                                ->where('id', '!=', $student->id)
                                                ->where('is_alumni', false)
                                                ->where('archive', false)
                                                ->get();
                                        }
                                    @endphp
                                    @if($student)
                                        <a href="{{ route('students.show', $student) }}">
                                            {{ $student->full_name }}
                                            <br><small class="text-muted">{{ $student->admission_number }}</small>
                                        </a>
                                        @if(count($siblings) > 0 && !$txnIsShared)
                                            <br><small class="text-info">
                                                <i class="bi bi-people"></i> {{ count($siblings) }} sibling{{ count($siblings) === 1 ? '' : 's' }} available
                                            </small>
                                        @endif
                                    @else
                                        <span class="text-muted">
                                            Student #{{ $txnStudentId }}
                                            <br><small>(Archived/Alumni)</small>
                                        </span>
                                    @endif
                                @else
                                    <span class="text-muted">Unmatched</span>
                                @endif
                                @if(!$isC2B && $transaction->payer_name)
                                    <br><small class="text-info">Payer: {{ $transaction->payer_name }}</small>
                                @endif
                            </td>
                            <td>
                                @if($txnMatchStatus == 'matched')
                                    <span class="badge bg-success">Matched</span>
                                    @if($txnMatchConfidence > 0)
                                        <br><small class="text-muted">{{ round($txnMatchConfidence * 100) }}%</small>
                                    @endif
                                @elseif($txnMatchStatus == 'multiple_matches')
                                    <span class="badge bg-warning">Multiple</span>
                                @elseif($txnMatchStatus == 'manual')
                                    <span class="badge bg-info">Manual</span>
                                @else
                                    <span class="badge bg-secondary">Unmatched</span>
                                @endif
                            </td>
                            <td>
                                @if($txnIsArchived)
                                    <span class="badge bg-secondary">Archived</span>
                                @elseif($txnIsDuplicate)
                                    <span class="badge bg-danger">Duplicate</span>
                                @elseif($isFullyCollected)
                                    <span class="badge bg-success">Collected</span>
                                @elseif($isPartiallyCollected)
                                    <span class="badge bg-warning text-dark">Partially Collected</span>
                                @elseif($txnStatus == 'confirmed')
                                    <span class="badge bg-primary">Confirmed</span>
                                @elseif($txnStatus == 'rejected')
                                    <span class="badge bg-danger">Rejected</span>
                                @elseif($txnMatchStatus == 'matched' && ($txnStudentId || $txnIsShared))
                                    <span class="badge bg-success">Auto Assigned</span>
                                @elseif($txnMatchStatus == 'manual' && ($txnStudentId || $txnIsShared))
                                    <span class="badge bg-info">Manual Assigned</span>
                                @else
                                    <span class="badge bg-warning">Draft</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    @if($isC2B)
                                        <a href="{{ route('finance.bank-statements.show', $transaction->id) }}?type=c2b" class="btn btn-finance btn-finance-secondary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if($txnMatchStatus === 'unmatched' || !$txnStudentId)
                                            <a href="{{ route('finance.bank-statements.show', $transaction->id) }}?type=c2b" class="btn btn-finance btn-finance-primary" title="Allocate">
                                                <i class="bi bi-person-plus"></i>
                                            </a>
                                        @endif
                                        @if($txnIsSwimming && $txnStatus !== 'rejected')
                                            <form method="POST" action="{{ route('finance.bank-statements.unmark-swimming', $transaction->id) }}?type=c2b" class="d-inline" onsubmit="return confirm('Revert this transaction from swimming? It will be treated as a regular fee payment again.')">
                                                @csrf
                                                <button type="submit" class="btn btn-finance btn-finance-warning btn-sm" title="Revert to regular payments (unmark as swimming)">
                                                    <i class="bi bi-arrow-return-left"></i> Revert
                                                </button>
                                            </form>
                                        @endif
                                    @else
                                        <a href="{{ route('finance.bank-statements.show', $transaction->id) }}?type=bank" class="btn btn-finance btn-finance-secondary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if($transaction->isDraft() && !$transaction->is_duplicate && !$transaction->is_archived)
                                            <a href="{{ route('finance.bank-statements.edit', $transaction) }}" class="btn btn-finance btn-finance-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        @endif
                                        @if($transaction->statement_file_path)
                                            <a href="{{ route('finance.bank-statements.view-pdf', $transaction) }}" target="_blank" class="btn btn-finance btn-finance-info" title="View PDF">
                                                <i class="bi bi-file-pdf"></i>
                                            </a>
                                        @endif
                                        @if($txnIsSwimming && $txnStatus !== 'rejected')
                                        @php
                                            // Check if allocations exist (if so, cannot unmark)
                                            $hasAllocations = false;
                                            if (\Illuminate\Support\Facades\Schema::hasTable('swimming_transaction_allocations')) {
                                                $hasAllocations = \App\Models\SwimmingTransactionAllocation::where('bank_statement_transaction_id', $transaction->id)
                                                    ->where('status', '!=', \App\Models\SwimmingTransactionAllocation::STATUS_REVERSED)
                                                    ->exists();
                                            }
                                        @endphp
                                        @if(!$hasAllocations)
                                            <form method="POST" action="{{ route('finance.bank-statements.unmark-swimming', $transaction) }}" class="d-inline" onsubmit="return confirm('Revert this transaction from swimming? It will be treated as a regular fee payment again.')">
                                                @csrf
                                                <button type="submit" class="btn btn-finance btn-finance-warning btn-sm" title="Revert to regular payments (unmark as swimming)">
                                                    <i class="bi bi-arrow-return-left"></i> Revert
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                    @endif
                                    @if(!$isC2B && $txnStatus !== 'rejected' && !$txnIsArchived)
                                        <form method="POST" action="{{ route('finance.bank-statements.reject', $transaction) }}" class="d-inline" onsubmit="return confirm('Reject and reset to unassigned? Any associated payment(s) will be reversed; matching, confirmation, and sibling/shared allocations will be cleared. You must then manually match, allocate, confirm, and create payment. Continue?');">
                                            @csrf
                                            <button type="submit" class="btn btn-finance btn-finance-danger btn-sm" title="Reject">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        </form>
                                    @endif
                                    @if(!$isC2B && !$txnIsArchived)
                                        <form method="POST" action="{{ route('finance.bank-statements.archive', $transaction) }}" class="d-inline" onsubmit="return confirm('Archive this transaction?')">
                                            @csrf
                                            <button type="submit" class="btn btn-finance btn-finance-secondary" title="Archive">
                                                <i class="bi bi-archive"></i>
                                            </button>
                                        </form>
                                    @elseif(!$isC2B && $txnIsArchived)
                                        <form method="POST" action="{{ route('finance.bank-statements.unarchive', $transaction) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-finance btn-finance-success" title="Unarchive">
                                                <i class="bi bi-archive-fill"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox"></i> No transactions found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="finance-table-footer">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-2">
                    <label for="perPageSelect" class="mb-0 text-muted small">Show:</label>
                    <select id="perPageSelect" class="form-select form-select-sm" style="width: auto;" onchange="changePerPage(this.value)">
                        @foreach($perPageOptions ?? [20, 50, 100, 200] as $option)
                            <option value="{{ $option }}" {{ ($currentPerPage ?? 25) == $option ? 'selected' : '' }}>
                                {{ $option }}
                            </option>
                        @endforeach
                    </select>
                    <span class="text-muted small">per page</span>
                </div>
                <div>
                    {{ $transactions->links() }}
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function changePerPage(perPage) {
            const url = new URL(window.location.href);
            url.searchParams.set('per_page', perPage);
            url.searchParams.set('page', '1'); // Reset to first page when changing per page
            window.location.href = url.toString();
        }
    </script>

    <script>
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            if (!selectAll) return;
            
            const checkboxes = document.querySelectorAll('.transaction-checkbox');
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateBulkIds();
        }

        function updateBulkIds() {
            const checked = Array.from(document.querySelectorAll('.transaction-checkbox:checked'));
            const checkedIds = checked.map(cb => parseInt(cb.value));
            const bulkIdsContainer = document.getElementById('bulkTransactionIdsContainer');
            const autoAssignIdsContainer = document.getElementById('autoAssignTransactionIdsContainer');
            const bulkArchiveIdsContainer = document.getElementById('bulkArchiveTransactionIdsContainer');
            const bulkSwimmingIdsContainer = document.getElementById('bulkSwimmingTransactionIdsContainer');
            const bulkConfirmBtn = document.getElementById('bulkConfirmBtn');
            const bulkArchiveBtn = document.getElementById('bulkArchiveBtn');
            const bulkActionsContainer = document.getElementById('bulkActionsContainer');
            
            // Clear existing hidden inputs
            bulkIdsContainer.innerHTML = '';
            autoAssignIdsContainer.innerHTML = '';
            if (bulkArchiveIdsContainer) {
                bulkArchiveIdsContainer.innerHTML = '';
            }
            if (bulkSwimmingIdsContainer) {
                bulkSwimmingIdsContainer.innerHTML = '';
            }
            
            // Separate transactions that can be confirmed vs archived vs transfer to swimming vs mark as swimming
            const confirmableIds = [];
            const archivableIds = [];
            const transferableToSwimmingIds = [];
            const transferableFromSwimmingIds = [];
            const markableAsSwimmingIds = [];
            
            checked.forEach(cb => {
                const id = parseInt(cb.value);
                const canConfirm = cb.getAttribute('data-can-confirm') === '1';
                const canArchive = cb.getAttribute('data-can-archive') === '1';
                const canTransferToSwimming = cb.getAttribute('data-can-transfer-swimming') === '1';
                const canTransferFromSwimming = cb.getAttribute('data-can-transfer-from-swimming') === '1';
                const canMarkAsSwimming = cb.getAttribute('data-can-mark-swimming') === '1';
                
                if (canConfirm) {
                    confirmableIds.push(id);
                }
                if (canArchive) {
                    archivableIds.push(id);
                }
                if (canTransferToSwimming) {
                    transferableToSwimmingIds.push(id);
                }
                if (canTransferFromSwimming) {
                    transferableFromSwimmingIds.push(id);
                }
                if (canMarkAsSwimming) {
                    markableAsSwimmingIds.push(id);
                }
                
                // Add to auto-assign container (for all checked)
                const autoAssignInput = document.createElement('input');
                autoAssignInput.type = 'hidden';
                autoAssignInput.name = 'transaction_ids[]';
                autoAssignInput.value = id;
                autoAssignIdsContainer.appendChild(autoAssignInput);
            });
            
            // Create hidden inputs for confirmable transactions
            confirmableIds.forEach(id => {
                const bulkInput = document.createElement('input');
                bulkInput.type = 'hidden';
                bulkInput.name = 'transaction_ids[]';
                bulkInput.value = id;
                bulkIdsContainer.appendChild(bulkInput);
            });
            
            // Create hidden inputs for archivable transactions
            if (bulkArchiveIdsContainer) {
                archivableIds.forEach(id => {
                    const archiveInput = document.createElement('input');
                    archiveInput.type = 'hidden';
                    archiveInput.name = 'transaction_ids[]';
                    archiveInput.value = id;
                    bulkArchiveIdsContainer.appendChild(archiveInput);
                });
            }
            
            // Create hidden inputs for swimming transactions (all checked)
            if (bulkSwimmingIdsContainer) {
                checkedIds.forEach(id => {
                    const swimmingInput = document.createElement('input');
                    swimmingInput.type = 'hidden';
                    swimmingInput.name = 'transaction_ids[]';
                    swimmingInput.value = id;
                    bulkSwimmingIdsContainer.appendChild(swimmingInput);
                });
            }
            
            // Show/hide bulk confirm button
            bulkActionsContainer.style.display = 'flex';
            
            if (confirmableIds.length > 0) {
                bulkConfirmBtn.style.display = 'inline-block';
            } else {
                bulkConfirmBtn.style.display = 'none';
            }
            
            // Show/hide bulk archive button
            if (bulkArchiveBtn) {
                if (archivableIds.length > 0) {
                    bulkArchiveBtn.style.display = 'inline-block';
                } else {
                    bulkArchiveBtn.style.display = 'none';
                }
            }
            
            // Show/hide bulk swimming button (for marking as swimming)
            if (bulkSwimmingBtn) {
                if (markableAsSwimmingIds.length > 0) {
                    bulkSwimmingBtn.style.display = 'inline-block';
                } else {
                    bulkSwimmingBtn.style.display = 'none';
                }
            }
            
            // Update bulk swimming form with markable transaction IDs
            if (bulkSwimmingIdsContainer) {
                bulkSwimmingIdsContainer.innerHTML = '';
                markableAsSwimmingIds.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'transaction_ids[]';
                    input.value = id;
                    bulkSwimmingIdsContainer.appendChild(input);
                });
            }
            
            // Show/hide bulk transfer to swimming button
            const bulkTransferToSwimmingBtn = document.getElementById('bulkTransferToSwimmingBtn');
            const bulkTransferToSwimmingIdsContainer = document.getElementById('bulkTransferToSwimmingTransactionIdsContainer');
            if (bulkTransferToSwimmingIdsContainer) {
                bulkTransferToSwimmingIdsContainer.innerHTML = '';
                transferableToSwimmingIds.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'transaction_ids[]';
                    input.value = id;
                    bulkTransferToSwimmingIdsContainer.appendChild(input);
                });
            }
            if (bulkTransferToSwimmingBtn) {
                if (transferableToSwimmingIds.length > 0) {
                    bulkTransferToSwimmingBtn.style.display = 'inline-block';
                } else {
                    bulkTransferToSwimmingBtn.style.display = 'none';
                }
            }
            
            // Show/hide bulk transfer from swimming button
            const bulkTransferFromSwimmingBtn = document.getElementById('bulkTransferFromSwimmingBtn');
            const bulkTransferFromSwimmingIdsContainer = document.getElementById('bulkTransferFromSwimmingTransactionIdsContainer');
            if (bulkTransferFromSwimmingIdsContainer) {
                bulkTransferFromSwimmingIdsContainer.innerHTML = '';
                transferableFromSwimmingIds.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'transaction_ids[]';
                    input.value = id;
                    bulkTransferFromSwimmingIdsContainer.appendChild(input);
                });
            }
            if (bulkTransferFromSwimmingBtn) {
                if (transferableFromSwimmingIds.length > 0) {
                    bulkTransferFromSwimmingBtn.style.display = 'inline-block';
                } else {
                    bulkTransferFromSwimmingBtn.style.display = 'none';
                }
            }
        }
        
        function bulkMarkSwimming() {
            const checked = Array.from(document.querySelectorAll('.transaction-checkbox:checked'))
                .filter(cb => cb.getAttribute('data-can-mark-swimming') === '1')
                .map(cb => parseInt(cb.value));
            
            if (checked.length === 0) {
                alert('Please select at least one transaction to mark as swimming. Note: Transactions already marked as swimming cannot be selected.');
                return;
            }
            
            if (!confirm(`Mark ${checked.length} transaction(s) as swimming transactions? This will exclude them from fee invoice allocation. Once marked, they cannot be moved again.`)) {
                return;
            }
            
            document.getElementById('bulkSwimmingForm').submit();
        }
        
        function bulkTransferToSwimming() {
            const checked = Array.from(document.querySelectorAll('.transaction-checkbox:checked'))
                .filter(cb => cb.getAttribute('data-can-transfer-swimming') === '1')
                .map(cb => parseInt(cb.value));
            
            if (checked.length === 0) {
                alert('Please select at least one collected payment to transfer to swimming');
                return;
            }
            
            if (!confirm(`Transfer ${checked.length} collected payment(s) to swimming? This will reverse the payment and move it to swimming for wallet allocation.`)) {
                return;
            }
            
            document.getElementById('bulkTransferToSwimmingForm').submit();
        }
        
        function bulkTransferFromSwimming() {
            const checked = Array.from(document.querySelectorAll('.transaction-checkbox:checked'))
                .filter(cb => cb.getAttribute('data-can-transfer-from-swimming') === '1')
                .map(cb => parseInt(cb.value));
            
            if (checked.length === 0) {
                alert('Please select at least one swimming transaction to transfer back to ordinary payments');
                return;
            }
            
            if (!confirm(`Transfer ${checked.length} swimming payment(s) back to ordinary payments? This will reverse the swimming wallet allocation and create/restore the payment for invoice allocation.`)) {
                return;
            }
            
            document.getElementById('bulkTransferFromSwimmingForm').submit();
        }

        function bulkConfirm() {
            const checked = Array.from(document.querySelectorAll('.transaction-checkbox:checked')).map(cb => parseInt(cb.value));
            
            if (checked.length === 0) {
                alert('Please select at least one transaction to confirm');
                return;
            }
            
            // All checkboxes shown are confirmable (they're only shown if status is draft and has student_id/is_shared)
            // No need to filter - if it has a checkbox, it can be confirmed
            const bulkIdsContainer = document.getElementById('bulkTransactionIdsContainer');
            bulkIdsContainer.innerHTML = '';
            checked.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'transaction_ids[]';
                input.value = id;
                bulkIdsContainer.appendChild(input);
            });
            
            if (confirm(`Confirm ${checked.length} transaction(s)? This will confirm draft, auto-assigned, and manual-assigned transactions.`)) {
                document.getElementById('bulkConfirmForm').submit();
            }
        }

        function bulkArchive() {
            const checked = Array.from(document.querySelectorAll('.transaction-checkbox:checked'))
                .filter(cb => cb.getAttribute('data-can-archive') === '1')
                .map(cb => parseInt(cb.value));
            
            if (checked.length === 0) {
                alert('Please select at least one unmatched transaction to archive');
                return;
            }
            
            const bulkArchiveIdsContainer = document.getElementById('bulkArchiveTransactionIdsContainer');
            bulkArchiveIdsContainer.innerHTML = '';
            checked.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'transaction_ids[]';
                input.value = id;
                bulkArchiveIdsContainer.appendChild(input);
            });
            
            if (confirm(`Archive ${checked.length} unmatched transaction(s)? This will move them to the archived view.`)) {
                document.getElementById('bulkArchiveForm').submit();
            }
        }

        function autoAssign() {
            console.log('Auto-assign clicked');
            const checked = Array.from(document.querySelectorAll('.transaction-checkbox:checked')).map(cb => parseInt(cb.value));
            const form = document.getElementById('autoAssignForm');
            const autoAssignIdsContainer = document.getElementById('autoAssignTransactionIdsContainer');
            
            if (!form) {
                console.error('Auto-assign form not found');
                alert('Error: Auto-assign form not found. Please refresh the page.');
                return;
            }
            
            if (!autoAssignIdsContainer) {
                console.error('Auto-assign container not found');
                alert('Error: Auto-assign container not found. Please refresh the page.');
                return;
            }
            
            // Clear existing hidden inputs
            autoAssignIdsContainer.innerHTML = '';
            
            console.log('Checked transactions:', checked);
            
            // If no specific selection, process all confirmed transactions
            if (checked.length === 0) {
                if (confirm('Create payments for all confirmed transactions? This will create payments for confirmed transactions that are matched (auto-assigned or manual-assigned) but don\'t have payments yet.')) {
                    console.log('Submitting form for all confirmed transactions');
                    form.submit();
                }
            } else {
                // Add hidden inputs for each checked ID
                checked.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'transaction_ids[]';
                    input.value = id;
                    autoAssignIdsContainer.appendChild(input);
                });
                
                console.log('Added hidden inputs:', autoAssignIdsContainer.innerHTML);
                
                if (confirm(`Create payments for ${checked.length} selected transaction(s)? This will process confirmed transactions that are matched (auto-assigned or manual-assigned) but don't have payments yet.`)) {
                    console.log('Submitting form with selected transactions');
                    form.submit();
                }
            }
        }

        // Update auto-assign IDs when checkboxes change
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('.transaction-checkbox');
            checkboxes.forEach(cb => {
                cb.addEventListener('change', function() {
                    updateBulkIds();
                });
            });
            
            // Initial update
            updateBulkIds();
        });
    </script>
@endsection

@section('js')
<script>
let isRefreshing = false;

function refreshTransactions() {
    if (isRefreshing) return;
    
    const refreshBtn = document.getElementById('refreshBtn');
    const refreshIcon = document.getElementById('refreshIcon');
    
    // Disable button and show loading
    refreshBtn.disabled = true;
    refreshIcon.classList.add('spinning');
    
    isRefreshing = true;
    
    // Reload page to show updated transactions
    window.location.reload();
}

// Add spinning animation
const style = document.createElement('style');
style.textContent = `
    .spinning {
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);
</script>
@endsection

