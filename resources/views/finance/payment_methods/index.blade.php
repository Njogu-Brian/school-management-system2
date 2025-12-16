@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @include('finance.partials.header', [
        'title' => 'Payment Methods',
        'icon' => 'bi bi-credit-card',
        'subtitle' => 'Manage payment methods and link to bank accounts',
        'actions' => '<a href="' . route('finance.payment-methods.create') . '" class="btn btn-finance btn-finance-primary"><i class="bi bi-plus-circle"></i> Add Payment Method</a>'
    ])

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show finance-animate" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show finance-animate" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="finance-table-wrapper finance-animate">
        <div class="table-responsive">
            <table class="finance-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Bank Account</th>
                        <th>Requires Reference</th>
                        <th>Online</th>
                        <th>Status</th>
                        <th>Display Order</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($paymentMethods as $method)
                        <tr>
                            <td><strong>{{ $method->name }}</strong></td>
                            <td><code>{{ $method->code }}</code></td>
                            <td>
                                @if($method->bankAccount)
                                    <span class="badge bg-info">{{ $method->bankAccount->name }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($method->requires_reference)
                                    <span class="finance-badge badge-approved">Yes</span>
                                @else
                                    <span class="finance-badge badge-pending">No</span>
                                @endif
                            </td>
                            <td>
                                @if($method->is_online)
                                    <span class="badge bg-primary">Online</span>
                                @else
                                    <span class="badge bg-secondary">Offline</span>
                                @endif
                            </td>
                            <td>
                                @if($method->is_active)
                                    <span class="finance-badge badge-approved">Active</span>
                                @else
                                    <span class="finance-badge badge-rejected">Inactive</span>
                                @endif
                            </td>
                            <td>{{ $method->display_order ?? 0 }}</td>
                            <td>
                                <div class="finance-action-buttons">
                                    <a href="{{ route('finance.payment-methods.show', $method) }}" class="btn btn-sm btn-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('finance.payment-methods.edit', $method) }}" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('finance.payment-methods.destroy', $method) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this payment method?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="finance-empty-state">
                                    <div class="finance-empty-state-icon">
                                        <i class="bi bi-credit-card"></i>
                                    </div>
                                    <h4>No payment methods found</h4>
                                    <p>Create your first payment method to get started.</p>
                                    <a href="{{ route('finance.payment-methods.create') }}" class="btn btn-finance btn-finance-primary">
                                        <i class="bi bi-plus-circle"></i> Add Payment Method
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

