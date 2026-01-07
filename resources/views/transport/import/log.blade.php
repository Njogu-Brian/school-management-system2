@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start">
            <div>
                <div class="crumb">
                    <a href="{{ route('transport.dashboard') }}">Transport</a> / 
                    <a href="{{ route('transport.import.form') }}">Import</a> / Log
                </div>
                <h1>Import Log Details</h1>
                <p>Detailed information about the import process.</p>
            </div>
            <a href="{{ route('transport.import.form') }}" class="btn btn-ghost-strong">
                <i class="bi bi-arrow-left"></i> Back to Import
            </a>
        </div>

        {{-- Summary --}}
        <div class="settings-card mt-3">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i> Import Summary</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr>
                                <th>Filename:</th>
                                <td>{{ $log->filename }}</td>
                            </tr>
                            <tr>
                                <th>Imported By:</th>
                                <td>{{ $log->importedBy->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Date:</th>
                                <td>{{ $log->created_at->format('F j, Y H:i:s') }}</td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    @if($log->status === 'completed')
                                        <span class="badge bg-success">Completed</span>
                                    @elseif($log->status === 'failed')
                                        <span class="badge bg-danger">Failed</span>
                                    @else
                                        <span class="badge bg-warning">Pending</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr>
                                <th>Total Rows:</th>
                                <td><span class="badge bg-primary">{{ $log->total_rows }}</span></td>
                            </tr>
                            <tr>
                                <th>Successfully Created:</th>
                                <td><span class="badge bg-success">{{ $log->success_count }}</span></td>
                            </tr>
                            <tr>
                                <th>Updated:</th>
                                <td><span class="badge bg-info">{{ $log->updated_count }}</span></td>
                            </tr>
                            <tr>
                                <th>Skipped:</th>
                                <td><span class="badge bg-secondary">{{ $log->skipped_count }}</span></td>
                            </tr>
                            <tr>
                                <th>Errors:</th>
                                <td><span class="badge bg-danger">{{ $log->error_count }}</span></td>
                            </tr>
                            <tr>
                                <th>Conflicts:</th>
                                <td><span class="badge bg-warning">{{ $log->conflict_count }}</span></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Errors --}}
        @if($log->errors && count($log->errors) > 0)
        <div class="settings-card mt-3">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-x-circle me-2"></i> Errors ({{ count($log->errors) }})</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Row</th>
                                <th>Error Message</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($log->errors as $error)
                            <tr>
                                <td>{{ $error['row'] ?? 'N/A' }}</td>
                                <td>{{ $error['message'] ?? 'Unknown error' }}</td>
                                <td><small><code>{{ json_encode($error['data'] ?? []) }}</code></small></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        {{-- Conflicts --}}
        @if($log->conflicts && count($log->conflicts) > 0)
        <div class="settings-card mt-3">
            <div class="card-header bg-warning">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i> Conflicts Resolved ({{ count($log->conflicts) }})</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Row</th>
                                <th>Admission No</th>
                                <th>Student Name</th>
                                <th>Existing Route</th>
                                <th>New Route</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($log->conflicts as $conflict)
                            <tr>
                                <td>{{ $conflict['row'] ?? 'N/A' }}</td>
                                <td>{{ $conflict['admission_number'] ?? 'N/A' }}</td>
                                <td>{{ $conflict['student_name'] ?? 'N/A' }}</td>
                                <td><span class="badge bg-primary">{{ $conflict['existing_route'] ?? 'N/A' }}</span></td>
                                <td><span class="badge bg-info">{{ $conflict['new_route'] ?? 'N/A' }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

    </div>
</div>
@endsection

