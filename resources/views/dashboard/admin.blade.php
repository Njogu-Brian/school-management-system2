@extends('layouts.app')

@push('styles')
    @include('dashboard.partials.styles')
@endpush

@section('content')
<div class="dashboard-page">
  <div class="dashboard-shell">
    <div class="dash-hero d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
        <div>
            <span class="crumb">Dashboard</span>
            <h2 class="mb-1">Admin Dashboard</h2>
            <p class="mb-0">Overview of students, attendance, finance, exams, transport & communications.</p>
        </div>
        <div class="actions">
            @include('dashboard.partials.quick_actions')
        </div>
    </div>

    {{-- Flash messages --}}
    @includeWhen(session('success') || session('error'),'dashboard.partials.flash')

    {{-- Filters (Term / Date range / Class / Stream / Status) --}}
    @include('dashboard.partials.filters')

    {{-- KPI cards --}}
    @include('dashboard.partials.kpis')

    <div class="row g-3">
        {{-- Left column --}}
        <div class="col-12 col-xl-8">
            {{-- Core charts --}}
            <div class="row g-3">
                <div class="col-12 col-xxl-6">
                    @include('dashboard.partials.attendance_chart')
                </div>
                <div class="col-12 col-xxl-6">
                    @include('dashboard.partials.enrolment_chart')
                </div>
                <div class="col-12 col-xxl-6">
                    @include('dashboard.partials.finance_donut')
                </div>
                <div class="col-12 col-xxl-6">
                    @include('dashboard.partials.exam_performance')
                </div>
            </div>

            {{-- Tables: absences, invoices, behaviour --}}
            <div class="row g-3 mt-1">
                <div class="col-12 col-xxl-6">
                    @include('dashboard.partials.absence_table')
                </div>
                <div class="col-12 col-xxl-6">
                    @include('dashboard.partials.invoice_table')
                </div>
            </div>
        </div>

        {{-- Right column --}}
        <div class="col-12 col-xl-4">
            @include('dashboard.partials.announcements')
            @include('dashboard.partials.upcoming')
            @include('dashboard.partials.transport_widget')
            @include('dashboard.partials.behaviour_widget')
            @include('dashboard.partials.system_health')
            
            {{-- Weekly Payments Chart --}}
            @if(in_array($role ?? 'admin', ['admin','finance']) && !empty($weeklyPayments))
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">Weekly Payments - Term</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="weeklyPaymentsChart" height="200"></canvas>
                    </div>
                </div>
            @endif
        </div>
    </div>
  </div>
</div>

{{-- Votehead Breakdown Modal --}}
@if(in_array($role ?? 'admin', ['admin','finance']))
<div class="modal fade" id="voteheadBreakdownModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Votehead Breakdown - Total Invoiced</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @php
                    $m = fn($v) => format_money($v);
                    $totalInvoiced = collect($voteheadBreakdown ?? [])->sum('total_amount');
                @endphp
                <div class="mb-3">
                    <strong>Total Invoiced: {{ $m($totalInvoiced) }}</strong>
                </div>
                @if(!empty($voteheadBreakdown) && count($voteheadBreakdown) > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Votehead</th>
                                    <th>Code</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($voteheadBreakdown as $item)
                                    @php
                                        $percentage = $totalInvoiced > 0 ? ($item['total_amount'] / $totalInvoiced) * 100 : 0;
                                    @endphp
                                    <tr>
                                        <td>{{ $item['votehead_name'] }}</td>
                                        <td><code>{{ $item['votehead_code'] }}</code></td>
                                        <td class="text-end">{{ $m($item['total_amount']) }}</td>
                                        <td class="text-end">{{ number_format($percentage, 2) }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted">No invoice data available for the selected period.</p>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @include('dashboard.partials.charts_js_bootstrap') {{-- centralizes chart inits --}}
    
    {{-- Weekly Payments Chart --}}
    @if(in_array($role ?? 'admin', ['admin','finance']) && !empty($weeklyPayments))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const weeklyPaymentsData = @json($weeklyPayments);
            
            if (weeklyPaymentsData && weeklyPaymentsData.length > 0) {
                const ctx = document.getElementById('weeklyPaymentsChart');
                if (ctx) {
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: weeklyPaymentsData.map(item => item.week_label),
                            datasets: [{
                                label: 'Payments Collected',
                                data: weeklyPaymentsData.map(item => item.total_amount),
                                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const item = weeklyPaymentsData[context.dataIndex];
                                            return 'KES ' + item.total_amount.toLocaleString('en-KE', {
                                                minimumFractionDigits: 2,
                                                maximumFractionDigits: 2
                                            }) + ' (' + item.payment_count + ' payments)';
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return 'KES ' + value.toLocaleString('en-KE');
                                        }
                                    }
                                },
                                x: {
                                    ticks: {
                                        maxRotation: 45,
                                        minRotation: 45
                                    }
                                }
                            }
                        }
                    });
                }
            }
        });
    </script>
    @endif
@endpush
