@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div>
                <div class="crumb">HR & Payroll / Payroll / Imports</div>
                <h1 class="mb-1">Verify Budget Import</h1>
                <p class="text-muted mb-0">Resolve any ambiguous rows before commit.</p>
            </div>
            <a href="{{ route('hr.payroll.imports.budget.form') }}" class="btn btn-ghost-strong">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>

        @include('partials.alerts')

        @php($rows = $import['rows'] ?? [])

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">Preview Rows</h5>
                    <p class="text-muted small mb-0">{{ count($rows) }} row(s) parsed for {{ $import['year'] }}-{{ str_pad($import['month'], 2, '0', STR_PAD_LEFT) }}</p>
                </div>
                <span class="pill-badge pill-secondary">Dry-run</span>
            </div>
            <div class="card-body p-0">
                <form method="POST" action="{{ route('hr.payroll.imports.budget.commit') }}">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-modern table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Name (PDF)</th>
                                    <th>Gross</th>
                                    <th>NSSF</th>
                                    <th>SHIF</th>
                                    <th>PAYE</th>
                                    <th>Housing</th>
                                    <th>Net</th>
                                    <th>Match</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $i => $r)
                                    @php($m = $r['match'] ?? [])
                                    @php($status = $m['status'] ?? 'unmatched')
                                    <tr>
                                        <td>{{ $r['row_index'] ?? ($i + 1) }}</td>
                                        <td class="fw-semibold">{{ $r['name'] ?? '' }}</td>
                                        <td>Ksh {{ number_format((float)($r['gross'] ?? 0), 2) }}</td>
                                        <td>Ksh {{ number_format((float)($r['nssf'] ?? 0), 2) }}</td>
                                        <td>Ksh {{ number_format((float)($r['shif'] ?? 0), 2) }}</td>
                                        <td>Ksh {{ number_format((float)($r['paye'] ?? 0), 2) }}</td>
                                        <td>Ksh {{ number_format((float)($r['housing'] ?? 0), 2) }}</td>
                                        <td><strong>Ksh {{ number_format((float)($r['net'] ?? 0), 2) }}</strong></td>
                                        <td>
                                            @if($status === 'matched')
                                                <span class="pill-badge pill-success">Matched</span>
                                                <div class="small text-muted">
                                                    {{ $m['candidates'][0]['name'] ?? ('Staff #' . ($m['staff_id'] ?? '')) }}
                                                </div>
                                            @elseif($status === 'ambiguous')
                                                <span class="pill-badge pill-warning">Ambiguous</span>
                                                <select class="form-select form-select-sm mt-1" name="resolve[{{ $i }}]">
                                                    <option value="">Select staff…</option>
                                                    @foreach(($m['candidates'] ?? []) as $c)
                                                        <option value="{{ $c['id'] }}">{{ $c['staff_id'] }} — {{ $c['name'] }}</option>
                                                    @endforeach
                                                </select>
                                                <div class="small text-muted mt-1">Matched by {{ $m['match_key'] ?? 'name' }}</div>
                                            @else
                                                <span class="pill-badge pill-danger">Unmatched</span>
                                                <div class="small text-muted">No safe match</div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3 d-flex justify-content-end">
                        <button class="btn btn-success" type="submit" onclick="return confirm('Commit import? This will create/update payroll records for the selected period.')">
                            <i class="bi bi-check2-circle"></i> Commit Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

