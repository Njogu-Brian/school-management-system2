@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">
                        <i class="fas fa-paper-plane"></i> Bulk Send Payment Notifications
                    </h3>
                    <small>Real-time progress tracking</small>
                </div>

                <div class="card-body">
                    <!-- Progress Overview -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5 class="text-muted">Total</h5>
                                    <h2 id="total-count">{{ $totalPayments }}</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h5>Sent</h5>
                                    <h2 id="sent-count">0</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h5>Skipped</h5>
                                    <h2 id="skipped-count">0</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h5>Failed</h5>
                                    <h2 id="failed-count">0</h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mb-4">
                        <h5>Progress <span id="progress-percentage" class="float-right">0%</span></h5>
                        <div class="progress" style="height: 30px;">
                            <div id="progress-bar" 
                                 class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" 
                                 style="width: 0%">
                                <span id="progress-text">0 / {{ $totalPayments }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Status Message -->
                    <div id="status-container" class="alert alert-info">
                        <div class="d-flex align-items-center">
                            <div class="spinner-border spinner-border-sm mr-2" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                            <div id="status-message">Initializing bulk send...</div>
                        </div>
                    </div>

                    <!-- Current Payment Being Processed -->
                    <div id="current-payment-container" class="mb-3" style="display: none;">
                        <h5>Currently Processing:</h5>
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <strong>Receipt:</strong> <span id="current-receipt">-</span>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Student:</strong> <span id="current-student">-</span>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Amount:</strong> Ksh <span id="current-amount">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Channels -->
                    <div class="mb-3">
                        <strong>Sending via:</strong>
                        @foreach($channels as $channel)
                            <span class="badge badge-primary badge-lg">
                                <i class="fas fa-{{ $channel == 'whatsapp' ? 'whatsapp' : ($channel == 'email' ? 'envelope' : 'sms') }}"></i>
                                {{ ucfirst($channel) }}
                            </span>
                        @endforeach
                    </div>

                    <!-- Errors Section -->
                    <div id="errors-container" style="display: none;">
                        <h5 class="text-danger">
                            <i class="fas fa-exclamation-triangle"></i> Errors
                        </h5>
                        <div id="errors-list" class="alert alert-danger">
                            <ul id="error-items"></ul>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-4 text-center">
                        <a href="{{ route('finance.payments.index') }}" 
                           id="back-button" 
                           class="btn btn-secondary"
                           style="display: none;">
                            <i class="fas fa-arrow-left"></i> Back to Payments
                        </a>
                        <button id="retry-button" 
                                class="btn btn-warning" 
                                style="display: none;"
                                onclick="location.reload()">
                            <i class="fas fa-redo"></i> Retry
                        </button>
                    </div>

                    <!-- Completion Summary -->
                    <div id="completion-summary" class="mt-4" style="display: none;">
                        <div class="alert alert-success">
                            <h4 class="alert-heading">
                                <i class="fas fa-check-circle"></i> Bulk Send Completed!
                            </h4>
                            <hr>
                            <p class="mb-0" id="completion-message"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="tracking-id" value="{{ $trackingId }}">
<input type="hidden" id="csrf-token" value="{{ csrf_token() }}">

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const trackingId = document.getElementById('tracking-id').value;
    const csrfToken = document.getElementById('csrf-token').value;
    const totalPayments = {{ $totalPayments }};
    
    let pollingInterval = null;
    let isCompleted = false;

    // Start polling for progress
    function startPolling() {
        // Poll every 1 second
        pollingInterval = setInterval(checkProgress, 1000);
        
        // Also check immediately
        checkProgress();
    }

    // Check progress
    function checkProgress() {
        fetch(`{{ route('finance.payments.bulk-send-progress-check') }}?tracking_id=${trackingId}`, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                showError(data.message || 'Failed to fetch progress');
                stopPolling();
                return;
            }

            updateProgress(data);

            // Stop polling if completed or failed
            if (data.status === 'completed' || data.status === 'failed') {
                stopPolling();
                showCompletion(data);
            }
        })
        .catch(error => {
            console.error('Progress check error:', error);
            // Don't stop polling on network errors, just log them
        });
    }

    // Update progress UI
    function updateProgress(data) {
        const processed = data.processed || 0;
        const sent = data.sent || 0;
        const skipped = data.skipped || 0;
        const failed = data.failed || 0;
        const percentage = totalPayments > 0 ? Math.round((processed / totalPayments) * 100) : 0;

        // Update counts
        document.getElementById('sent-count').textContent = sent;
        document.getElementById('skipped-count').textContent = skipped;
        document.getElementById('failed-count').textContent = failed;

        // Update progress bar
        const progressBar = document.getElementById('progress-bar');
        progressBar.style.width = percentage + '%';
        progressBar.setAttribute('aria-valuenow', percentage);
        document.getElementById('progress-percentage').textContent = percentage + '%';
        document.getElementById('progress-text').textContent = processed + ' / ' + totalPayments;

        // Update status message
        let statusMessage = '';
        if (data.status === 'processing') {
            statusMessage = `Processing payments... ${processed} of ${totalPayments} completed`;
        } else if (data.status === 'completed') {
            statusMessage = 'Bulk send completed successfully!';
        } else if (data.status === 'failed') {
            statusMessage = 'Bulk send failed. Please check errors below.';
        }
        document.getElementById('status-message').textContent = statusMessage;

        // Update current payment info
        if (data.current_payment) {
            document.getElementById('current-payment-container').style.display = 'block';
            document.getElementById('current-receipt').textContent = data.current_payment.receipt || '-';
            document.getElementById('current-student').textContent = data.current_payment.student || '-';
            document.getElementById('current-amount').textContent = data.current_payment.amount || '-';
        } else {
            document.getElementById('current-payment-container').style.display = 'none';
        }

        // Update errors
        if (data.errors && data.errors.length > 0) {
            document.getElementById('errors-container').style.display = 'block';
            const errorItems = document.getElementById('error-items');
            errorItems.innerHTML = '';
            data.errors.forEach(error => {
                const li = document.createElement('li');
                li.textContent = error;
                errorItems.appendChild(li);
            });
        }

        // Change progress bar color based on status
        if (data.status === 'completed') {
            progressBar.classList.remove('progress-bar-animated');
            progressBar.classList.add('bg-success');
        } else if (data.status === 'failed') {
            progressBar.classList.remove('progress-bar-animated');
            progressBar.classList.add('bg-danger');
        }
    }

    // Show completion summary
    function showCompletion(data) {
        isCompleted = true;
        
        // Hide spinner in status
        const statusContainer = document.getElementById('status-container');
        if (data.status === 'completed') {
            statusContainer.classList.remove('alert-info');
            statusContainer.classList.add('alert-success');
            statusContainer.innerHTML = '<i class="fas fa-check-circle"></i> Bulk send completed successfully!';
        } else {
            statusContainer.classList.remove('alert-info');
            statusContainer.classList.add('alert-danger');
            statusContainer.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Bulk send failed. ' + (data.error_message || 'Please check the logs for details.');
        }

        // Show completion summary
        const completionMsg = `Successfully sent ${data.sent || 0} notification(s), skipped ${data.skipped || 0}, failed ${data.failed || 0}.`;
        document.getElementById('completion-message').textContent = completionMsg;
        document.getElementById('completion-summary').style.display = 'block';

        // Show back button
        document.getElementById('back-button').style.display = 'inline-block';
        
        // Show retry button if there were failures
        if ((data.failed || 0) > 0) {
            document.getElementById('retry-button').style.display = 'inline-block';
        }
    }

    // Stop polling
    function stopPolling() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
    }

    // Show error
    function showError(message) {
        const statusContainer = document.getElementById('status-container');
        statusContainer.classList.remove('alert-info');
        statusContainer.classList.add('alert-danger');
        statusContainer.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${message}`;
        
        document.getElementById('back-button').style.display = 'inline-block';
        document.getElementById('retry-button').style.display = 'inline-block';
    }

    // Start polling on page load
    startPolling();

    // Clean up on page unload
    window.addEventListener('beforeunload', function() {
        stopPolling();
    });

    // Auto-scroll to bottom for current payment updates
    setInterval(function() {
        if (!isCompleted) {
            const currentContainer = document.getElementById('current-payment-container');
            if (currentContainer.style.display !== 'none') {
                currentContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }
    }, 2000);
});
</script>
@endpush
@endsection

