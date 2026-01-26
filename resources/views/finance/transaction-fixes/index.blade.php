@extends('layouts.app')

@php
    use Illuminate\Support\Str;
@endphp

@section('title', 'Transaction Fixes Audit')

@section('content')
<style>
    .table tbody tr {
        border-bottom: 1px solid #dee2e6;
    }
    .table tbody tr:hover {
        background-color: #f8f9fa !important;
    }
    .table td {
        vertical-align: middle;
        color: #333 !important;
    }
    .table th {
        background-color: #343a40 !important;
        color: #fff !important;
        font-weight: 600;
        border: none;
    }
    .badge {
        font-weight: 500;
    }
</style>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Transaction Fixes Audit Log</h4>
                    <div>
                        <a href="{{ route('finance.transaction-fixes.export') }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-download"></i> Export
                        </a>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="card-body border-bottom">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5>Total Changes</h5>
                                    <h3>{{ number_format($stats['total']) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5>Applied</h5>
                                    <h3>{{ number_format($stats['applied']) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h5>Reversed</h5>
                                    <h3>{{ number_format($stats['reversed']) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5>Pending</h5>
                                    <h3>{{ number_format($stats['pending']) }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- By Type -->
                    <div class="mt-3">
                        <h6>By Fix Type:</h6>
                        <div class="row">
                            @foreach($stats['by_type'] as $type => $count)
                            <div class="col-md-2">
                                <span class="badge badge-secondary">{{ $type }}: {{ $count }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card-body border-bottom">
                    <form method="GET" action="{{ route('finance.transaction-fixes.index') }}">
                        <div class="row">
                            <div class="col-md-3">
                                <select name="fix_type" class="form-control">
                                    <option value="">All Fix Types</option>
                                    <option value="reset_reversed_payment" {{ request('fix_type') == 'reset_reversed_payment' ? 'selected' : '' }}>Reset Reversed Payment</option>
                                    <option value="reset_confirmed_no_payment" {{ request('fix_type') == 'reset_confirmed_no_payment' ? 'selected' : '' }}>Reset Confirmed No Payment</option>
                                    <option value="fix_swimming" {{ request('fix_type') == 'fix_swimming' ? 'selected' : '' }}>Fix Swimming</option>
                                    <option value="link_payment" {{ request('fix_type') == 'link_payment' ? 'selected' : '' }}>Link Payment</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="entity_type" class="form-control">
                                    <option value="">All Entity Types</option>
                                    <option value="bank_statement_transaction" {{ request('entity_type') == 'bank_statement_transaction' ? 'selected' : '' }}>Bank Statement</option>
                                    <option value="mpesa_c2b_transaction" {{ request('entity_type') == 'mpesa_c2b_transaction' ? 'selected' : '' }}>C2B Transaction</option>
                                    <option value="payment" {{ request('entity_type') == 'payment' ? 'selected' : '' }}>Payment</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="applied" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="1" {{ request('applied') == '1' ? 'selected' : '' }}>Applied</option>
                                    <option value="0" {{ request('applied') == '0' ? 'selected' : '' }}>Pending</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="reversed" class="form-control">
                                    <option value="">Reversed?</option>
                                    <option value="1" {{ request('reversed') == '1' ? 'selected' : '' }}>Yes</option>
                                    <option value="0" {{ request('reversed') == '0' ? 'selected' : '' }}>No</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary btn-block">Filter</button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Table -->
                <div class="card-body">
                    <form id="bulkReverseForm" method="POST" action="{{ route('finance.transaction-fixes.bulk-reverse') }}">
                        @csrf
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" style="font-size: 13px;">
                                <thead class="thead-dark">
                                    <tr>
                                        <th><input type="checkbox" id="selectAll"></th>
                                        <th>ID</th>
                                        <th>Fix Type</th>
                                        <th>Entity</th>
                                        <th>Transaction</th>
                                        <th>Before (Old Values)</th>
                                        <th>After (New Values)</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        <th>Applied At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($audits as $audit)
                                    <tr style="background-color: #fff;">
                                        <td>
                                            @if($audit->applied && !$audit->reversed)
                                            <input type="checkbox" name="audit_ids[]" value="{{ $audit->id }}">
                                            @endif
                                        </td>
                                        <td style="color: #333; font-weight: 600;">{{ $audit->id }}</td>
                                        <td><span class="badge badge-info" style="font-size: 11px; padding: 4px 8px;">{{ $audit->fix_type }}</span></td>
                                        <td><span class="badge badge-secondary" style="font-size: 11px; padding: 4px 8px;">{{ $audit->entity_type }}</span></td>
                                        <td style="color: #333;">
                                            @if($audit->entity_type === 'bank_statement_transaction' || $audit->entity_type === 'mpesa_c2b_transaction')
                                                <a href="{{ route('finance.bank-statements.show', $audit->entity_id) }}" class="text-primary font-weight-bold" style="text-decoration: underline;">
                                                    #{{ $audit->entity_id }}
                                                </a>
                                            @else
                                                <span style="color: #333; font-weight: 600;">{{ $audit->entity_id }}</span>
                                            @endif
                                        </td>
                                        <td style="color: #333; background-color: #f8f9fa; padding: 8px;">
                                            @foreach($audit->old_values ?? [] as $key => $value)
                                            <div style="margin-bottom: 6px; color: #495057;">
                                                @if($key === 'payment_id' && $value)
                                                    @php
                                                        $payment = \App\Models\Payment::find($value);
                                                    @endphp
                                                    @if($payment)
                                                        <a href="{{ route('finance.payments.show', $payment->id) }}" class="text-primary font-weight-bold" style="text-decoration: underline;" title="View Payment">
                                                            <i class="fas fa-receipt"></i> {{ $payment->receipt_number ?? 'N/A' }}
                                                        </a>
                                                        <div style="color: #6c757d; font-size: 11px; margin-top: 2px;">
                                                            {{ $payment->narration ?? 'No narration' }}
                                                        </div>
                                                        <div style="color: #6c757d; font-size: 11px;">
                                                            <i class="fas fa-calendar"></i> {{ $payment->payment_date ? $payment->payment_date->format('Y-m-d') : 'No date' }}
                                                        </div>
                                                    @else
                                                        <span style="color: #dc3545;">Payment ID: {{ $value }} (Deleted)</span>
                                                    @endif
                                                @else
                                                    <strong style="color: #212529;">{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> 
                                                    <span style="color: #495057;">{{ is_array($value) ? json_encode($value) : ($value ?? 'NULL') }}</span>
                                                @endif
                                            </div>
                                            @endforeach
                                            @if(empty($audit->old_values))
                                                <span style="color: #6c757d; font-style: italic;">No old values</span>
                                            @endif
                                        </td>
                                        <td style="color: #333; background-color: #e8f5e9; padding: 8px;">
                                            @foreach($audit->new_values ?? [] as $key => $value)
                                            <div style="margin-bottom: 6px; color: #495057;">
                                                @if($key === 'payment_id' && $value)
                                                    @php
                                                        $payment = \App\Models\Payment::find($value);
                                                    @endphp
                                                    @if($payment)
                                                        <a href="{{ route('finance.payments.show', $payment->id) }}" class="text-success font-weight-bold" style="text-decoration: underline;" title="View Payment">
                                                            <i class="fas fa-receipt"></i> {{ $payment->receipt_number ?? 'N/A' }}
                                                        </a>
                                                        <div style="color: #6c757d; font-size: 11px; margin-top: 2px;">
                                                            {{ $payment->narration ?? 'No narration' }}
                                                        </div>
                                                        <div style="color: #6c757d; font-size: 11px;">
                                                            <i class="fas fa-calendar"></i> {{ $payment->payment_date ? $payment->payment_date->format('Y-m-d') : 'No date' }}
                                                        </div>
                                                    @else
                                                        <span style="color: #dc3545;">Payment ID: {{ $value }} (Not Found)</span>
                                                    @endif
                                                @elseif($key === 'payment_created')
                                                    <strong style="color: #212529;">Payment Created:</strong> 
                                                    <span style="color: {{ $value ? '#28a745' : '#dc3545' }}; font-weight: 600;">{{ $value ? 'Yes' : 'No' }}</span>
                                                @else
                                                    <strong style="color: #212529;">{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> 
                                                    <span style="color: #495057;">{{ is_array($value) ? json_encode($value) : ($value ?? 'NULL') }}</span>
                                                @endif
                                            </div>
                                            @endforeach
                                            @if(empty($audit->new_values))
                                                <span style="color: #6c757d; font-style: italic;">No new values</span>
                                            @endif
                                        </td>
                                        <td style="color: #333; max-width: 250px;">
                                            <div class="text-wrap" style="color: #495057; line-height: 1.4;">
                                                {{ $audit->reason }}
                                            </div>
                                        </td>
                                        <td>
                                            @if($audit->reversed)
                                            <span class="badge badge-warning" style="font-size: 11px; padding: 5px 10px;">Reversed</span>
                                            @elseif($audit->applied)
                                            <span class="badge badge-success" style="font-size: 11px; padding: 5px 10px;">Applied</span>
                                            @else
                                            <span class="badge badge-secondary" style="font-size: 11px; padding: 5px 10px;">Pending</span>
                                            @endif
                                        </td>
                                        <td style="color: #333;">
                                            @if($audit->applied_at)
                                                <div style="font-weight: 600; color: #212529;">{{ $audit->applied_at->format('Y-m-d') }}</div>
                                                <small style="color: #6c757d; font-size: 11px;">{{ $audit->applied_at->format('H:i:s') }}</small>
                                            @else
                                                <span style="color: #6c757d;">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('finance.transaction-fixes.show', $audit) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if($audit->applied && !$audit->reversed)
                                            <form method="POST" action="{{ route('finance.transaction-fixes.reverse', $audit) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure you want to reverse this change?')">
                                                    <i class="fas fa-undo"></i>
                                                </button>
                                            </form>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="11" class="text-center" style="color: #6c757d; padding: 20px;">No fixes found</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($audits->where('applied', true)->where('reversed', false)->count() > 0)
                        <div class="mt-3">
                            <button type="submit" class="btn btn-warning" onclick="return confirm('Are you sure you want to reverse all selected changes?')">
                                <i class="fas fa-undo"></i> Reverse Selected
                            </button>
                        </div>
                        @endif
                    </form>

                    {{ $audits->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('input[name="audit_ids[]"]');
    checkboxes.forEach(cb => cb.checked = this.checked);
});
</script>
@endsection
