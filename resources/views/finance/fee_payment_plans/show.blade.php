@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Payment Plan Details</h1>
        <a href="{{ route('finance.fee-payment-plans.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Plan Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th>Student:</th>
                            <td>{{ $feePaymentPlan->student->first_name }} {{ $feePaymentPlan->student->last_name }}</td>
                        </tr>
                        <tr>
                            <th>Total Amount:</th>
                            <td>KES {{ number_format($feePaymentPlan->total_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Installments:</th>
                            <td>{{ $feePaymentPlan->installment_count }}</td>
                        </tr>
                        <tr>
                            <th>Installment Amount:</th>
                            <td>KES {{ number_format($feePaymentPlan->installment_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Start Date:</th>
                            <td>{{ $feePaymentPlan->start_date->format('M d, Y') }}</td>
                        </tr>
                        <tr>
                            <th>End Date:</th>
                            <td>{{ $feePaymentPlan->end_date->format('M d, Y') }}</td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                <span class="badge bg-{{ $feePaymentPlan->status == 'active' ? 'success' : ($feePaymentPlan->status == 'completed' ? 'info' : 'secondary') }}">
                                    {{ ucfirst($feePaymentPlan->status) }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Installments</h5>
                    <form action="{{ route('finance.fee-payment-plans.update-status', $feePaymentPlan) }}" method="POST" class="d-inline">
                        @csrf
                        <select name="status" class="form-select form-select-sm d-inline-block" style="width: auto;" onchange="this.form.submit()">
                            <option value="active" {{ $feePaymentPlan->status == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="completed" {{ $feePaymentPlan->status == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ $feePaymentPlan->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </form>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Amount</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($feePaymentPlan->installments as $installment)
                                    <tr>
                                        <td>{{ $installment->installment_number }}</td>
                                        <td>KES {{ number_format($installment->amount, 2) }}</td>
                                        <td>{{ $installment->due_date->format('M d, Y') }}</td>
                                        <td>
                                            <span class="badge bg-{{ $installment->status == 'paid' ? 'success' : ($installment->status == 'overdue' ? 'danger' : 'warning') }}">
                                                {{ ucfirst($installment->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

