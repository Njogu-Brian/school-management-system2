@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'Payment Links',
        'icon' => 'bi bi-link-45deg',
        'subtitle' => 'Manage and monitor payment links',
        'actions' => '<a href="' . route('finance.mpesa.links.create') . '" class="btn btn-finance btn-finance-primary"><i class="bi bi-plus-circle"></i> Create Link</a><a href="' . route('finance.mpesa.dashboard') . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>'
    ])

    <!-- Filters -->
    <div class="finance-card finance-animate mb-4">
        <div class="finance-card-body">
            <form method="GET" action="{{ route('finance.mpesa.links.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="used" {{ request('status') === 'used' ? 'selected' : '' }}>Used</option>
                        <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Student name, token, reference..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-finance btn-finance-primary w-100">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <a href="{{ route('finance.mpesa.links.index') }}" class="btn btn-finance btn-finance-outline w-100">
                            <i class="bi bi-x-circle"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Payment Links Table -->
    <div class="finance-card finance-animate">
        <div class="finance-card-header">
            <h5 class="finance-card-title">
                <i class="bi bi-list-ul me-2"></i>
                Payment Links ({{ $links->total() }})
            </h5>
        </div>
        <div class="finance-card-body p-0">
            <div class="finance-table-wrapper">
                <table class="finance-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Reference</th>
                            <th class="text-end">Amount</th>
                            <th>Status</th>
                            <th>Expires</th>
                            <th>Uses</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($links as $link)
                        <tr>
                            <td>
                                <div>
                                    <strong>{{ $link->student->first_name }} {{ $link->student->last_name }}</strong>
                                </div>
                                <small class="text-muted">{{ $link->student->admission_number }}</small>
                                @if($link->invoice)
                                <div>
                                    <small class="text-info">
                                        <i class="bi bi-file-text"></i> {{ $link->invoice->invoice_number }}
                                    </small>
                                </div>
                                @endif
                            </td>
                            <td>
                                <code class="text-muted small">{{ $link->payment_reference }}</code>
                                <div>
                                    <small class="text-muted">{{ Str::limit($link->token, 12) }}</small>
                                </div>
                            </td>
                            <td class="text-end">
                                <strong>KES {{ number_format($link->amount, 2) }}</strong>
                            </td>
                            <td>
                                @if($link->status === 'active')
                                    <span class="finance-badge badge-success">Active</span>
                                @elseif($link->status === 'used')
                                    <span class="finance-badge badge-info">Used</span>
                                @elseif($link->status === 'expired')
                                    <span class="finance-badge badge-warning">Expired</span>
                                @elseif($link->status === 'cancelled')
                                    <span class="finance-badge badge-danger">Cancelled</span>
                                @else
                                    <span class="finance-badge badge-secondary">{{ ucfirst($link->status) }}</span>
                                @endif
                            </td>
                            <td>
                                @if($link->expires_at)
                                    @if($link->expires_at->isPast())
                                        <span class="text-danger small">
                                            <i class="bi bi-clock-history"></i> Expired
                                        </span>
                                        <div>
                                            <small class="text-muted">{{ $link->expires_at->format('M d, Y') }}</small>
                                        </div>
                                    @else
                                        <span class="text-success small">
                                            <i class="bi bi-clock"></i> {{ $link->expires_at->diffForHumans() }}
                                        </span>
                                        <div>
                                            <small class="text-muted">{{ $link->expires_at->format('M d, Y') }}</small>
                                        </div>
                                    @endif
                                @else
                                    <span class="text-muted small">No expiry</span>
                                @endif
                            </td>
                            <td>
                                <span class="text-muted">{{ $link->use_count }} / {{ $link->max_uses }}</span>
                                @if($link->payment_id)
                                <div>
                                    <small class="text-success">
                                        <i class="bi bi-check-circle"></i> Paid
                                    </small>
                                </div>
                                @endif
                            </td>
                            <td>
                                <span class="text-muted small">{{ $link->created_at->format('M d, Y') }}</span>
                                <div>
                                    <small class="text-muted">{{ $link->created_at->format('H:i') }}</small>
                                </div>
                                @if($link->creator)
                                <div>
                                    <small class="text-muted">by {{ $link->creator->name }}</small>
                                </div>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('finance.mpesa.link.show', $link) }}" 
                                       class="btn btn-finance btn-finance-sm" 
                                       title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if($link->status === 'active')
                                    <a href="{{ $link->getPaymentUrl() }}" 
                                       target="_blank"
                                       class="btn btn-finance btn-finance-sm btn-success" 
                                       title="View Payment Page">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                <p class="mb-2">No payment links found</p>
                                <a href="{{ route('finance.mpesa.links.create') }}" class="btn btn-finance btn-finance-primary btn-sm">
                                    <i class="bi bi-plus-circle"></i> Create Payment Link
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($links->hasPages())
        <div class="finance-card-footer">
            {{ $links->links() }}
        </div>
        @endif
    </div>
@endsection
