@extends('layouts.app')

@section('content')
<div class="finance-page">
    <div class="finance-shell">
        @include('finance.partials.header', [
            'title' => 'Extra income',
            'icon' => 'bi bi-piggy-bank',
            'subtitle' => 'Trips, fun days, swimming, and other activity fees you can split out of a school-fees payment.',
            'actions' => '<a href="' . route('finance.extra-income.create') . '" class="btn btn-finance btn-finance-primary"><i class="bi bi-plus-circle"></i> Add extra income</a>',
        ])

        @include('finance.invoices.partials.alerts')

        <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
            <div class="finance-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Activity</th>
                                <th>Type</th>
                                <th>Class</th>
                                <th>Term</th>
                                <th class="text-end">Amount</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $item)
                                <tr>
                                    <td class="fw-semibold">
                                        <a href="{{ route('finance.extra-income.show', $item) }}">{{ $item->name }}</a>
                                        @if($item->event_date)
                                            <div class="small text-muted">{{ $item->event_date->format('d M Y') }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $item->kindLabel() }}</td>
                                    <td>{{ $item->classroom->name ?? 'Any class' }}</td>
                                    <td>Term {{ $item->term }} {{ $item->year }}</td>
                                    <td class="text-end">Ksh {{ number_format($item->amount, 2) }}</td>
                                    <td>
                                        @if($item->is_active)
                                            <span class="badge bg-success">Open</span>
                                        @else
                                            <span class="badge bg-secondary">Closed</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('finance.extra-income.show', $item) }}" class="btn btn-sm btn-finance btn-finance-outline">View</a>
                                        <a href="{{ route('finance.extra-income.edit', $item) }}" class="btn btn-sm btn-finance btn-finance-outline">Edit</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        No extra income yet. Add a trip, fun day, or swimming charge, then split a payment against it.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
