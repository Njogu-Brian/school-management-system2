@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Bank Accounts',
        'icon' => 'bi bi-bank',
        'subtitle' => 'Manage bank accounts for payment processing',
        'actions' => '<a href="' . route('finance.bank-accounts.create') . '" class="btn btn-finance btn-finance-primary"><i class="bi bi-plus-circle"></i> Add Bank Account</a>'
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
                        <th>Account Number</th>
                        <th>Bank Name</th>
                        <th>Branch</th>
                        <th>Account Type</th>
                        <th>Currency</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bankAccounts as $account)
                        <tr>
                            <td><strong>{{ $account->name }}</strong></td>
                            <td><code>{{ $account->account_number }}</code></td>
                            <td>{{ $account->bank_name }}</td>
                            <td>{{ $account->branch ?? '—' }}</td>
                            <td>
                                <span class="badge bg-info">{{ ucfirst($account->account_type) }}</span>
                            </td>
                            <td>{{ $account->currency ?? 'KES' }}</td>
                            <td>
                                @if($account->is_active)
                                    <span class="finance-badge badge-approved">Active</span>
                                @else
                                    <span class="finance-badge badge-rejected">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div class="finance-action-buttons">
                                    <a href="{{ route('finance.bank-accounts.show', $account) }}" class="btn btn-sm btn-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('finance.bank-accounts.edit', $account) }}" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('finance.bank-accounts.destroy', $account) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this bank account?');">
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
                                        <i class="bi bi-bank"></i>
                                    </div>
                                    <h4>No bank accounts found</h4>
                                    <p>Create your first bank account to get started.</p>
                                    <a href="{{ route('finance.bank-accounts.create') }}" class="btn btn-finance btn-finance-primary">
                                        <i class="bi bi-plus-circle"></i> Add Bank Account
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
</div>
@endsection

