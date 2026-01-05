@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'Bank Statement Transactions',
        'icon' => 'bi bi-bank',
        'subtitle' => 'Upload and reconcile bank statements',
        'actions' => '<a href="' . route('finance.bank-statements.create') . '" class="btn btn-finance btn-finance-primary"><i class="bi bi-upload"></i> Upload Statement</a>'
    ])

    @include('finance.invoices.partials.alerts')

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
                        Auto-Assigned <span class="badge bg-success">{{ $counts['auto-assigned'] ?? 0 }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ ($view ?? 'all') == 'draft' ? 'active' : '' }}" href="{{ route('finance.bank-statements.index', ['view' => 'draft'] + request()->except('view')) }}">
                        Draft <span class="badge bg-warning">{{ $counts['draft'] ?? 0 }}</span>
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
    @if(request('status') == 'draft' || !request('status'))
    <div class="d-flex justify-content-between align-items-center mb-3">
        <form id="bulkConfirmForm" method="POST" action="{{ route('finance.bank-statements.bulk-confirm') }}">
            @csrf
            <input type="hidden" name="transaction_ids" id="bulkTransactionIds">
            <button type="button" class="btn btn-finance btn-finance-success" onclick="bulkConfirm()">
                <i class="bi bi-check-circle"></i> Confirm Selected
            </button>
        </form>
    </div>
    @endif

    <!-- Transactions Table -->
    <div class="finance-table-wrapper finance-animate shadow-sm rounded-4 border-0">
        <div class="table-responsive">
            <table class="table table-hover finance-table">
                <thead>
                    <tr>
                        <th width="40">
                            @if(request('status') == 'draft' || !request('status'))
                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
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
                                @if(request('status') == 'draft' || !request('status'))
                                <input type="checkbox" class="transaction-checkbox" value="{{ $transaction->id }}" onchange="updateBulkIds()">
                                @endif
                            </td>
                            <td>{{ $transaction->transaction_date->format('d M Y') }}</td>
                            <td>
                                <strong class="{{ $transaction->transaction_type == 'credit' ? 'text-success' : 'text-danger' }}">
                                    {{ $transaction->transaction_type == 'credit' ? '+' : '-' }}Ksh {{ number_format($transaction->amount, 2) }}
                                </strong>
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width: 200px;" title="{{ $transaction->description }}">
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
                                @elseif($transaction->status == 'confirmed')
                                    <span class="badge bg-success">Confirmed</span>
                                @elseif($transaction->status == 'rejected')
                                    <span class="badge bg-danger">Rejected</span>
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
            const checkboxes = document.querySelectorAll('.transaction-checkbox');
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateBulkIds();
        }

        function updateBulkIds() {
            const checked = Array.from(document.querySelectorAll('.transaction-checkbox:checked')).map(cb => cb.value);
            document.getElementById('bulkTransactionIds').value = JSON.stringify(checked);
        }

        function bulkConfirm() {
            const ids = JSON.parse(document.getElementById('bulkTransactionIds').value || '[]');
            if (ids.length === 0) {
                alert('Please select at least one transaction');
                return;
            }
            if (confirm(`Confirm ${ids.length} transaction(s)?`)) {
                document.getElementById('bulkConfirmForm').submit();
            }
        }
    </script>
@endsection

