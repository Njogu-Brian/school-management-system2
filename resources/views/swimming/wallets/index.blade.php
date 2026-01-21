@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Swimming Wallets',
        'icon' => 'bi bi-water',
        'subtitle' => 'View and manage student swimming wallet balances',
        'actions' => '<a href="' . route('swimming.payments.create') . '" class="btn btn-finance btn-finance-primary"><i class="bi bi-plus-circle"></i> Record Payment</a>'
    ])

    @include('finance.invoices.partials.alerts')

    <!-- Bulk Send Card - Only show if there are students with negative balance -->
    @php
        $negativeBalanceCount = $wallets->filter(fn($w) => $w->balance < 0)->count();
        $totalOwed = abs($wallets->filter(fn($w) => $w->balance < 0)->sum('balance'));
    @endphp
    @if($negativeBalanceCount > 0)
    <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mb-4 border-start border-warning border-4">
        <div class="finance-card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h6 class="mb-1"><i class="bi bi-exclamation-triangle text-warning"></i> Outstanding Swimming Balances</h6>
                    <p class="text-muted small mb-0">
                        <strong>{{ $negativeBalanceCount }}</strong> student(s) have outstanding balances totaling 
                        <strong class="text-danger">Ksh {{ number_format($totalOwed, 2) }}</strong>
                    </p>
                </div>
                <button type="button" class="btn btn-finance btn-finance-warning" data-bs-toggle="modal" data-bs-target="#bulkSendModal">
                    <i class="bi bi-send"></i> Send Balance Messages & Payment Links
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Filters -->
    <div class="finance-filter-card finance-animate shadow-sm rounded-4 border-0 mb-4">
        <form method="GET" action="{{ route('swimming.wallets.index') }}" class="row g-3">
            <div class="col-md-4">
                <label class="finance-form-label">Search</label>
                <input type="text" 
                       name="search" 
                       class="finance-form-control" 
                       value="{{ $filters['search'] ?? '' }}" 
                       placeholder="Student name or admission number">
            </div>
            <div class="col-md-3">
                <label class="finance-form-label">Classroom</label>
                <select name="classroom_id" class="finance-form-select">
                    <option value="">All Classrooms</option>
                    @foreach($classrooms as $classroom)
                        <option value="{{ $classroom->id }}" {{ ($filters['classroom_id'] ?? '') == $classroom->id ? 'selected' : '' }}>
                            {{ $classroom->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="finance-form-label">Balance Filter</label>
                <select name="balance_filter" class="finance-form-select">
                    <option value="">All Balances</option>
                    <option value="positive" {{ ($filters['balance_filter'] ?? '') == 'positive' ? 'selected' : '' }}>Positive Balance</option>
                    <option value="zero" {{ ($filters['balance_filter'] ?? '') == 'zero' ? 'selected' : '' }}>Zero Balance</option>
                    <option value="negative" {{ ($filters['balance_filter'] ?? '') == 'negative' ? 'selected' : '' }}>Negative Balance (Owes)</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-finance btn-finance-primary flex-fill">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                <a href="{{ route('swimming.wallets.index') }}" class="btn btn-finance btn-finance-secondary" title="Clear Filters">
                    <i class="bi bi-x-circle"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Wallets Table -->
    <form id="bulkSelectForm">
    <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
        <div class="finance-card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Student Wallets</h5>
                <p class="text-muted small mb-0">{{ $wallets->total() }} wallet(s) found</p>
            </div>
            <div id="bulkActionsBar" class="d-none">
                <span class="me-2 text-muted"><span id="selectedCount">0</span> selected</span>
                <button type="button" class="btn btn-sm btn-finance btn-finance-warning" onclick="openBulkSendModalWithSelected()">
                    <i class="bi bi-send"></i> Send Balance Messages
                </button>
            </div>
        </div>
        <div class="finance-card-body p-0">
            <div class="table-responsive">
                <table class="table table-modern align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)" title="Select all with negative balance">
                            </th>
                            <th>Admission #</th>
                            <th>Student Name</th>
                            <th>Classroom</th>
                            <th class="text-end">Balance</th>
                            <th class="text-end">Total Credited</th>
                            <th class="text-end">Total Debited</th>
                            <th>Last Transaction</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($wallets as $wallet)
                            <tr>
                                <td>
                                    @if($wallet->balance < 0)
                                        <input type="checkbox" 
                                               class="student-checkbox" 
                                               name="student_ids[]" 
                                               value="{{ $wallet->student_id }}"
                                               data-balance="{{ $wallet->balance }}"
                                               onchange="updateSelectedCount()">
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $wallet->student->admission_number ?? 'N/A' }}</strong>
                                </td>
                                <td>
                                    {{ $wallet->student->full_name ?? '' }}
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ $wallet->student->classroom->name ?? 'N/A' }}</span>
                                </td>
                                <td class="text-end">
                                    <span class="fw-bold {{ $wallet->balance >= 0 ? 'text-success' : 'text-danger' }}">
                                        Ksh {{ number_format($wallet->balance, 2) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <span class="text-success">Ksh {{ number_format($wallet->total_credited, 2) }}</span>
                                </td>
                                <td class="text-end">
                                    <span class="text-danger">Ksh {{ number_format($wallet->total_debited, 2) }}</span>
                                </td>
                                <td>
                                    @if($wallet->last_transaction_at)
                                        <small class="text-muted">{{ $wallet->last_transaction_at->format('M d, Y') }}</small>
                                    @else
                                        <small class="text-muted">Never</small>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        @if($wallet->balance < 0)
                                            <button type="button" 
                                                    class="btn btn-sm btn-finance btn-finance-warning" 
                                                    title="Send Balance & Payment Link"
                                                    onclick="openSendModal({{ $wallet->student_id }}, '{{ $wallet->student->full_name ?? '' }}', {{ abs($wallet->balance) }})">
                                                <i class="bi bi-send"></i>
                                            </button>
                                        @endif
                                        <a href="{{ route('swimming.wallets.show', $wallet->student) }}" 
                                           class="btn btn-sm btn-finance btn-finance-outline" 
                                           title="View Wallet Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                        <p class="mt-3 mb-0">No wallets found</p>
                                        <small>Try adjusting your filters</small>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($wallets->hasPages())
            <div class="finance-card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Showing {{ $wallets->firstItem() }} to {{ $wallets->lastItem() }} of {{ $wallets->total() }} wallets
                    </div>
                    <div>
                        {{ $wallets->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>
    </form>
  </div>
</div>

<!-- Individual Send Modal -->
<div class="modal fade" id="sendModal" tabindex="-1" aria-labelledby="sendModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="individualSendForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="sendModalLabel">
                        <i class="bi bi-send"></i> Send Balance & Payment Link
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">
                        Send balance notification with payment link to <strong id="sendStudentName"></strong>
                    </p>
                    <p class="text-danger mb-3">
                        Outstanding Balance: <strong id="sendStudentBalance"></strong>
                    </p>
                    
                    <div class="mb-3">
                        <label class="finance-form-label">Send Via <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="channels[]" value="sms" id="indSMS" checked>
                                <label class="form-check-label" for="indSMS">SMS</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="channels[]" value="email" id="indEmail" checked>
                                <label class="form-check-label" for="indEmail">Email</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="channels[]" value="whatsapp" id="indWhatsApp">
                                <label class="form-check-label" for="indWhatsApp">WhatsApp</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="finance-form-label">Payment Link Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">Ksh</span>
                            <input type="number" class="form-control" name="amount" id="sendAmount" step="0.01" min="1">
                        </div>
                        <small class="text-muted">Leave blank to use full outstanding balance</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="finance-form-label">Link Expiration (Days)</label>
                        <input type="number" class="form-control" name="expiration_days" value="30" min="1" max="365">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-finance btn-finance-warning">
                        <i class="bi bi-send"></i> Send Now
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Send Modal -->
<div class="modal fade" id="bulkSendModal" tabindex="-1" aria-labelledby="bulkSendModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="bulkSendForm" method="POST" action="{{ route('swimming.payments.bulk-send-balance') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkSendModalLabel">
                        <i class="bi bi-send"></i> Bulk Send Balance Messages & Payment Links
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> This will send balance notifications with payment links to all selected students with outstanding (negative) balances.
                    </div>
                    
                    <div id="bulkStudentList" class="mb-3">
                        <!-- Populated by JavaScript -->
                    </div>
                    
                    <div class="mb-3">
                        <label class="finance-form-label">Send Via <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="channels[]" value="sms" id="bulkSMS" checked>
                                <label class="form-check-label" for="bulkSMS">SMS</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="channels[]" value="email" id="bulkEmail" checked>
                                <label class="form-check-label" for="bulkEmail">Email</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="channels[]" value="whatsapp" id="bulkWhatsApp">
                                <label class="form-check-label" for="bulkWhatsApp">WhatsApp</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="finance-form-label">Fixed Payment Link Amount (Optional)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Ksh</span>
                                    <input type="number" class="form-control" name="amount" step="0.01" min="1" placeholder="Use individual balance">
                                </div>
                                <small class="text-muted">Leave blank to use each student's outstanding balance</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="finance-form-label">Link Expiration (Days)</label>
                                <input type="number" class="form-control" name="expiration_days" value="30" min="1" max="365">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-finance btn-finance-warning">
                        <i class="bi bi-send"></i> Send to All Selected
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.student-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
    updateSelectedCount();
}

function updateSelectedCount() {
    const checked = document.querySelectorAll('.student-checkbox:checked');
    const count = checked.length;
    document.getElementById('selectedCount').textContent = count;
    
    const bulkActionsBar = document.getElementById('bulkActionsBar');
    if (count > 0) {
        bulkActionsBar.classList.remove('d-none');
    } else {
        bulkActionsBar.classList.add('d-none');
    }
    
    // Update select all checkbox state
    const allCheckboxes = document.querySelectorAll('.student-checkbox');
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    if (allCheckboxes.length > 0) {
        selectAllCheckbox.checked = allCheckboxes.length === checked.length;
        selectAllCheckbox.indeterminate = checked.length > 0 && checked.length < allCheckboxes.length;
    }
}

function openSendModal(studentId, studentName, balance) {
    document.getElementById('sendStudentName').textContent = studentName;
    document.getElementById('sendStudentBalance').textContent = 'Ksh ' + balance.toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('sendAmount').value = balance;
    document.getElementById('individualSendForm').action = '{{ url("swimming/payments/student") }}/' + studentId + '/send-balance';
    
    const modal = new bootstrap.Modal(document.getElementById('sendModal'));
    modal.show();
}

function openBulkSendModalWithSelected() {
    const checked = document.querySelectorAll('.student-checkbox:checked');
    if (checked.length === 0) {
        alert('Please select at least one student with outstanding balance.');
        return;
    }
    
    // Clear existing hidden inputs
    const form = document.getElementById('bulkSendForm');
    form.querySelectorAll('input[name="student_ids[]"]').forEach(el => el.remove());
    
    // Add hidden inputs for selected students
    let totalBalance = 0;
    checked.forEach(cb => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'student_ids[]';
        input.value = cb.value;
        form.appendChild(input);
        totalBalance += Math.abs(parseFloat(cb.dataset.balance) || 0);
    });
    
    // Update student list display
    document.getElementById('bulkStudentList').innerHTML = `
        <div class="alert alert-secondary">
            <strong>${checked.length}</strong> student(s) selected with total outstanding balance of 
            <strong class="text-danger">Ksh ${totalBalance.toLocaleString('en-US', {minimumFractionDigits: 2})}</strong>
        </div>
    `;
    
    const modal = new bootstrap.Modal(document.getElementById('bulkSendModal'));
    modal.show();
}

// For the top bulk send button - select all with negative balance
document.getElementById('bulkSendModal')?.addEventListener('show.bs.modal', function(e) {
    // If triggered from the top button (not from inline button), select all negative balance students
    if (e.relatedTarget && e.relatedTarget.dataset.bsToggle === 'modal') {
        const checkboxes = document.querySelectorAll('.student-checkbox');
        checkboxes.forEach(cb => cb.checked = true);
        updateSelectedCount();
        
        // Then open with selected
        setTimeout(() => openBulkSendModalWithSelected(), 100);
    }
});

// Form validation
document.getElementById('individualSendForm')?.addEventListener('submit', function(e) {
    const channels = this.querySelectorAll('input[name="channels[]"]:checked');
    if (channels.length === 0) {
        e.preventDefault();
        alert('Please select at least one channel (SMS, Email, or WhatsApp).');
    }
});

document.getElementById('bulkSendForm')?.addEventListener('submit', function(e) {
    const channels = this.querySelectorAll('input[name="channels[]"]:checked');
    if (channels.length === 0) {
        e.preventDefault();
        alert('Please select at least one channel (SMS, Email, or WhatsApp).');
    }
    
    const studentIds = this.querySelectorAll('input[name="student_ids[]"]');
    if (studentIds.length === 0) {
        e.preventDefault();
        alert('Please select at least one student.');
    }
});
</script>
@endsection
