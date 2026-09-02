@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
    <style>
        @media print {
            .no-print, .sidebar, .topbar, form, .crumb { display: none !important; }
        }
    </style>
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Inventory</div>
                <h1>What we received</h1>
                <p>Totals taken into school stock for a period — for example 1,000 tissues this term. Verification-only items (learner keeps them) are not counted here.</p>
            </div>
            <div class="d-flex gap-2 no-print">
                <a class="btn btn-ghost-strong" href="{{ route('inventory.reports.receipts.csv', request()->query()) }}"><i class="bi bi-download"></i> CSV</a>
                <button class="btn btn-settings-primary" type="button" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
            </div>
        </div>

        @include('partials.alerts')

        <div class="settings-card mb-3 no-print">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">From</label>
                        <input type="date" name="from" class="form-control" value="{{ $from }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">To</label>
                        <input type="date" name="to" class="form-control" value="{{ $to }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="">All</option>
                            @foreach($report['categories'] as $cat)
                                <option value="{{ $cat }}" @selected($category === $cat)>{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-settings-primary w-100" type="submit"><i class="bi bi-funnel"></i> Show totals</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Received {{ \Carbon\Carbon::parse($from)->format('d M Y') }} – {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</h5>
                <span class="input-chip">{{ count($report['rows']) }} items</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th>Category</th>
                                <th class="text-end">From learners</th>
                                <th class="text-end">Other receipts</th>
                                <th class="text-end">Total received</th>
                                <th class="text-end">Current stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($report['rows'] as $row)
                                <tr>
                                    <td>
                                        <a href="{{ route('inventory.items.show', $row['item_id']) }}" class="fw-semibold text-decoration-none">{{ $row['name'] }}</a>
                                        <div class="small text-muted">{{ $row['unit'] }}</div>
                                    </td>
                                    <td>{{ $row['category'] ?? '—' }}</td>
                                    <td class="text-end">{{ rtrim(rtrim(number_format($row['from_learners'], 2), '0'), '.') }}</td>
                                    <td class="text-end">{{ rtrim(rtrim(number_format($row['other_receipts'], 2), '0'), '.') }}</td>
                                    <td class="text-end fw-semibold">{{ rtrim(rtrim(number_format($row['total_received'], 2), '0'), '.') }} {{ $row['unit'] }}</td>
                                    <td class="text-end">{{ rtrim(rtrim(number_format($row['current_stock'], 2), '0'), '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No stock was received in this period.</td>
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
