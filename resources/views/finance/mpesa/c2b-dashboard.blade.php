@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'M-PESA Paybill Transactions',
        'icon' => 'bi bi-cash-coin',
        'subtitle' => 'Real-time paybill transactions with smart student matching',
        'actions' => '<a href="' . route('finance.mpesa.c2b.transactions') . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-list"></i> View All Transactions</a><a href="' . route('finance.mpesa.dashboard') . '" class="btn btn-finance btn-finance-secondary"><i class="bi bi-arrow-left"></i> Back</a>'
    ])

    <!-- Stats Row -->
    <div class="row g-4 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="finance-stat-card">
                <div class="finance-stat-icon bg-success">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div class="finance-stat-content">
                    <div class="finance-stat-value" id="todayCount">{{ $stats['today_count'] }}</div>
                    <div class="finance-stat-label">Today's Transactions</div>
                    <div class="finance-stat-meta">KES {{ number_format($stats['today_amount'], 2) }}</div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="finance-stat-card">
                <div class="finance-stat-icon bg-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div class="finance-stat-content">
                    <div class="finance-stat-value" id="unallocatedCount">{{ $stats['unallocated_count'] }}</div>
                    <div class="finance-stat-label">Pending Allocation</div>
                    <div class="finance-stat-meta">KES {{ number_format($stats['unallocated_amount'], 2) }}</div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="finance-stat-card">
                <div class="finance-stat-icon bg-primary">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="finance-stat-content">
                    <div class="finance-stat-value" id="autoMatchedCount">{{ $stats['auto_matched_count'] }}</div>
                    <div class="finance-stat-label">Auto-Matched Today</div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="finance-stat-card">
                <div class="finance-stat-icon bg-danger">
                    <i class="bi bi-files"></i>
                </div>
                <div class="finance-stat-content">
                    <div class="finance-stat-value" id="duplicatesCount">{{ $stats['duplicates_count'] }}</div>
                    <div class="finance-stat-label">Duplicates Detected</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Real-time Updates Indicator -->
    <div class="alert alert-info border-0 mb-4 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <div class="spinner-border spinner-border-sm me-3" id="refreshSpinner" style="display: none;"></div>
            <div>
                <i class="bi bi-broadcast me-2"></i>
                <strong>Live Updates Active</strong>
                <span class="ms-2 text-muted">Last updated: <span id="lastUpdate">just now</span></span>
            </div>
        </div>
        <button class="btn btn-sm btn-outline-primary" onclick="forceRefresh()">
            <i class="bi bi-arrow-clockwise"></i> Refresh Now
        </button>
    </div>

    <!-- Unallocated Transactions -->
    <div class="finance-card finance-animate">
        <div class="finance-card-header">
            <h5 class="finance-card-title">
                <i class="bi bi-hourglass-split me-2"></i>
                Unallocated Transactions
                <span class="badge bg-warning ms-2" id="unallocatedBadge">{{ $unallocatedTransactions->total() }}</span>
            </h5>
        </div>
        <div class="finance-card-body">
            @if($unallocatedTransactions->isEmpty())
                <div class="text-center text-muted py-5">
                    <i class="bi bi-check-circle fs-1"></i>
                    <p class="mt-3 mb-0">No unallocated transactions</p>
                    <small>All transactions have been allocated to students</small>
                </div>
            @else
                <div class="finance-table-wrapper">
                    <table class="finance-table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Amount</th>
                                <th>Payer</th>
                                <th>Phone</th>
                                <th>Reference</th>
                                <th>Match Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="transactionsTableBody">
                            @foreach($unallocatedTransactions as $transaction)
                                <tr data-transaction-id="{{ $transaction->id }}">
                                    <td>
                                        <div class="text-muted small">{{ $transaction->trans_time->format('d M Y') }}</div>
                                        <div><strong>{{ $transaction->trans_time->format('H:i:s') }}</strong></div>
                                    </td>
                                    <td>
                                        <strong class="text-success">KES {{ number_format($transaction->trans_amount, 2) }}</strong>
                                    </td>
                                    <td>
                                        <div>{{ $transaction->full_name }}</div>
                                        @if($transaction->student)
                                            <small class="text-muted">Matched: {{ $transaction->student->first_name }} {{ $transaction->student->last_name }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $transaction->formatted_phone }}</td>
                                    <td>
                                        <code>{{ $transaction->bill_ref_number ?? '-' }}</code>
                                    </td>
                                    <td>
                                        @if($transaction->match_confidence && $transaction->match_confidence >= 80)
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle"></i> {{ $transaction->match_confidence }}% Match
                                            </span>
                                        @elseif($transaction->match_confidence && $transaction->match_confidence >= 60)
                                            <span class="badge bg-warning">
                                                <i class="bi bi-exclamation-triangle"></i> {{ $transaction->match_confidence }}% Match
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">
                                                <i class="bi bi-question-circle"></i> No Match
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('finance.mpesa.c2b.transaction.show', $transaction->id) }}" 
                                           class="btn btn-sm btn-finance btn-finance-primary">
                                            <i class="bi bi-arrow-right-circle"></i> Allocate
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $unallocatedTransactions->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

@section('js')
<script>
let lastUpdateTime = new Date();
let pollInterval;

$(document).ready(function() {
    // Start polling for new transactions every 10 seconds
    startPolling();
    
    // Update "last updated" timestamp every second
    setInterval(updateLastUpdateText, 1000);
});

function startPolling() {
    pollInterval = setInterval(checkForNewTransactions, 10000); // 10 seconds
}

function checkForNewTransactions() {
    $('#refreshSpinner').show();
    
    $.ajax({
        url: '/api/finance/mpesa/c2b/latest',
        method: 'GET',
        data: {
            since: lastUpdateTime.toISOString()
        },
        success: function(response) {
            // Handle both array and object responses
            let transactions = Array.isArray(response) ? response : (response.transactions || []);
            
            if (response.error) {
                console.error('C2B API Error:', response.message || response.error);
                if (response.message && response.message.includes('migrations')) {
                    showNotification('⚠️ Database migration required. Please contact administrator.', 'warning');
                }
            } else if (transactions.length > 0) {
                // Update stats
                updateStats();
                
                // Prepend new transactions
                prependNewTransactions(transactions);
                
                // Show notification
                showNotification(transactions.length + ' new transaction(s) received');
                
                lastUpdateTime = new Date();
            }
            $('#refreshSpinner').hide();
        },
        error: function(xhr, status, error) {
            console.error('C2B Polling Error:', {
                status: status,
                error: error,
                response: xhr.responseText
            });
            $('#refreshSpinner').hide();
            
            // Show error notification only if it's not a network error
            if (xhr.status !== 0) {
                showNotification('⚠️ Failed to fetch transactions. Check console for details.', 'danger');
            }
        }
    });
}

function updateStats() {
    // Reload stats via AJAX
    $.get(window.location.href, function(html) {
        // Extract and update stat values
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        
        $('#todayCount').text($(doc).find('#todayCount').text());
        $('#unallocatedCount').text($(doc).find('#unallocatedCount').text());
        $('#autoMatchedCount').text($(doc).find('#autoMatchedCount').text());
        $('#duplicatesCount').text($(doc).find('#duplicatesCount').text());
        $('#unallocatedBadge').text($(doc).find('#unallocatedBadge').text());
    });
}

function prependNewTransactions(transactions) {
    const tbody = $('#transactionsTableBody');
    
    transactions.forEach(function(txn) {
        // Check if transaction already exists
        if ($(`tr[data-transaction-id="${txn.id}"]`).length > 0) {
            return;
        }
        
        let matchBadge = '';
        if (txn.match_confidence >= 80) {
            matchBadge = `<span class="badge bg-success"><i class="bi bi-check-circle"></i> ${txn.match_confidence}% Match</span>`;
        } else if (txn.match_confidence >= 60) {
            matchBadge = `<span class="badge bg-warning"><i class="bi bi-exclamation-triangle"></i> ${txn.match_confidence}% Match</span>`;
        } else {
            matchBadge = '<span class="badge bg-secondary"><i class="bi bi-question-circle"></i> No Match</span>';
        }
        
        const row = `
            <tr data-transaction-id="${txn.id}" class="table-success" style="animation: highlight 2s;">
                <td>
                    <div class="text-muted small">${new Date(txn.trans_time).toLocaleDateString()}</div>
                    <div><strong>${new Date(txn.trans_time).toLocaleTimeString()}</strong></div>
                </td>
                <td><strong class="text-success">KES ${txn.amount}</strong></td>
                <td>
                    <div>${txn.payer_name}</div>
                    ${txn.student_name ? `<small class="text-muted">Matched: ${txn.student_name}</small>` : ''}
                </td>
                <td>${txn.phone}</td>
                <td><code>${txn.reference || '-'}</code></td>
                <td>${matchBadge}</td>
                <td>
                    <a href="/finance/mpesa/c2b/transactions/${txn.id}" class="btn btn-sm btn-finance btn-finance-primary">
                        <i class="bi bi-arrow-right-circle"></i> Allocate
                    </a>
                </td>
            </tr>
        `;
        
        tbody.prepend(row);
        
        // Remove highlight after animation
        setTimeout(function() {
            $(`tr[data-transaction-id="${txn.id}"]`).removeClass('table-success');
        }, 2000);
    });
}

function showNotification(message, type = 'primary') {
    // Map type to Bootstrap classes
    const typeClasses = {
        'primary': 'bg-primary',
        'success': 'bg-success',
        'warning': 'bg-warning',
        'danger': 'bg-danger',
        'info': 'bg-info'
    };
    
    const bgClass = typeClasses[type] || typeClasses['primary'];
    
    // Create a toast notification
    const toast = $(`
        <div class="toast align-items-center text-white ${bgClass} border-0" role="alert" style="position: fixed; top: 80px; right: 20px; z-index: 9999;">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-bell-fill me-2"></i>${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `);
    
    $('body').append(toast);
    const bsToast = new bootstrap.Toast(toast[0]);
    bsToast.show();
    
    // Play sound notification (optional)
    try {
        const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIGGi77eiaUxELTqLZ7K5bFApNn9vuvmwhBSuBzvLZiTYIGGi77eiaUxELTqLZ7K5bFApNn9vuvmwhBSuBzvLZiTYIGGi77eiaUxELTqLZ7K5bFApNn9vuvmwhBSuBzvLZiTYIGGi77eiaUxELTqLZ7K5bFApNn9vuvmwhBSuBzvLZiTYIGGi77eiaUxELTqLZ7K5bFApNn9vuvmwhBSuBzvLZiTYIGGi77eiaUxELTqLZ7K5bFApNn9vuvmwhBSuBzvLZiTYIGGi77eiaUxELTqLZ7K5bFApNn9vuvmwhBSuBzvLZiTYIGGi77eiaUxELTqLZ7K5bFA==');
        audio.play();
    } catch(e) {
        // Ignore if audio fails
    }
}

function updateLastUpdateText() {
    const seconds = Math.floor((new Date() - lastUpdateTime) / 1000);
    let text = '';
    
    if (seconds < 10) {
        text = 'just now';
    } else if (seconds < 60) {
        text = seconds + ' seconds ago';
    } else {
        const minutes = Math.floor(seconds / 60);
        text = minutes + ' minute' + (minutes > 1 ? 's' : '') + ' ago';
    }
    
    $('#lastUpdate').text(text);
}

function forceRefresh() {
    checkForNewTransactions();
}

// Add highlight animation
$('</head>').before(`
    <style>
        @keyframes highlight {
            0% { background-color: #d1e7dd; }
            100% { background-color: transparent; }
        }
    </style>
`);
</script>
@endsection

