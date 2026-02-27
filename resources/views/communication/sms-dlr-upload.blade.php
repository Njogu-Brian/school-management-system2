@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        @include('communication.partials.header', [
            'title' => 'SMS HostPinnacle DLR',
            'icon' => 'bi bi-file-earmark-arrow-up',
            'subtitle' => 'Upload HostPinnacle delivery report (DLR) CSV to reconcile with system logs. Identifies who received messages, who failed at HostPinnacle, and who never reached HostPinnacle.',
            'actions' => '<a href="' . route('communication.logs') . '" class="btn btn-ghost-strong"><i class="bi bi-clock-history"></i> Logs</a>'
        ])

        @include('communication.partials.flash')

        <div class="settings-card">
            <div class="card-header">
                <h5 class="mb-0">Upload DLR CSV</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Download the DLR from your HostPinnacle portal, then upload the CSV here. The system will update CommunicationLog with HostPinnacle status and show a reconciliation report.</p>
                <form method="POST" action="{{ route('communication.sms-dlr.process') }}" enctype="multipart/form-data" class="row g-3">
                    @csrf
                    <div class="col-md-6">
                        <label class="form-label">DLR CSV file</label>
                        <input type="file" name="dlr_file" class="form-control" accept=".csv,.txt" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">SMS date from</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from', now()->subDays(7)->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">SMS date to</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to', now()->format('Y-m-d')) }}">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-settings-primary">
                            <i class="bi bi-upload"></i> Upload & Reconcile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
