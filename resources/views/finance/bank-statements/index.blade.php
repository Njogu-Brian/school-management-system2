@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'Bank Statement Transactions',
        'icon' => 'bi bi-bank',
        'subtitle' => 'Upload and reconcile bank statements',
        'actions' => '<a href="' . route('finance.bank-statements.statements') . '" class="btn btn-finance btn-finance-info"><i class="bi bi-folder2-open"></i> View Statements</a>
                      <a href="' . route('finance.bank-statements.create') . '" class="btn btn-finance btn-finance-primary"><i class="bi bi-upload"></i> Upload Statement</a>'
    ])

    @include('finance.invoices.partials.alerts')

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

    <!-- Summary Card -->
    <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="mb-0">Total Parsed Amount</h5>
                    <p class="text-muted mb-0">Total for {{ $totalCount ?? 0 }} transaction(s)</p>
                </div>
                <div class="col-md-6 text-end">
                    <h3 class="mb-0 text-success">Ksh {{ number_format($totalAmount ?? 0, 2) }}</h3>
                    <small class="text-muted">Compare with statement total</small>
                </div>
            </div>
        </div>
    </div>

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
            
            <form id="bulkSwimmingForm" method="POST" action="{{ route('bulk-mark-swimming') }}">
                @csrf
                <div id="bulkSwimmingTransactionIdsContainer"></div>
                <button type="button" class="btn btn-finance btn-finance-info" onclick="bulkMarkSwimming()" id="bulkSwimmingBtn" style="display: none;">
                    <i class="bi bi-water"></i> Mark as Swimming Transaction
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
        
        <form id="autoAssignForm" method="POST" action="{{ route('finance.bank-statements.auto-assign') }}">
            @csrf
            <div id="autoAssignTransactionIdsContainer"></div>
            <button type="button" class="btn btn-finance btn-finance-primary" onclick="autoAssign()" id="autoAssignBtn">
                <i class="bi bi-magic"></i> Auto-Assign (Create Payments for Confirmed)
            </button>
        </form>
    </div>

    <!-- Transactions Table -->
    <div class="finance-table-wrapper finance-animate shadow-sm rounded-4 border-0">
        <div class="table-responsive">
            <table class="table table-hover finance-table">
                <thead>
                    <tr>
                        <th width="40">
                            @if(in_array(request('view'), ['draft', 'auto-assigned', 'manual-assigned', 'confirmed', 'collected', 'unassigned', 'all']) || !request('view'))
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </th>
                        <th>Date</th>
                        <th>Amount</th>
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
                        <tr>
                            <td>
                                @php
                                    $canConfirm = $transaction->status === 'draft' 
                                        && !$transaction->is_duplicate 
                                        && !$transaction->is_archived
                                        && ($transaction->student_id || $transaction->is_shared);
                                    $canArchive = $transaction->match_status === 'unmatched'
                                        && !$transaction->is_archived
                                        && !$transaction->is_duplicate
                                        && !$transaction->student_id;
                                @endphp
                                @if($canConfirm || $canArchive)
                                    <input type="checkbox" class="transaction-checkbox" value="{{ $transaction->id }}" onchange="updateBulkIds()" data-can-confirm="{{ $canConfirm ? '1' : '0' }}" data-can-archive="{{ $canArchive ? '1' : '0' }}">
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $transaction->transaction_date->format('d M Y') }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <strong class="{{ $transaction->transaction_type == 'credit' ? 'text-success' : 'text-danger' }}">
                                        {{ $transaction->transaction_type == 'credit' ? '+' : '-' }}Ksh {{ number_format($transaction->amount, 2) }}
                                    </strong>
                                    @if($transaction->is_swimming_transaction)
                                        <span class="badge bg-info" title="Swimming Transaction">
                                            <i class="bi bi-water"></i>
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="text-break" style="max-width: 300px; word-wrap: break-word; white-space: pre-wrap;" title="{{ $transaction->description }}">
                                    {{ $transaction->description }}
                                </div>
                            </td>
                            <td><code>{{ $transaction->reference_number ?? 'N/A' }}</code></td>
                            <td>{{ $transaction->phone_number ?? 'N/A' }}</td>
                            <td>
                                @if($transaction->is_duplicate)
                                    <span class="text-danger">
                                        <i class="bi bi-exclamation-triangle"></i> Duplicate
                                        @if($transaction->duplicateOfPayment)
                                            <br><small>Payment: {{ $transaction->duplicateOfPayment->receipt_number ?? $transaction->duplicateOfPayment->transaction_code }}</small>
                                        @endif
                                    </span>
                                @elseif($transaction->is_shared && $transaction->shared_allocations)
                                    <div class="text-primary">
                                        <i class="bi bi-people"></i> <strong>Shared Payment</strong>
                                        <br><small class="text-info">({{ count($transaction->shared_allocations) }} sibling{{ count($transaction->shared_allocations) === 1 ? '' : 's' }})</small>
                                    </div>
                                    @foreach($transaction->shared_allocations as $allocation)
                                        @php $student = \App\Models\Student::find($allocation['student_id']); @endphp
                                        @if($student)
                                            <div class="mt-1">
                                                <a href="{{ route('students.show', $student) }}" class="text-decoration-none">
                                                    {{ $student->first_name }} {{ $student->last_name }}
                                                    <br><small class="text-muted">{{ $student->admission_number }}</small>
                                                </a>
                                                <br><small class="text-success fw-bold">Ksh {{ number_format($allocation['amount'], 2) }}</small>
                                            </div>
                                        @endif
                                    @endforeach
                                @elseif($transaction->student_id)
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
                                    @if($transaction->student)
                                        <a href="{{ route('students.show', $transaction->student) }}">
                                            {{ $transaction->student->first_name }} {{ $transaction->student->last_name }}
                                            <br><small class="text-muted">{{ $transaction->student->admission_number }}</small>
                                        </a>
                                        @if(count($siblings) > 0 && !$transaction->is_shared)
                                            <br><small class="text-info">
                                                <i class="bi bi-people"></i> {{ count($siblings) }} sibling{{ count($siblings) === 1 ? '' : 's' }} available
                                            </small>
                                        @endif
                                    @else
                                        <span class="text-muted">
                                            Student #{{ $transaction->student_id }}
                                            <br><small>(Archived/Alumni)</small>
                                        </span>
                                    @endif
                                @elseif($transaction->student)
                                    <a href="{{ route('students.show', $transaction->student) }}">
                                        {{ $transaction->student->first_name }} {{ $transaction->student->last_name }}
                                        <br><small class="text-muted">{{ $transaction->student->admission_number }}</small>
                                    </a>
                                @else
                                    <span class="text-muted">Unmatched</span>
                                @endif
                                @if($transaction->payer_name)
                                    <br><small class="text-info">Payer: {{ $transaction->payer_name }}</small>
                                @endif
                            </td>
                            <td>
                                @if($transaction->match_status == 'matched')
                                    <span class="badge bg-success">Matched</span>
                                @elseif($transaction->match_status == 'multiple_matches')
                                    <span class="badge bg-warning">Multiple</span>
                                @elseif($transaction->match_status == 'manual')
                                    <span class="badge bg-info">Manual</span>
                                @else
                                    <span class="badge bg-secondary">Unmatched</span>
                                @endif
                            </td>
                            <td>
                                @if($transaction->is_archived)
                                    <span class="badge bg-secondary">Archived</span>
                                @elseif($transaction->is_duplicate)
                                    <span class="badge bg-danger">Duplicate</span>
                                @elseif($transaction->status == 'confirmed' && $transaction->payment_created)
                                    <span class="badge bg-success">Collected</span>
                                @elseif($transaction->status == 'confirmed')
                                    <span class="badge bg-primary">Confirmed</span>
                                @elseif($transaction->status == 'rejected')
                                    <span class="badge bg-danger">Rejected</span>
                                @elseif($transaction->match_status == 'matched' && ($transaction->student_id || $transaction->is_shared))
                                    <span class="badge bg-success">Auto Assigned</span>
                                @elseif($transaction->match_status == 'manual' && ($transaction->student_id || $transaction->is_shared))
                                    <span class="badge bg-info">Manual Assigned</span>
                                @else
                                    <span class="badge bg-warning">Draft</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('finance.bank-statements.show', $transaction) }}" class="btn btn-finance btn-finance-secondary" title="View">
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
                                    @if(!$transaction->is_archived)
                                        <form method="POST" action="{{ route('finance.bank-statements.archive', $transaction) }}" class="d-inline" onsubmit="return confirm('Archive this transaction?')">
                                            @csrf
                                            <button type="submit" class="btn btn-finance btn-finance-secondary" title="Archive">
                                                <i class="bi bi-archive"></i>
                                            </button>
                                        </form>
                                    @else
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
            {{ $transactions->links() }}
        </div>
    </div>

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
            
            // Separate transactions that can be confirmed vs archived
            const confirmableIds = [];
            const archivableIds = [];
            
            checked.forEach(cb => {
                const id = parseInt(cb.value);
                const canConfirm = cb.getAttribute('data-can-confirm') === '1';
                const canArchive = cb.getAttribute('data-can-archive') === '1';
                
                if (canConfirm) {
                    confirmableIds.push(id);
                }
                if (canArchive) {
                    archivableIds.push(id);
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
            
            // Show/hide bulk swimming button
            if (bulkSwimmingBtn) {
                if (checkedIds.length > 0) {
                    bulkSwimmingBtn.style.display = 'inline-block';
                } else {
                    bulkSwimmingBtn.style.display = 'none';
                }
            }
        }
        
        function bulkMarkSwimming() {
            const checked = Array.from(document.querySelectorAll('.transaction-checkbox:checked')).map(cb => parseInt(cb.value));
            
            if (checked.length === 0) {
                alert('Please select at least one transaction to mark as swimming');
                return;
            }
            
            if (!confirm(`Mark ${checked.length} transaction(s) as swimming transactions? This will exclude them from fee invoice allocation.`)) {
                return;
            }
            
            const form = document.getElementById('bulkSwimmingForm');
            const bulkSwimmingIdsContainer = document.getElementById('bulkSwimmingTransactionIdsContainer');
            bulkSwimmingIdsContainer.innerHTML = '';
            checked.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'transaction_ids[]';
                input.value = id;
                bulkSwimmingIdsContainer.appendChild(input);
            });
            
            form.submit();
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

