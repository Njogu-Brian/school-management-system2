@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'Failed Payment Communications',
        'icon' => 'bi bi-exclamation-triangle',
        'subtitle' => 'Review and resend failed payment notifications',
        'actions' => '<a href="' . route('finance.payments.index') . '" class="btn btn-finance btn-finance-secondary"><i class="bi bi-arrow-left"></i> Back to Payments</a>'
    ])

    @include('finance.invoices.partials.alerts')

    <!-- Filters -->
    <div class="finance-filter-card finance-animate shadow-sm rounded-4 border-0 mb-4">
        <form method="GET" action="{{ route('finance.payments.failed-communications') }}" class="row g-3">
            <div class="col-md-3">
                <label class="finance-form-label">Date From</label>
                <input type="date" name="date_from" class="finance-form-input" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-3">
                <label class="finance-form-label">Date To</label>
                <input type="date" name="date_to" class="finance-form-input" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-3">
                <label class="finance-form-label">Channel</label>
                <select name="channel" class="finance-form-select">
                    <option value="">All Channels</option>
                    <option value="sms" {{ request('channel') == 'sms' ? 'selected' : '' }}>SMS</option>
                    <option value="email" {{ request('channel') == 'email' ? 'selected' : '' }}>Email</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="finance-form-label">Error Code</label>
                <select name="error_code" class="finance-form-select">
                    <option value="">All Error Codes</option>
                    @foreach($errorCodes as $code)
                        <option value="{{ $code }}" {{ request('error_code') == $code ? 'selected' : '' }}>{{ $code }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-12">
                <button type="submit" class="btn btn-finance btn-finance-primary">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                <a href="{{ route('finance.payments.failed-communications') }}" class="btn btn-finance btn-finance-outline">
                    <i class="bi bi-x-circle"></i> Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Bulk Actions -->
    @if($failedCommunications->count() > 0)
    <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mb-4">
        <div class="card-body">
            <form id="bulkResendForm" method="POST" action="{{ route('finance.payments.communications.resend-multiple') }}">
                @csrf
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <input type="checkbox" id="selectAll" class="form-check-input me-2">
                        <label for="selectAll" class="form-check-label">Select All</label>
                        <span class="ms-3 text-muted" id="selectedCount">0 selected</span>
                    </div>
                    <button type="submit" class="btn btn-finance btn-finance-primary" id="bulkResendBtn" disabled>
                        <i class="bi bi-send"></i> Resend Selected
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Failed Communications List -->
    <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
        <div class="card-body p-0">
            @if($failedCommunications->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="40">
                                    <input type="checkbox" id="selectAllTable" class="form-check-input">
                                </th>
                                <th>Date</th>
                                <th>Payment</th>
                                <th>Student</th>
                                <th>Parent</th>
                                <th>Channel</th>
                                <th>Contact</th>
                                <th>Error Code</th>
                                <th>Error Reason</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($failedCommunications as $communication)
                                <tr>
                                    <td>
                                        <input type="checkbox" name="communication_ids[]" value="{{ $communication->id }}" class="form-check-input communication-checkbox">
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $communication->created_at->format('d M Y H:i') }}</small>
                                    </td>
                                    <td>
                                        @if($communication->payment)
                                            <a href="{{ route('finance.payments.show', $communication->payment) }}" class="text-decoration-none">
                                                #{{ $communication->payment->receipt_number ?? $communication->payment->transaction_code }}
                                            </a>
                                            <br>
                                            <small class="text-muted">Ksh {{ number_format($communication->payment->amount, 2) }}</small>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($communication->payment && $communication->payment->student)
                                            {{ $communication->payment->student->full_name ?? 'N/A' }}
                                            <br>
                                            <small class="text-muted">{{ $communication->payment->student->admission_number }}</small>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($communication->payment && $communication->payment->student && $communication->payment->student->parent)
                                            {{ $communication->payment->student->parent->primary_contact_name ?? 
                                               $communication->payment->student->parent->father_name ?? 
                                               $communication->payment->student->parent->mother_name ?? 
                                               'N/A' }}
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $communication->channel == 'sms' ? 'info' : 'primary' }}">
                                            {{ strtoupper($communication->channel) }}
                                        </span>
                                    </td>
                                    <td>
                                        <small>{{ $communication->contact }}</small>
                                    </td>
                                    <td>
                                        @if($communication->error_code)
                                            <span class="badge bg-danger">{{ $communication->error_code }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($communication->response)
                                            @php
                                                $reason = data_get($communication->response, 'reason') ?? 
                                                         data_get($communication->response, 'message') ?? 
                                                         data_get($communication->response, 'error') ?? 
                                                         'Unknown error';
                                            @endphp
                                            <small class="text-danger" title="{{ $reason }}">
                                                {{ Str::limit($reason, 50) }}
                                            </small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <form action="{{ route('finance.payments.communications.resend', $communication) }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="channel" value="{{ $communication->channel }}">
                                            <button type="submit" class="btn btn-sm btn-finance btn-finance-primary" 
                                                    onclick="return confirm('Resend this communication?')">
                                                <i class="bi bi-send"></i> Resend
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="card-footer">
                    {{ $failedCommunications->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">No Failed Communications</h5>
                    <p class="text-muted">All payment communications have been sent successfully.</p>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('selectAll');
            const selectAllTable = document.getElementById('selectAllTable');
            const checkboxes = document.querySelectorAll('.communication-checkbox');
            const bulkResendBtn = document.getElementById('bulkResendBtn');
            const selectedCount = document.getElementById('selectedCount');
            const bulkResendForm = document.getElementById('bulkResendForm');

            function updateSelectedCount() {
                const checked = document.querySelectorAll('.communication-checkbox:checked');
                const count = checked.length;
                selectedCount.textContent = count + ' selected';
                bulkResendBtn.disabled = count === 0;
                
                // Update hidden inputs in form
                bulkResendForm.querySelectorAll('input[name="communication_ids[]"]').forEach(input => {
                    if (!input.classList.contains('communication-checkbox')) {
                        input.remove();
                    }
                });
                
                checked.forEach(checkbox => {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'communication_ids[]';
                    hiddenInput.value = checkbox.value;
                    bulkResendForm.appendChild(hiddenInput);
                });
            }

            function toggleAll(checked) {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = checked;
                });
                updateSelectedCount();
            }

            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    toggleAll(this.checked);
                });
            }

            if (selectAllTable) {
                selectAllTable.addEventListener('change', function() {
                    toggleAll(this.checked);
                    if (selectAll) selectAll.checked = this.checked;
                });
            }

            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSelectedCount);
            });

            updateSelectedCount();
        });
    </script>
    @endpush
@endsection

