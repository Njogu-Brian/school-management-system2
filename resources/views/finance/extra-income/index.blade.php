@extends('layouts.app')

@section('content')
<div class="finance-page">
    <div class="finance-shell">
        @include('finance.partials.header', [
            'title' => 'Extra income',
            'icon' => 'bi bi-piggy-bank',
            'subtitle' => 'Trips, fun days, swimming, and other activity fees you can split out of a school-fees payment. Showing term ' . ($term ?? '') . ' ' . ($year ?? '') . '.',
            'actions' => '<a href="' . route('finance.extra-income.create') . '" class="btn btn-finance btn-finance-primary"><i class="bi bi-plus-circle"></i> Add extra income</a>',
        ])

        @include('finance.invoices.partials.alerts')

        <div class="finance-filter-card finance-animate shadow-sm rounded-4 border-0 mb-4">
            <form method="GET" action="{{ route('finance.extra-income.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="finance-form-label">Academic year</label>
                    <select name="year" class="finance-form-select">
                        @foreach(($years ?? collect([$year])) as $y)
                            <option value="{{ $y }}" {{ (int) $year === (int) $y ? 'selected' : '' }}>
                                {{ $y }}{{ (int) $y === (int) ($activeYear ?? 0) ? ' (current)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="finance-form-label">Term</label>
                    <select name="term" class="finance-form-select">
                        @foreach([1, 2, 3] as $t)
                            <option value="{{ $t }}" {{ (int) $term === $t ? 'selected' : '' }}>
                                Term {{ $t }}{{ (int) $t === (int) ($activeTerm ?? 0) ? ' (current)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-finance btn-finance-primary">Apply</button>
                </div>
            </form>
        </div>

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
                                    <td>{{ $item->classroomNames() }}</td>
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
