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
        </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @include('dashboard.partials.charts_js_bootstrap') {{-- centralizes chart inits --}}
@endpush
