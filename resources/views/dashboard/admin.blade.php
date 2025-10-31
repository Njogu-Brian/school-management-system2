@extends('layouts.app')

@section('content')
<div class="container-fluid">
    {{-- Page header --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
        <div>
            <h2 class="mb-0">Admin Dashboard</h2>
            <small class="text-muted">An overview of students, attendance, finance, exams, transport & communications</small>
        </div>
        @include('dashboard.partials.quick_actions')
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
@endsection

@push('scripts')
    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @include('dashboard.partials.charts_js_bootstrap') {{-- centralizes chart inits --}}
@endpush
