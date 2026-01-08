@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">
                        <i class="fas fa-paper-plane"></i> Bulk Send Preview
                    </h3>
                    <small>Review and select payments to send notifications</small>
                </div>

                <div class="card-body">
                    <!-- Summary -->
                    <div class="alert alert-info">
                        <div class="row">
                            <div class="col-md-3">
                                <strong>Selected Channels:</strong>
                                @foreach($channels as $channel)
                                    <span class="badge badge-primary">{{ ucfirst($channel) }}</span>
                                @endforeach
                            </div>
                            <div class="col-md-3">
                                <strong>To Send:</strong> <span class="text-success">{{ $toSendCount }}</span>
                            </div>
                            <div class="col-md-3">
                                <strong>Already Sent:</strong> <span class="text-warning">{{ $toSkipCount }}</span>
                            </div>
                            <div class="col-md-3">
                                <strong>No Parent Contact:</strong> <span class="text-danger">{{ $noParentCount }}</span>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('finance.payments.bulk-send') }}" method="POST" id="bulkSendForm">
                        @csrf
                        
                        <!-- Hidden fields for channels and filters -->
                        @foreach($channels as $channel)
                            <input type="hidden" name="channels[]" value="{{ $channel }}">
                        @endforeach
                        
                        @foreach($filters as $key => $value)
                            @if($value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach

                        <!-- Actions -->
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="selectAll">
                                    <i class="fas fa-check-square"></i> Select All To Send
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAll">
                                    <i class="fas fa-square"></i> Deselect All
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-info" id="selectNotSent">
                                    <i class="fas fa-check"></i> Select Only Not Sent
                                </button>
                            </div>
                            <div>
                                <a href="{{ route('finance.payments.index', $filters) }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-success" id="sendBtn">
                                    <i class="fas fa-paper-plane"></i> Send Selected (<span id="selectedCount">0</span>)
                                </button>
                            </div>
                        </div>

                        <!-- Payments Table -->
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th width="40">
                                            <input type="checkbox" id="selectAllCheckbox">
                                        </th>
                                        <th>Receipt #</th>
                                        <th>Student</th>
                                        <th>Class</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th>Channels Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($payments as $item)
                                        @php
                                            $payment = $item['payment'];
                                            $alreadySent = $item['already_sent_channels'];
                                            $willBeSkipped = $item['will_be_skipped'];
                                            $hasParent = $item['has_parent'];
                                            
                                            // Determine row class
                                            $rowClass = '';
                                            $disabled = '';
                                            $checked = '';
                                            
                                            if (!$hasParent) {
                                                $rowClass = 'table-danger';
                                                $disabled = 'disabled';
                                                $statusText = 'No Parent Contact';
                                                $statusClass = 'danger';
                                            } elseif ($willBeSkipped) {
                                                $rowClass = 'table-warning';
                                                $statusText = 'Already Sent (All Channels)';
                                                $statusClass = 'warning';
                                            } else {
                                                $rowClass = '';
                                                $checked = 'checked';
                                                $statusText = 'Ready to Send';
                                                $statusClass = 'success';
                                            }
                                        @endphp
                                        <tr class="{{ $rowClass }}">
                                            <td>
                                                <input 
                                                    type="checkbox" 
                                                    name="payment_ids[]" 
                                                    value="{{ $payment->id }}" 
                                                    class="payment-checkbox"
                                                    data-status="{{ $statusText }}"
                                                    {{ $checked }}
                                                    {{ $disabled }}
                                                >
                                            </td>
                                            <td>
                                                <a href="{{ route('finance.receipts.view', $payment->id) }}" target="_blank">
                                                    {{ $payment->receipt_number }}
                                                </a>
                                            </td>
                                            <td>
                                                @if($payment->student)
                                                    {{ $payment->student->full_name }}
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($payment->student && $payment->student->classroom)
                                                    {{ $payment->student->classroom->name }}
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>KES {{ number_format($payment->amount, 2) }}</td>
                                            <td>{{ $payment->payment_date->format('d M Y') }}</td>
                                            <td>
                                                @if($payment->paymentMethod)
                                                    {{ $payment->paymentMethod->name }}
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ $statusClass }}">
                                                    {{ $statusText }}
                                                </span>
                                            </td>
                                            <td>
                                                @if(!empty($alreadySent))
                                                    @foreach($channels as $channel)
                                                        @if(isset($alreadySent[$channel]))
                                                            <span class="badge badge-warning" title="Already sent via {{ $alreadySent[$channel] }}">
                                                                <i class="fas fa-check"></i> {{ ucfirst($channel) }}
                                                            </span>
                                                        @else
                                                            <span class="badge badge-light">
                                                                <i class="fas fa-clock"></i> {{ ucfirst($channel) }}
                                                            </span>
                                                        @endif
                                                    @endforeach
                                                @else
                                                    @foreach($channels as $channel)
                                                        <span class="badge badge-light">
                                                            <i class="fas fa-clock"></i> {{ ucfirst($channel) }}
                                                        </span>
                                                    @endforeach
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted">
                                                No payments found matching the criteria
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('bulkSendForm');
    const checkboxes = document.querySelectorAll('.payment-checkbox:not([disabled])');
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const selectedCountSpan = document.getElementById('selectedCount');
    const sendBtn = document.getElementById('sendBtn');
    
    // Update selected count
    function updateSelectedCount() {
        const count = document.querySelectorAll('.payment-checkbox:checked').length;
        selectedCountSpan.textContent = count;
        sendBtn.disabled = count === 0;
    }
    
    // Select All
    document.getElementById('selectAll').addEventListener('click', function() {
        checkboxes.forEach(cb => cb.checked = true);
        selectAllCheckbox.checked = true;
        updateSelectedCount();
    });
    
    // Deselect All
    document.getElementById('deselectAll').addEventListener('click', function() {
        checkboxes.forEach(cb => cb.checked = false);
        selectAllCheckbox.checked = false;
        updateSelectedCount();
    });
    
    // Select Only Not Sent
    document.getElementById('selectNotSent').addEventListener('click', function() {
        checkboxes.forEach(cb => {
            if (cb.dataset.status === 'Ready to Send') {
                cb.checked = true;
            } else {
                cb.checked = false;
            }
        });
        updateSelectedCount();
    });
    
    // Select all checkbox
    selectAllCheckbox.addEventListener('change', function() {
        checkboxes.forEach(cb => cb.checked = this.checked);
        updateSelectedCount();
    });
    
    // Individual checkboxes
    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateSelectedCount);
    });
    
    // Form submit confirmation
    form.addEventListener('submit', function(e) {
        const count = document.querySelectorAll('.payment-checkbox:checked').length;
        if (count === 0) {
            e.preventDefault();
            alert('Please select at least one payment to send.');
            return false;
        }
        
        if (!confirm(`Are you sure you want to send notifications to ${count} payment(s)?`)) {
            e.preventDefault();
            return false;
        }
    });
    
    // Initial count
    updateSelectedCount();
});
</script>
@endpush
@endsection

