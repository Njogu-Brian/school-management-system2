@extends('layouts.app')

@section('title', 'Transaction Fix Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Transaction Fix Details #{{ $audit->id }}</h4>
                    <a href="{{ route('finance.transaction-fixes.index') }}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Fix Information</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th>Fix Type</th>
                                    <td><span class="badge badge-info">{{ $audit->fix_type }}</span></td>
                                </tr>
                                <tr>
                                    <th>Entity Type</th>
                                    <td><span class="badge badge-secondary">{{ $audit->entity_type }}</span></td>
                                </tr>
                                <tr>
                                    <th>Entity ID</th>
                                    <td>{{ $audit->entity_id }}</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        @if($audit->reversed)
                                        <span class="badge badge-warning">Reversed</span>
                                        @elseif($audit->applied)
                                        <span class="badge badge-success">Applied</span>
                                        @else
                                        <span class="badge badge-secondary">Pending</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Reason</th>
                                    <td>{{ $audit->reason }}</td>
                                </tr>
                                <tr>
                                    <th>Applied At</th>
                                    <td>{{ $audit->applied_at ? $audit->applied_at->format('Y-m-d H:i:s') : '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Applied By</th>
                                    <td>{{ $audit->appliedBy ? $audit->appliedBy->name : 'System' }}</td>
                                </tr>
                                @if($audit->reversed)
                                <tr>
                                    <th>Reversed At</th>
                                    <td>{{ $audit->reversed_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <th>Reversed By</th>
                                    <td>{{ $audit->reversedBy ? $audit->reversedBy->name : 'System' }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>

                        <div class="col-md-6">
                            <h5>Entity Details</h5>
                            @if($entity)
                            <div class="card">
                                <div class="card-body">
                                    @if($audit->entity_type === 'bank_statement_transaction')
                                    <p><strong>Reference:</strong> {{ $entity->reference_number }}</p>
                                    <p><strong>Amount:</strong> KES {{ number_format($entity->amount, 2) }}</p>
                                    <p><strong>Date:</strong> {{ $entity->transaction_date ? $entity->transaction_date->format('Y-m-d') : 'N/A' }}</p>
                                    <p><strong>Status:</strong> {{ $entity->status }}</p>
                                    <p><strong>Student:</strong> {{ $entity->student ? $entity->student->full_name : 'N/A' }}</p>
                                    <p><strong>Description:</strong> {{ Str::limit($entity->description ?? 'N/A', 100) }}</p>
                                    @if($entity->payment)
                                    <p><strong>Linked Payment:</strong> 
                                        <a href="{{ route('finance.payments.show', $entity->payment->id) }}" class="text-primary">
                                            {{ $entity->payment->receipt_number }} ({{ $entity->payment->payment_date ? $entity->payment->payment_date->format('Y-m-d') : 'No date' }})
                                        </a>
                                    </p>
                                    @endif
                                    @elseif($audit->entity_type === 'mpesa_c2b_transaction')
                                    <p><strong>Transaction ID:</strong> {{ $entity->trans_id }}</p>
                                    <p><strong>Amount:</strong> KES {{ number_format($entity->trans_amount, 2) }}</p>
                                    <p><strong>Date:</strong> {{ $entity->trans_time ? $entity->trans_time->format('Y-m-d H:i') : 'N/A' }}</p>
                                    <p><strong>Status:</strong> {{ $entity->status }}</p>
                                    <p><strong>Student:</strong> {{ $entity->student ? $entity->student->full_name : 'N/A' }}</p>
                                    <p><strong>Payer:</strong> {{ $entity->full_name }}</p>
                                    @if($entity->payment)
                                    <p><strong>Linked Payment:</strong> 
                                        <a href="{{ route('finance.payments.show', $entity->payment->id) }}" class="text-primary">
                                            {{ $entity->payment->receipt_number }} ({{ $entity->payment->payment_date ? $entity->payment->payment_date->format('Y-m-d') : 'No date' }})
                                        </a>
                                    </p>
                                    @endif
                                    @elseif($audit->entity_type === 'payment')
                                    <p><strong>Receipt Number:</strong> {{ $entity->receipt_number }}</p>
                                    <p><strong>Amount:</strong> KES {{ number_format($entity->amount, 2) }}</p>
                                    <p><strong>Date:</strong> {{ $entity->payment_date ? $entity->payment_date->format('Y-m-d') : 'N/A' }}</p>
                                    <p><strong>Narration:</strong> {{ $entity->narration ?? 'No narration' }}</p>
                                    <p><strong>Student:</strong> {{ $entity->student ? $entity->student->full_name : 'N/A' }}</p>
                                    <p><strong>Transaction Code:</strong> {{ $entity->transaction_code ?? 'N/A' }}</p>
                                    @endif
                                </div>
                            </div>
                            @else
                            <p class="text-muted">Entity not found or was deleted</p>
                            @endif
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <h5>Before (Old Values)</h5>
                            <div class="card bg-light">
                                <div class="card-body">
                                    @if($audit->old_values)
                                        @foreach($audit->old_values as $key => $value)
                                            <div class="mb-2">
                                                <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong>
                                                @if($key === 'payment_id' && $value)
                                                    @php
                                                        $oldPayment = \App\Models\Payment::find($value);
                                                    @endphp
                                                    @if($oldPayment)
                                                        <a href="{{ route('finance.payments.show', $oldPayment->id) }}" class="text-info">
                                                            {{ $oldPayment->receipt_number ?? 'N/A' }}
                                                        </a>
                                                        <br><small class="text-muted">{{ $oldPayment->narration ?? 'No narration' }}</small>
                                                        <br><small class="text-muted">{{ $oldPayment->payment_date ? $oldPayment->payment_date->format('Y-m-d') : 'No date' }}</small>
                                                    @else
                                                        <span class="text-danger">Payment #{{ $value }} (Deleted/Not Found)</span>
                                                    @endif
                                                @else
                                                    {{ is_array($value) ? json_encode($value) : ($value ?? 'NULL') }}
                                                @endif
                                            </div>
                                        @endforeach
                                    @else
                                        <p class="text-muted">No old values</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h5>After (New Values)</h5>
                            <div class="card bg-light">
                                <div class="card-body">
                                    @if($audit->new_values)
                                        @foreach($audit->new_values as $key => $value)
                                            <div class="mb-2">
                                                <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong>
                                                @if($key === 'payment_id' && $value)
                                                    @php
                                                        $newPayment = \App\Models\Payment::find($value);
                                                    @endphp
                                                    @if($newPayment)
                                                        <a href="{{ route('finance.payments.show', $newPayment->id) }}" class="text-success">
                                                            {{ $newPayment->receipt_number ?? 'N/A' }}
                                                        </a>
                                                        <br><small class="text-muted">{{ $newPayment->narration ?? 'No narration' }}</small>
                                                        <br><small class="text-muted">{{ $newPayment->payment_date ? $newPayment->payment_date->format('Y-m-d') : 'No date' }}</small>
                                                    @else
                                                        <span class="text-danger">Payment #{{ $value }} (Not Found)</span>
                                                    @endif
                                                @elseif($key === 'payment_created')
                                                    {{ $value ? 'Yes' : 'No' }}
                                                @else
                                                    {{ is_array($value) ? json_encode($value) : ($value ?? 'NULL') }}
                                                @endif
                                            </div>
                                        @endforeach
                                    @else
                                        <p class="text-muted">No new values</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($audit->applied && !$audit->reversed)
                    <div class="mt-4">
                        <form method="POST" action="{{ route('finance.transaction-fixes.reverse', $audit) }}">
                            @csrf
                            <button type="submit" class="btn btn-warning" onclick="return confirm('Are you sure you want to reverse this change?')">
                                <i class="fas fa-undo"></i> Reverse This Change
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
