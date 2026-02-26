@extends('layouts.app')

@push('styles')
<style>
    /* Fix for sticky header overlapping transactions table in bank statements view */
    #bank-statements-transactions-section {
        position: relative;
        z-index: 1;
        margin-top: 0;
        scroll-margin-top: 100px;
    }

    /* Consistent font across filter tabs - no font change when switching */
    .nav-tabs-finance .nav-link,
    .nav-tabs-finance .nav-link.active {
        font-family: inherit;
        font-size: 0.9rem;
        font-weight: 500;
    }
    .nav-tabs-finance .nav-link .badge {
        font-size: 0.8rem;
        font-weight: 500;
    }

    /* Responsive bank statements - card layout on mobile */
    @media (max-width: 767.98px) {
        .nav-tabs-finance {
            flex-wrap: nowrap;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 0.5rem;
        }
        .nav-tabs-finance .nav-link,
        .nav-tabs-finance .nav-link.active {
            white-space: nowrap;
            font-size: 0.85rem;
            padding: 0.5rem 0.75rem;
        }
        #bulkActionsContainer {
            flex-direction: column;
            align-items: stretch !important;
        }
        #bulkActionsContainer .d-flex.gap-2 {
            flex-wrap: wrap;
            justify-content: flex-start;
        }
        #bulkActionsContainer .btn, #bulkActionsContainer .form button {
            font-size: 0.8rem;
            padding: 0.35rem 0.6rem;
        }
        .finance-filter-card .row.g-3 [class^="col-"] {
            flex: 0 0 100%;
            max-width: 100%;
        }
        /* Mobile transaction cards */
        .finance-table-wrapper .table-responsive {
            overflow-x: visible;
        }
        .finance-table-wrapper table thead {
            display: none;
        }
        .finance-table-wrapper table tbody tr {
            display: block;
            border: 1px solid var(--bs-border-color, #dee2e6);
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            padding: 1rem;
            background: var(--bs-body-bg, #fff);
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .finance-table-wrapper table tbody td {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 0.5rem 0;
            border: none;
            gap: 0.75rem;
        }
        .finance-table-wrapper table tbody td[data-label]:not([data-label=""])::before {
            content: attr(data-label);
            font-weight: 600;
            color: var(--bs-secondary);
            flex-shrink: 0;
            min-width: 80px;
        }
        .finance-table-wrapper table tbody td:first-child {
            border-bottom: 1px solid var(--bs-border-color-translucent, rgba(0,0,0,0.1));
        }
        .finance-table-wrapper table tbody td .btn-group {
            flex-wrap: wrap;
        }
    }
    @media (min-width: 768px) {
        .finance-table-wrapper tbody td[data-label]::before {
            display: none;
        }
    }

    /* Desktop: visually appealing transaction rows */
    .finance-table-wrapper .finance-table tbody tr {
        transition: background-color 0.2s ease, box-shadow 0.2s ease;
    }
    .finance-table-wrapper .finance-table tbody tr:hover {
        background: color-mix(in srgb, var(--fin-primary, #0f766e) 4%, var(--fin-surface, #fff) 96%) !important;
    }
    .finance-table-wrapper .finance-table tbody tr:nth-child(even) {
        background: color-mix(in srgb, var(--fin-primary, #0f766e) 2%, var(--fin-surface, #fff) 98%);
    }
    .finance-table-wrapper .finance-table tbody tr:nth-child(even):hover {
        background: color-mix(in srgb, var(--fin-primary, #0f766e) 5%, var(--fin-surface, #fff) 95%) !important;
    }
    /* Description block - clear hierarchy */
    .bank-txn-description {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }
    .bank-txn-description .txn-primary {
        font-weight: 600;
        color: var(--fin-text, #0f172a);
        line-height: 1.35;
    }
    .bank-txn-description .txn-payer {
        font-size: 0.8rem;
        color: var(--fin-muted, #6b7280);
    }
    /* Reference - monospace with subtle pill */
    .bank-txn-reference,
    td[data-label="Reference"] code {
        font-family: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
        font-size: 0.85rem;
        padding: 0.2rem 0.5rem;
        background: color-mix(in srgb, var(--fin-primary, #0f766e) 8%, transparent 92%);
        border-radius: 6px;
        color: var(--fin-primary, #0f766e);
    }
    /* Amount emphasis */
    .bank-txn-amount {
        font-variant-numeric: tabular-nums;
        letter-spacing: 0.02em;
    }
    /* Status badges - pill style */
    .finance-table-wrapper .badge {
        border-radius: 999px;
        padding: 0.35rem 0.65rem;
        font-size: 0.75rem;
        font-weight: 500;
    }

    /* Mobile: polished transaction cards */
    @media (max-width: 767.98px) {
        .finance-table-wrapper table tbody tr {
            padding: 0;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid var(--fin-border, #e5e7eb);
            border-left: 4px solid var(--fin-primary, #0f766e);
        }
        .finance-table-wrapper table tbody tr:first-child td {
            padding-top: 1rem;
        }
        .finance-table-wrapper table tbody tr td:first-child {
            padding-top: 1rem;
            border-bottom: none;
            padding-bottom: 0.5rem;
        }
        .finance-table-wrapper table tbody td[data-label="Date"] {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.25rem;
            padding: 0.75rem 1rem;
            background: color-mix(in srgb, var(--fin-primary, #0f766e) 6%, var(--fin-surface, #fff) 94%);
            border-radius: 0.5rem 0.5rem 0 0;
            margin: 0 1rem;
            margin-top: 0.5rem;
        }
        .finance-table-wrapper table tbody td[data-label="Amount"] {
            padding: 0.5rem 1rem 0.75rem;
            font-size: 1.1rem;
        }
        .finance-table-wrapper table tbody td[data-label="Amount"] strong {
            font-size: 1.05rem;
        }
        .finance-table-wrapper table tbody td[data-label="Description"] {
            padding: 0.75rem 1rem;
            flex-direction: column;
            align-items: flex-start;
            gap: 0.25rem;
        }
        .finance-table-wrapper table tbody td[data-label="Description"]::before {
            display: none;
        }
        .finance-table-wrapper table tbody td[data-label="Description"] .bank-txn-description {
            width: 100%;
        }
        .finance-table-wrapper table tbody td[data-label="Reference"] code {
            font-size: 0.8rem;
            padding: 0.2rem 0.4rem;
        }
        .finance-table-wrapper table tbody td[data-label="Student"] {
            padding: 0.75rem 1rem;
        }
        .finance-table-wrapper table tbody td[data-label="Actions"] {
            padding: 0.75rem 1rem;
            border-top: 1px solid var(--fin-border, rgba(0,0,0,0.08));
            margin-top: 0.25rem;
        }
        .finance-table-wrapper table tbody td[data-label]::before {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            min-width: 70px;
        }
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
            @if(($view ?? 'all') === 'all')
            <div class="col-md-3">
                <label class="finance-form-label">Status</label>
                <select name="status" class="finance-form-select">
                    <option value="">All Statuses</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            @endif
            <div class="col-md-3">
                <label class="finance-form-label">Swimming Transaction</label>
                <select name="is_swimming" class="finance-form-select">
                    <option value="">All Transactions</option>
                    <option value="1" {{ request('is_swimming') == '1' ? 'selected' : '' }}>Swimming Only</option>
                    <option value="0" {{ request('is_swimming') == '0' ? 'selected' : '' }}>Non-Swimming Only</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="finance-form-label">Bank Type</label>
                <select name="bank_type" class="finance-form-select">
                    <option value="">All (M-Pesa + Equity)</option>
                    <option value="mpesa" {{ request('bank_type') == 'mpesa' ? 'selected' : '' }}>M-Pesa</option>
                    <option value="equity" {{ request('bank_type') == 'equity' ? 'selected' : '' }}>Equity Bank</option>
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
                    <i class="bi bi-check-circle"></i> Confirm & Create Payments
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
            <form method="POST" action="{{ route('finance.bank-statements.reconcile-payments') }}" onsubmit="return confirm('Reconcile payment links for all bank statement transactions? This syncs transaction status with actual payments and can fix transactions stuck in Confirmed when payments already exist.');">
                @csrf
                <button type="submit" class="btn btn-finance btn-finance-secondary" title="Sync transaction payment_created and payment_id with actual payments by reference number. Fixes transactions stuck in Confirmed when matching payments exist." data-bs-toggle="tooltip" data-bs-placement="bottom">
                    <i class="bi bi-arrow-repeat"></i> Reconcile Payments
                </button>
            </form>
        </div>
    </div>
    <div id="bulkSelectionHint" class="small text-muted mt-1 mb-2" style="display: none;"></div>

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
                            @if(in_array(request('view'), ['draft', 'auto-assigned', 'manual-assigned', 'collected', 'unassigned', 'all', 'swimming']) || !request('view'))
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
                        @if(($view ?? 'all') === 'all')
                        <th>Status</th>
                        @endif
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
                            $txnIsEquity = !$isC2B && ($transaction->bank_type ?? null) === 'equity';
                            $txnStudentId = $transaction->student_id;
                            $txnIsShared = $transaction->is_shared ?? false;
                            $txnSharedAllocations = $transaction->shared_allocations ?? [];
                            $txnAllocationStatus = $isC2B ? ($transaction->allocation_status ?? 'unallocated') : null;
                            $activeTotal = (float) ($transaction->active_payment_total ?? 0);
                            $isFullyCollected = $txnStatus === 'confirmed' && $activeTotal >= ($txnAmount - 0.01);
                            $isPartiallyCollected = $txnStatus === 'confirmed' && $activeTotal > 0.01 && $activeTotal < ($txnAmount - 0.01);
                            
                            // Permission checks - only auto-assigned or manual-assigned (NOT unassigned)
                            $isAutoOrManualAssigned = $isC2B
                                ? in_array($txnAllocationStatus ?? '', ['auto_matched', 'manually_allocated'])
                                : in_array($txnMatchStatus ?? '', ['matched', 'manual']);
                            $canConfirm = !$txnIsDuplicate 
                                && !$txnIsArchived
                                && $isAutoOrManualAssigned
                                && ($txnStudentId || $txnIsShared)
                                && ($txnStatus === 'draft' || ($txnStatus === 'confirmed' && !$txnPaymentCreated));
                            $canArchive = $txnMatchStatus === 'unmatched'
                                && !$txnIsArchived
                                && !$txnIsDuplicate
                                && !$txnStudentId
                                && !$isC2B; // C2B transactions can't be archived
                            // For C2B, check if swimming column exists
                            $c2bCanSwim = $isC2B ? \Illuminate\Support\Facades\Schema::hasColumn('mpesa_c2b_transactions', 'is_swimming_transaction') : true;
                            
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
                            // Payer full name: C2B = first + middle + last; Bank = payer_name
                            $payerFullName = $isC2B
                                ? trim(implode(' ', array_filter([$transaction->first_name ?? '', $transaction->middle_name ?? '', $transaction->last_name ?? ''])))
                                : ($transaction->payer_name ?? '');
                        @endphp
                        <tr>
                            <td data-label="Select">
                                @if($canConfirm || $canArchive || $canTransferToSwimming || $canTransferFromSwimming || $canMarkAsSwimming || $canSelectDraftUnmatched)
                                    <input type="checkbox" class="transaction-checkbox" value="{{ $transaction->id }}" data-txn-type="{{ $isC2B ? 'c2b' : 'bank' }}" onchange="updateBulkIds()" data-can-confirm="{{ $canConfirm ? '1' : '0' }}" data-can-archive="{{ $canArchive ? '1' : '0' }}" data-can-transfer-swimming="{{ $canTransferToSwimming ? '1' : '0' }}" data-can-transfer-from-swimming="{{ $canTransferFromSwimming ? '1' : '0' }}" data-can-mark-swimming="{{ ($canMarkAsSwimming || $canSelectDraftUnmatched) ? '1' : '0' }}">
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td data-label="Date">
                                @if($isC2B)
                                    <span class="badge bg-success mb-1 d-block" style="font-size: 0.7rem;">C2B</span>
                                @endif
                                {{ $txnDate instanceof \Carbon\Carbon ? $txnDate->format('d M Y') : \Carbon\Carbon::parse($txnDate)->format('d M Y') }}
                                @if($isC2B && $transaction->trans_time)
                                    <br><small class="text-muted">{{ $transaction->trans_time->format('h:i A') }}</small>
                                @endif
                            </td>
                            <td data-label="Amount">
                                <div class="d-flex align-items-center gap-2 flex-wrap bank-txn-amount">
                                    <strong class="text-success">
                                        +Ksh {{ number_format($txnAmount, 2) }}
                                    </strong>
                                    @if($txnIsEquity && file_exists(public_path('images/equity-bank-logo.png')))
                                        <img src="{{ asset('images/equity-bank-logo.png') }}" alt="Equity Bank" class="bank-type-logo" style="height: 20px; max-width: 60px; object-fit: contain;" title="Equity Bank Payment">
                                    @endif
                                    @if($txnIsSwimming && in_array($view ?? 'all', ['all', 'swimming']) && file_exists(public_path('images/swim logo.png')))
                                        <img src="{{ asset('images/swim logo.png') }}" alt="Swimming" class="swim-logo" style="height: 20px; max-width: 40px; object-fit: contain;" title="Swimming Transaction">
                                    @elseif($txnIsSwimming)
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
                            <td data-label="Description">
                                <div class="bank-txn-description text-break" style="max-width: 300px; word-wrap: break-word; white-space: pre-wrap;" title="{{ $txnDescription }}">
                                    @if($isC2B && $transaction->bill_ref_number)
                                        <span class="txn-primary">{{ $transaction->bill_ref_number }}</span>
                                        @if($payerFullName)
                                            <span class="txn-payer"><i class="bi bi-person me-1"></i>{{ $payerFullName }}</span>
                                        @endif
                                    @else
                                        <span class="txn-primary">{{ $txnDescription }}</span>
                                        @if($isC2B && $payerFullName)
                                            <span class="txn-payer"><i class="bi bi-person me-1"></i>{{ $payerFullName }}</span>
                                        @endif
                                    @endif
                                </div>
                            </td>
                            <td data-label="Reference">
                                <code class="bank-txn-reference">{{ $txnReference }}</code>
                            </td>
                            <td data-label="Phone">{{ $txnPhone ?? 'N/A' }}</td>
                            <td data-label="Student">
                                @if($txnIsDuplicate)
                                    <span class="text-danger">
                                        <i class="bi bi-exclamation-triangle"></i> Duplicate
                                        @if(!$isC2B && $transaction->duplicateOfTransaction)
                                            <br><a href="{{ route('finance.bank-statements.show', $transaction->duplicateOfTransaction) }}?type=bank" class="text-primary text-decoration-none" title="View original transaction">Original #{{ $transaction->duplicateOfTransaction->id }}</a>
                                        @elseif(!$isC2B && $transaction->duplicateOfPayment)
                                            <br><small>Payment: {{ $transaction->duplicateOfPayment->receipt_number ?? $transaction->duplicateOfPayment->transaction_code }}</small>
                                        @elseif($isC2B && $transaction->duplicateOf)
                                            <br><a href="{{ route('finance.bank-statements.show', $transaction->duplicateOf) }}?type=c2b" class="text-primary text-decoration-none" title="View original transaction">Original #{{ $transaction->duplicateOf->id }}</a>
                                        @elseif($isC2B && !$transaction->duplicate_of)
                                            <br><small class="text-muted">Cross-type (bank original)</small>
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
                                @if(!$isC2B && $payerFullName)
                                    <br><small class="text-info">Payer: {{ $payerFullName }}</small>
                                @endif
                            </td>
                            @if(($view ?? 'all') === 'all')
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
                            @endif
                            <td data-label="Actions">
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
                            <td colspan="{{ ($view ?? 'all') === 'all' ? 10 : 9 }}" class="text-center py-4 text-muted">
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
            const bulkArchiveIdsContainer = document.getElementById('bulkArchiveTransactionIdsContainer');
            const bulkSwimmingIdsContainer = document.getElementById('bulkSwimmingTransactionIdsContainer');
            const bulkConfirmBtn = document.getElementById('bulkConfirmBtn');
            const bulkArchiveBtn = document.getElementById('bulkArchiveIdsContainer');
            const bulkActionsContainer = document.getElementById('bulkActionsContainer');
            
            // Clear existing hidden inputs
            bulkIdsContainer.innerHTML = '';
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

            // Hint when selection has no confirmable transactions
            var hintEl = document.getElementById('bulkSelectionHint');
            if (hintEl) {
                if (checked.length > 0 && confirmableIds.length === 0) {
                    hintEl.textContent = 'Selected transactions are already confirmed and collected. No action needed.';
                    hintEl.style.display = 'block';
                } else {
                    hintEl.textContent = '';
                    hintEl.style.display = 'none';
                }
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
            const confirmableIds = Array.from(document.querySelectorAll('.transaction-checkbox:checked'))
                .filter(cb => cb.getAttribute('data-can-confirm') === '1')
                .map(cb => parseInt(cb.value));
            if (confirmableIds.length === 0) {
                alert('Please select at least one transaction that can be confirmed (draft or auto/manual-assigned without payment).');
                return;
            }
            const bulkIdsContainer = document.getElementById('bulkTransactionIdsContainer');
            bulkIdsContainer.innerHTML = '';
            confirmableIds.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'transaction_ids[]';
                input.value = id;
                bulkIdsContainer.appendChild(input);
            });
            if (confirm(`Confirm and create payments for ${confirmableIds.length} transaction(s)? Receipts will open for printing.`)) {
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

        // Update bulk IDs when checkboxes change
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
// Initialize Bootstrap tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltipEls = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        tooltipEls.forEach(el => new bootstrap.Tooltip(el));
    } else if (typeof $ !== 'undefined' && $.fn.tooltip) {
        $(tooltipEls).tooltip();
    }
});

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

