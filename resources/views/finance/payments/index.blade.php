@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'Payments',
        'icon' => 'bi bi-cash-stack',
        'subtitle' => 'Track and manage all payment records',
        'actions' => '<a href="' . route('finance.payments.failed-communications') . '" class="btn btn-finance btn-finance-warning me-2"><i class="bi bi-exclamation-triangle"></i> Failed Communications</a><a href="' . route('finance.payments.create') . '" class="btn btn-finance btn-finance-primary"><i class="bi bi-plus-circle"></i> Record Payment</a>'
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
    @elseif(session('payment_id'))
        <script>
            window.addEventListener('load', function() {
                // Open receipt in popup window (not tab)
                const paymentId = {{ session('payment_id') }};
                const receiptUrl = '{{ route("finance.payments.receipt.view", ":id") }}'.replace(':id', paymentId);
                
                const popup = window.open(
                    receiptUrl,
                    'ReceiptWindow',
                    'width=800,height=900,scrollbars=yes,resizable=yes,toolbar=no,menubar=no,location=no,status=no'
                );
                
                if (!popup || popup.closed || typeof popup.closed == 'undefined') {
                    // Popup blocked - fallback to new tab
                    alert('Popup blocked. Please allow popups for this site to view receipt automatically.');
                    window.open(receiptUrl, '_blank');
                } else {
                    popup.focus();
                }
            });
        </script>
    @endif

    <!-- Filters -->
    <div class="finance-filter-card finance-animate shadow-sm rounded-4 border-0">
        <form method="GET" action="{{ route('finance.payments.index') }}" class="row g-3" id="paymentsFilterForm">
            <div class="col-md-6 col-lg-2">
                <label class="finance-form-label">Student</label>
                @include('partials.student_live_search', [
                    'hiddenInputId' => 'student_id',
                    'displayInputId' => 'studentFilterSearch',
                    'resultsId' => 'studentFilterResults',
                    'placeholder' => 'Type name or admission #',
                    'initialLabel' => request('student_id') ? optional(\App\Models\Student::find(request('student_id')))->search_display : ''
                ])
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="finance-form-label">Class</label>
                <select name="class_id" class="finance-form-select">
                    <option value="">All Classes</option>
                    @foreach($classrooms ?? [] as $classroom)
                        <option value="{{ $classroom->id }}" {{ request('class_id') == $classroom->id ? 'selected' : '' }}>
                            {{ $classroom->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="finance-form-label">Stream</label>
                <select name="stream_id" class="finance-form-select">
                    <option value="">All Streams</option>
                    @foreach($streams ?? [] as $stream)
                        <option value="{{ $stream->id }}" {{ request('stream_id') == $stream->id ? 'selected' : '' }}>
                            {{ $stream->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="finance-form-label">Payment Method</label>
                <select name="payment_method_id" class="finance-form-select">
                    <option value="">All Methods</option>
                    @foreach($paymentMethods ?? [] as $method)
                        <option value="{{ $method->id }}" {{ request('payment_method_id') == $method->id ? 'selected' : '' }}>
                            {{ $method->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="finance-form-label">Allocation</label>
                <select name="allocation_status" class="finance-form-select">
                    <option value="">All</option>
                    <option value="allocated" {{ request('allocation_status') == 'allocated' ? 'selected' : '' }}>Allocated</option>
                    <option value="unallocated" {{ request('allocation_status') == 'unallocated' ? 'selected' : '' }}>Unallocated</option>
                </select>
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="finance-form-label">Sort By</label>
                <select name="sort" class="finance-form-select">
                    <option value="payment_date_desc" {{ request('sort') == 'payment_date_desc' || !request('sort') ? 'selected' : '' }}>Newest</option>
                    <option value="amount_desc" {{ request('sort') == 'amount_desc' ? 'selected' : '' }}>Amount (High → Low)</option>
                </select>
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="finance-form-label">Receipt / Transaction Code</label>
                <input type="text" name="search" class="finance-form-control" value="{{ request('search') }}" placeholder="e.g. RCPT/2026-0524 or UA1SB5QUU8">
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="finance-form-label">From Date</label>
                <input type="date" name="from_date" class="finance-form-control" value="{{ request('from_date') }}">
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="finance-form-label">To Date</label>
                <input type="date" name="to_date" class="finance-form-control" value="{{ request('to_date') }}">
            </div>
            <div class="col-md-6 col-lg-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-finance btn-finance-primary flex-fill">
                    <i class="bi bi-search"></i> Filter
                </button>
                <button type="button" class="btn btn-finance btn-finance-secondary" id="bulkPrintBtn" title="Bulk Print Receipts">
                    <i class="bi bi-printer"></i> Bulk Print
                </button>
            </div>
        </form>
    </div>

    <!-- Bulk send toolbar -->
    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
        <div class="text-muted small">
            Select receipts to send via SMS / Email / WhatsApp, or use filters to bulk print.
            <span id="selectedCountBadge" class="badge bg-primary ms-2" style="display: none;">0 selected</span>
        </div>
        <div class="d-flex gap-2">
            <form action="{{ route('finance.payments.bulk-allocate-unallocated') }}" method="POST" class="d-inline" onsubmit="return confirm('This will allocate all unallocated payments to outstanding invoices. Continue?');">
                @csrf
                <button type="submit" class="btn btn-finance btn-finance-success" title="Auto-allocate all unallocated payments to outstanding invoices">
                    <i class="bi bi-arrow-repeat"></i> Allocate Unallocated Payments
                </button>
            </form>
            <a href="{{ route('finance.payments.failed-communications') }}" class="btn btn-finance btn-finance-warning" title="View and resend failed payment communications">
                <i class="bi bi-exclamation-triangle"></i> Failed Communications
            </a>
            <button type="button" class="btn btn-finance btn-finance-secondary" id="sendSelectedBtn">
                <i class="bi bi-send"></i> Send Selected (<span id="sendSelectedCount">0</span>)
            </button>
            <button type="button" class="btn btn-finance btn-finance-primary" id="bulkSendBtn"
                title="Send to all payments matching current filters (skips already sent)">
                <i class="bi bi-send-fill"></i> Bulk Send All
            </button>
            <button type="button" class="btn btn-finance btn-finance-outline" id="clearSelectionsBtn" title="Clear all selections">
                <i class="bi bi-x-circle"></i> Clear
            </button>
            <button type="button" class="btn btn-finance btn-finance-outline" id="bulkPrintBtn2" title="Bulk Print Receipts (uses current filters)">
                <i class="bi bi-printer"></i> Bulk Print
            </button>
            <a href="{{ route('finance.payments.create') }}" class="btn btn-finance btn-finance-primary"><i class="bi bi-plus-circle"></i> Record Payment</a>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="finance-table-wrapper finance-animate shadow-sm rounded-4 border-0">
        <div class="table-responsive px-3 pb-3">
            <table class="finance-table align-middle">
                <thead>
                        <tr>
                            <th style="width:32px;"><input type="checkbox" id="receiptCheckAll"></th>
                            <th>Receipt #</th>
                            <th>Student</th>
                            <th>Payment Date</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Allocated</th>
                            <th class="text-end">Unallocated</th>
                            <th>Payment Method</th>
                            <th>Transaction Code</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                        <tr class="{{ $payment->reversed ? 'table-secondary opacity-75' : '' }}">
                            <td>
                                <input type="checkbox" class="form-check-input receipt-checkbox" value="{{ $payment->id }}" {{ $payment->reversed ? 'disabled' : '' }}>
                            </td>
                            <td>
                                @if($payment->reversed)
                                    <del class="text-muted">
                                        <strong>{{ $payment->receipt_number ?? $payment->transaction_code }}</strong>
                                    </del>
                                @else
                                    <strong>{{ $payment->receipt_number ?? $payment->transaction_code }}</strong>
                                @endif
                            </td>
                            <td>
                                {{ $payment->student->full_name ?? 'N/A' }}
                                <br><small class="text-muted">{{ $payment->student->admission_number ?? 'N/A' }}</small>
                            </td>
                            <td>
                                {{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') : 'N/A' }}
                            </td>
                            <td class="text-end">
                                @if($payment->reversed)
                                    <del class="text-muted">
                                        <strong>Ksh {{ number_format($payment->amount, 2) }}</strong>
                                    </del>
                                @else
                                    <strong>Ksh {{ number_format($payment->amount, 2) }}</strong>
                                @endif
                            </td>
                            <td class="text-end">
                                <span class="text-success">Ksh {{ number_format($payment->allocated_amount ?? 0, 2) }}</span>
                            </td>
                            <td class="text-end">
                                @php
                                    $unallocated = $payment->unallocated_amount ?? ($payment->amount - ($payment->allocated_amount ?? 0));
                                @endphp
                                @if($unallocated > 0)
                                    <span class="text-warning">Ksh {{ number_format($unallocated, 2) }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                {{ $payment->paymentMethod->name ?? $payment->payment_method ?? 'N/A' }}
                            </td>
                            <td>
                                <small>{{ $payment->transaction_code ?? '—' }}</small>
                            </td>
                            <td>
                                @php
                                    // Use computed status from Payment model
                                    $status = $payment->status; // This uses the getStatusAttribute accessor
                                    $badgeClass = match($status) {
                                        'completed' => 'success',
                                        'partial' => 'warning',
                                        'unallocated' => 'info',
                                        'reversed' => 'danger',
                                        default => 'secondary'
                                    };
                                    $statusLabel = match($status) {
                                        'completed' => 'Completed',
                                        'partial' => 'Partial',
                                        'unallocated' => 'Unallocated',
                                        'reversed' => 'Reversed',
                                        default => ucfirst($status)
                                    };
                                @endphp
                                <span class="badge bg-{{ $badgeClass }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td>
                                <div class="finance-action-buttons">
                                    <a href="{{ route('finance.payments.show', $payment) }}" class="btn btn-sm btn-outline-primary" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('finance.payments.receipt.view', $payment) }}" 
                                       class="btn btn-sm btn-outline-secondary" 
                                       title="View/Print Receipt"
                                       onclick="window.open('{{ route('finance.payments.receipt.view', $payment) }}', 'ReceiptWindow', 'width=800,height=900,scrollbars=yes,resizable=yes,toolbar=no,menubar=no,location=no,status=no'); return false;">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-success" title="Send"
                                        onclick="openSendDocument('receipt', [{{ $payment->id }}])">
                                        <i class="bi bi-send"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10">
                                <div class="finance-empty-state">
                                    <div class="finance-empty-state-icon">
                                        <i class="bi bi-cash-stack"></i>
                                    </div>
                                    <h4>No payments found</h4>
                                    <p class="text-muted mb-3">Record your first payment to get started</p>
                                    <a href="{{ route('finance.payments.create') }}" class="btn btn-finance btn-finance-primary">
                                        <i class="bi bi-plus-circle"></i> Record First Payment
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($payments->isNotEmpty())
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="3" class="text-end">Totals:</th>
                            <th class="text-end">Ksh {{ number_format($payments->sum('amount'), 2) }}</th>
                            <th class="text-end">Ksh {{ number_format($payments->sum('allocated_amount'), 2) }}</th>
                            <th class="text-end">Ksh {{ number_format($payments->sum(function($p) { return $p->unallocated_amount ?? ($p->amount - ($p->allocated_amount ?? 0)); }), 2) }}</th>
                            <th colspan="4"></th>
                        </tr>
                    </tfoot>
                    @endif
                </table>
        </div>
        @if($payments->hasPages())
        <div class="finance-card-body" style="padding-top: 1rem; border-top: 1px solid #e5e7eb;">
            {{ $payments->links() }}
        </div>
        @endif
    </div>

@include('communication.partials.document-send-modal')

<!-- Bulk Send Modal -->
<div class="modal fade" id="bulkSendModal" tabindex="-1" aria-labelledby="bulkSendLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('finance.payments.bulk-send-preview') }}" id="bulkSendForm">
        @csrf
        <!-- Include current filter parameters -->
        <input type="hidden" name="student_id" value="{{ request('student_id') }}">
        <input type="hidden" name="class_id" value="{{ request('class_id') }}">
        <input type="hidden" name="stream_id" value="{{ request('stream_id') }}">
        <input type="hidden" name="payment_method_id" value="{{ request('payment_method_id') }}">
        <input type="hidden" name="from_date" value="{{ request('from_date') }}">
        <input type="hidden" name="to_date" value="{{ request('to_date') }}">
        
        <div class="modal-header">
          <h5 class="modal-title" id="bulkSendLabel">Bulk Send Payment Receipts</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> This will show you a <strong>preview</strong> of all payments matching your current filters. You'll be able to review which payments have already been sent and select which ones to send.
          </div>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label fw-semibold">Select Channels <span class="text-danger">*</span></label>
              <div class="d-flex gap-3">
                <div class="form-check">
                  <input class="form-check-input channel-checkbox" type="checkbox" name="channels[]" value="sms" id="bulkChannelSms" checked>
                  <label class="form-check-label" for="bulkChannelSms">
                    <i class="bi bi-chat-dots"></i> SMS
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input channel-checkbox" type="checkbox" name="channels[]" value="whatsapp" id="bulkChannelWhatsApp" checked>
                  <label class="form-check-label" for="bulkChannelWhatsApp">
                    <i class="bi bi-whatsapp"></i> WhatsApp
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input channel-checkbox" type="checkbox" name="channels[]" value="email" id="bulkChannelEmail" checked>
                  <label class="form-check-label" for="bulkChannelEmail">
                    <i class="bi bi-envelope"></i> Email
                  </label>
                </div>
              </div>
              <small class="text-muted d-block mt-2">Select one or more channels. You'll see which payments have already been sent and can choose which to send.</small>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ghost-strong" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-settings-primary">
            <i class="bi bi-eye-fill"></i> Preview & Select
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
// localStorage key for storing selected payment IDs
const SELECTED_PAYMENTS_KEY = 'selected_payment_ids';

// Get all selected payment IDs from localStorage
function getAllSelectedPaymentIds() {
    const stored = localStorage.getItem(SELECTED_PAYMENTS_KEY);
    if (!stored) return [];
    try {
        return JSON.parse(stored).map(id => parseInt(id)).filter(id => !isNaN(id));
    } catch (e) {
        return [];
    }
}

// Make it globally accessible
window.getAllSelectedPaymentIds = getAllSelectedPaymentIds;

// Save selected payment IDs to localStorage
function saveSelectedPaymentIds(ids) {
    const uniqueIds = [...new Set(ids.map(id => parseInt(id)).filter(id => !isNaN(id)))];
    localStorage.setItem(SELECTED_PAYMENTS_KEY, JSON.stringify(uniqueIds));
    updateSelectedCount();
}

// Add payment ID to selection
function addPaymentToSelection(paymentId) {
    const current = getAllSelectedPaymentIds();
    if (!current.includes(paymentId)) {
        current.push(paymentId);
        saveSelectedPaymentIds(current);
    }
}

// Remove payment ID from selection
function removePaymentFromSelection(paymentId) {
    const current = getAllSelectedPaymentIds();
    const filtered = current.filter(id => id !== paymentId);
    saveSelectedPaymentIds(filtered);
}

// Clear all selections
function clearAllSelections() {
    if (confirm('Clear all selected payments across all pages?')) {
        localStorage.removeItem(SELECTED_PAYMENTS_KEY);
        // Uncheck all checkboxes on current page
        document.querySelectorAll('.receipt-checkbox').forEach(cb => cb.checked = false);
        const checkAll = document.getElementById('receiptCheckAll');
        if (checkAll) {
            checkAll.checked = false;
        }
        updateSelectedCount();
    }
}

// Make it globally accessible
window.clearAllSelections = clearAllSelections;

// Update selected count display
function updateSelectedCount() {
    const selectedIds = getAllSelectedPaymentIds();
    const count = selectedIds.length;
    const badge = document.getElementById('selectedCountBadge');
    const sendCount = document.getElementById('sendSelectedCount');
    const sendBtn = document.getElementById('sendSelectedBtn');
    
    if (count > 0) {
        badge.textContent = count + ' selected';
        badge.style.display = 'inline-block';
        sendCount.textContent = count;
        sendBtn.disabled = false;
    } else {
        badge.style.display = 'none';
        sendCount.textContent = '0';
        sendBtn.disabled = true;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const checkAll = document.getElementById('receiptCheckAll');
    const boxes = document.querySelectorAll('.receipt-checkbox');
    const selectedIds = getAllSelectedPaymentIds();
    
    // Sync checkboxes with localStorage on page load
    boxes.forEach(box => {
        const paymentId = parseInt(box.value);
        if (selectedIds.includes(paymentId)) {
            box.checked = true;
        }
        
        // Update localStorage when checkbox changes
        box.addEventListener('change', function() {
            const paymentId = parseInt(this.value);
            if (this.checked) {
                addPaymentToSelection(paymentId);
            } else {
                removePaymentFromSelection(paymentId);
            }
            updateSelectAllState();
        });
    });
    
    // Update "Select All" state
    function updateSelectAllState() {
        if (checkAll && boxes.length > 0) {
            const allChecked = Array.from(boxes).every(b => b.checked);
            checkAll.checked = allChecked;
        }
    }
    
    // Handle "Select All" checkbox
    checkAll?.addEventListener('change', function() {
        boxes.forEach(box => {
            const paymentId = parseInt(box.value);
            box.checked = this.checked;
            if (this.checked) {
                addPaymentToSelection(paymentId);
            } else {
                removePaymentFromSelection(paymentId);
            }
        });
        updateSelectedCount();
    });
    
    updateSelectAllState();
    updateSelectedCount();
    
    // Initialize button state (selectedIds already declared above)
    const sendBtn = document.getElementById('sendSelectedBtn');
    if (sendBtn && selectedIds.length > 0) {
        sendBtn.disabled = false;
    }
});

function bulkPrintReceipts() {
    try {
        const form = document.getElementById('paymentsFilterForm');
        if (!form) {
            console.error('Payments filter form not found');
            alert('Filter form not found. Please refresh the page.');
            return;
        }
        
        const formData = new FormData(form);
        
        // Get selected payment IDs from localStorage (all pages)
        const selectedIds = getAllSelectedPaymentIds();
        
        // Also check current page checkboxes as fallback
        const checkedIds = Array.from(document.querySelectorAll('.receipt-checkbox:checked'))
            .map(cb => parseInt(cb.value));
        
        // Combine both sources
        const allIds = [...new Set([...selectedIds, ...checkedIds])];
        
        if (allIds.length > 0) {
            formData.append('payment_ids', allIds.join(','));
        }
        
        // Build query string
        const params = new URLSearchParams();
        for (const [key, value] of formData.entries()) {
            if (value) {
                params.append(key, value);
            }
        }
        
        // Open bulk print in new window
        const url = '{{ route("finance.payments.bulk-print") }}?' + params.toString();
        const printWindow = window.open(url, 'BulkReceiptPrint', 'width=800,height=900,scrollbars=yes,resizable=yes,toolbar=no,menubar=no,location=no,status=no');
        
        if (!printWindow || printWindow.closed || typeof printWindow.closed == 'undefined') {
            alert('Popup blocked. Please allow popups for this site to print receipts.');
        }
    } catch (error) {
        console.error('Error in bulkPrintReceipts:', error);
        alert('Error opening bulk print: ' + error.message);
    }
}

// Make it globally accessible
window.bulkPrintReceipts = bulkPrintReceipts;

function collectCheckedIds(selector) {
    // Use localStorage instead of just current page
    return getAllSelectedPaymentIds();
}

// Bulk Send Modal - Make it globally accessible
function openBulkSendModal() {
    try {
        const modalEl = document.getElementById('bulkSendModal');
        if (!modalEl) {
            console.error('Bulk send modal not found');
            alert('Bulk send modal not found. Please refresh the page.');
            return;
        }

        // Check if Bootstrap is available
        if (typeof bootstrap === 'undefined') {
            console.error('Bootstrap is not loaded');
            alert('Bootstrap is not loaded. Please refresh the page.');
            return;
        }

        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    } catch (error) {
        console.error('Error opening bulk send modal:', error);
        alert('Error opening bulk send modal: ' + error.message);
    }
}

// Also make it available on window for backwards compatibility
window.openBulkSendModal = openBulkSendModal;

// Simple button initialization using onclick (more reliable than addEventListener)
(function initPaymentButtons() {
    function setupButtons() {
        // Bulk Send All
        const bulkSendBtn = document.getElementById('bulkSendBtn');
        if (bulkSendBtn) bulkSendBtn.onclick = () => { openBulkSendModal(); };

        // Bulk Print buttons
        ['bulkPrintBtn', 'bulkPrintBtn2'].forEach(id => {
            const btn = document.getElementById(id);
            if (btn) btn.onclick = () => { bulkPrintReceipts(); };
        });

        // Send Selected
        const sendSelectedBtn = document.getElementById('sendSelectedBtn');
        if (sendSelectedBtn) {
            sendSelectedBtn.onclick = function() {
                const ids = getAllSelectedPaymentIds();
                if (ids.length === 0) {
                    alert('Please select at least one payment to send.');
                    return;
                }
                // Wait for openSendDocument
                let attempts = 0;
                function tryOpen() {
                    if (window.openSendDocument) {
                        window.openSendDocument('receipt', ids);
                    } else if (attempts++ < 20) {
                        setTimeout(tryOpen, 100);
                    } else {
                        alert('Send function not available. Please refresh the page.');
                    }
                }
                tryOpen();
            };
        }

        // Clear Selections
        const clearBtn = document.getElementById('clearSelectionsBtn');
        if (clearBtn) clearBtn.onclick = () => { clearAllSelections(); };

        // Bulk send form validation
        const bulkForm = document.getElementById('bulkSendForm');
        if (bulkForm) {
            bulkForm.onsubmit = function(e) {
                const checked = Array.from(this.querySelectorAll('.channel-checkbox')).some(c => c.checked);
                if (!checked) {
                    e.preventDefault();
                    alert('Please select at least one channel (SMS, WhatsApp, or Email)');
                    return false;
                }
                const btn = this.querySelector('button[type="submit"]');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending...';
                    setTimeout(() => { btn.disabled = false; btn.innerHTML = 'Send to All'; }, 10000);
                }
            };
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => setTimeout(setupButtons, 300));
    } else {
        setTimeout(setupButtons, 300);
    }
})();
</script>
@endpush
@endsection
