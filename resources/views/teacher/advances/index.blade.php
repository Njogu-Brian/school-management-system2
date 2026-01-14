@extends('layouts.app')

@php
  $routePrefix = request()->routeIs('senior_teacher.*') ? 'senior_teacher.advances' : 'teacher.advances';
@endphp

@push('styles')
    @if(request()->routeIs('senior_teacher.*'))
        @include('senior_teacher.partials.styles')
    @endif
@endpush

@section('content')
<div class="{{ request()->routeIs('senior_teacher.*') ? 'senior-teacher-page' : '' }}">
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">My Advance Requests</h1>
            <p class="text-muted mb-0">Track the status of salary advances you have requested.</p>
        </div>
        <a href="{{ route($routePrefix . '.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Request Advance
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Repayment</th>
                        <th>Status</th>
                        <th>Purpose</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($advances as $advance)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $advance->advance_date?->format('d M Y') }}</div>
                                <small class="text-muted">Requested {{ $advance->created_at->diffForHumans() }}</small>
                            </td>
                            <td>KES {{ number_format($advance->amount, 2) }}</td>
                            <td class="text-capitalize">{{ str_replace('_', ' ', $advance->repayment_method) }}</td>
                            <td>
                                @php
                                    $badge = [
                                        'pending' => 'warning text-dark',
                                        'approved' => 'info text-dark',
                                        'active' => 'primary',
                                        'completed' => 'success',
                                        'rejected' => 'danger',
                                    ][$advance->status] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $badge }}">{{ ucfirst($advance->status) }}</span>
                            </td>
                            <td>{{ $advance->purpose ?: '—' }}</td>
                            <td>
                                @if($advance->balance !== null)
                                    KES {{ number_format($advance->balance, 2) }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                You have not submitted any advance requests yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($advances->hasPages())
            <div class="card-footer">
                {{ $advances->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

