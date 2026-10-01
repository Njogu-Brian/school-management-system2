@extends('operator.layout')
@section('title', 'Billing')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h1 class="h3">Billing</h1>
    <form class="d-flex gap-2" method="get">
        <input name="period" class="form-control form-control-sm" value="{{ $period }}" placeholder="YYYY-MM">
        <button class="btn btn-sm btn-outline-primary">Filter</button>
    </form>
</div>
<div class="card">
    <table class="table mb-0">
        <thead><tr><th>School</th><th>Code</th><th>Due</th><th>Paid</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse($subscriptions as $sub)
            <tr>
                <td>{{ $sub->school?->name }}</td>
                <td><code>{{ $sub->school?->code }}</code></td>
                <td>{{ number_format((float)$sub->amount_due, 2) }}</td>
                <td>{{ number_format((float)$sub->amount_paid, 2) }}</td>
                <td>{{ $sub->status }}</td>
                <td>@if($sub->school)<a href="{{ route('operator.schools.show', $sub->school) }}">Manage</a>@endif</td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-muted p-3">No subscription rows for {{ $period }}.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
