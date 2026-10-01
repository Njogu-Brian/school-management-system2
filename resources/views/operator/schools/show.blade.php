@extends('operator.layout')
@section('title', $school->name)
@section('content')
@if(session('admin_password'))
    <div class="alert alert-warning"><strong>Save this Super Admin password (shown once):</strong> <code>{{ session('admin_password') }}</code></div>
@endif
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h1 class="h3 mb-1">{{ $school->name }}</h1>
        <div class="text-muted">Code <code>{{ $school->code }}</code> · Status {{ $school->status }} · Billing {{ $school->billing_status }}</div>
    </div>
    <div class="d-flex gap-2">
        @if($school->status === 'suspended')
            <form method="post" action="{{ route('operator.schools.activate', $school) }}">@csrf<button class="btn btn-success btn-sm">Activate</button></form>
        @else
            <form method="post" action="{{ route('operator.schools.suspend', $school) }}">@csrf<button class="btn btn-outline-danger btn-sm">Suspend</button></form>
        @endif
        <a class="btn btn-outline-primary btn-sm" href="{{ $school->tenantWebUrl() }}" target="_blank">Open ERP</a>
    </div>
</div>
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card p-3 mb-3">
            <h2 class="h6">Provisioning</h2>
            <dl class="row mb-0 small">
                <dt class="col-4">API</dt><dd class="col-8"><code>{{ $school->api_base_url }}</code></dd>
                <dt class="col-4">Database</dt><dd class="col-8">{{ $school->db_name }} @ {{ $school->db_host }}</dd>
                <dt class="col-4">Students</dt><dd class="col-8">{{ $school->student_count_cached }}</dd>
                <dt class="col-4">Monthly fee</dt><dd class="col-8">KES {{ number_format((float)$school->monthly_fee, 2) }}</dd>
                <dt class="col-4">Next due</dt><dd class="col-8">{{ optional($school->next_due_date)->toDateString() ?? '—' }}</dd>
                <dt class="col-4">Provisioned</dt><dd class="col-8">{{ optional($school->provisioned_at)->toDateTimeString() ?? '—' }}</dd>
            </dl>
        </div>
        <div class="card p-3 mb-3">
            <h2 class="h6">App invite</h2>
            <textarea class="form-control" rows="7" readonly>{{ $invite }}</textarea>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card p-3 mb-3">
            <h2 class="h6">Record payment</h2>
            <form method="post" action="{{ route('operator.schools.payments.store', $school) }}" class="row g-2">
                @csrf
                <div class="col-6"><input type="number" step="0.01" name="amount" class="form-control" placeholder="Amount" required value="{{ $school->monthly_fee }}"></div>
                <div class="col-6">
                    <select name="method" class="form-select" required>
                        <option value="mpesa">M-Pesa</option>
                        <option value="bank">Bank</option>
                        <option value="cash">Cash</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="col-6"><input name="period" class="form-control" value="{{ now()->format('Y-m') }}" placeholder="YYYY-MM"></div>
                <div class="col-6"><input name="reference" class="form-control" placeholder="Reference"></div>
                <div class="col-12"><input name="notes" class="form-control" placeholder="Notes"></div>
                <div class="col-12"><button class="btn btn-primary btn-sm">Save payment</button></div>
            </form>
        </div>
        <div class="card p-3 mb-3">
            <h2 class="h6">Subscriptions</h2>
            <table class="table table-sm mb-0">
                <thead><tr><th>Period</th><th>Due</th><th>Paid</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($subscriptions as $sub)
                    <tr><td>{{ $sub->period }}</td><td>{{ $sub->amount_due }}</td><td>{{ $sub->amount_paid }}</td><td>{{ $sub->status }}</td></tr>
                @empty
                    <tr><td colspan="4" class="text-muted">None</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card p-3">
            <h2 class="h6">Recent payments</h2>
            <table class="table table-sm mb-0">
                <thead><tr><th>When</th><th>Amount</th><th>Method</th><th>Ref</th></tr></thead>
                <tbody>
                @forelse($payments as $p)
                    <tr><td>{{ optional($p->paid_at)->toDateString() }}</td><td>{{ $p->amount }}</td><td>{{ $p->method }}</td><td>{{ $p->reference }}</td></tr>
                @empty
                    <tr><td colspan="4" class="text-muted">None</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
