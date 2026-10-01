@extends('operator.layout')

@section('title', 'Operator Dashboard')

@section('content')
<h1 class="h3 mb-4">Dashboard</h1>
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card stat-card p-3"><div class="text-muted small">Schools</div><div class="fs-3 fw-bold">{{ $schools->count() }}</div></div></div>
    <div class="col-md-3"><div class="card stat-card p-3"><div class="text-muted small">Active</div><div class="fs-3 fw-bold text-success">{{ $active }}</div></div></div>
    <div class="col-md-3"><div class="card stat-card p-3"><div class="text-muted small">Suspended / overdue</div><div class="fs-3 fw-bold text-danger">{{ $suspended }} / {{ $overdue }}</div></div></div>
    <div class="col-md-3"><div class="card stat-card p-3"><div class="text-muted small">Students (cached)</div><div class="fs-3 fw-bold">{{ number_format($students) }}</div></div></div>
</div>
<div class="row g-3 mb-4">
    <div class="col-md-6"><div class="card stat-card p-3"><div class="text-muted small">Billing {{ $period }} — expected</div><div class="fs-4">KES {{ number_format($expected, 2) }}</div></div></div>
    <div class="col-md-6"><div class="card stat-card p-3"><div class="text-muted small">Billing {{ $period }} — collected</div><div class="fs-4 text-success">KES {{ number_format($collected, 2) }}</div></div></div>
</div>
<div class="d-flex justify-content-between align-items-center mb-2">
    <h2 class="h5 mb-0">Schools</h2>
    <a href="{{ route('operator.schools.create') }}" class="btn btn-primary btn-sm">Create school</a>
</div>
<div class="card stat-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Code</th><th>Name</th><th>Status</th><th>Billing</th><th>Students</th><th></th></tr></thead>
            <tbody>
            @forelse($schools as $s)
                <tr>
                    <td><code>{{ $s->code }}</code></td>
                    <td>{{ $s->name }}</td>
                    <td>{{ $s->status }}</td>
                    <td>{{ $s->billing_status }}</td>
                    <td>{{ $s->student_count_cached }}</td>
                    <td><a href="{{ route('operator.schools.show', $s) }}">Open</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-muted p-3">No schools yet. Create the demo school or a blank tenant.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
