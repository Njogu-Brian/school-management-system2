@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">
                    <a href="{{ route('transport.index') }}">Transport</a> / Import
                </div>
                <h1>Import Transport Assignments</h1>
                <p>Upload an Excel file to assign students to evening transport routes and vehicles.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('transport.import.template') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-download"></i> Download Template
                </a>
                <a href="{{ route('transport.index') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-arrow-left"></i> Back to Transport
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success mt-3">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger mt-3">
                <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            </div>
        @endif

        {{-- Upload Form --}}
        <div class="settings-card mt-3">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-upload me-2"></i> Upload Excel File</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('transport.import.preview') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">Excel File</label>
                        <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" 
                               accept=".xlsx,.xls,.csv" required>
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            Accepted formats: .xlsx, .xls, .csv (Max size: 10MB)
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Academic Year (Optional)</label>
                            <input type="number" name="year" class="form-control" 
                                   value="{{ request('year', date('Y')) }}" 
                                   min="2020" max="2100">
                            <div class="form-text">
                                Used for transport fee conflict detection. Defaults to current year.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Term (Optional)</label>
                            <select name="term" class="form-select">
                                <option value="">Auto-detect</option>
                                <option value="1" {{ request('term') == '1' ? 'selected' : '' }}>Term 1</option>
                                <option value="2" {{ request('term') == '2' ? 'selected' : '' }}>Term 2</option>
                                <option value="3" {{ request('term') == '3' ? 'selected' : '' }}>Term 3</option>
                            </select>
                            <div class="form-text">
                                Used for transport fee conflict detection. Defaults to current term.
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <h6 class="alert-heading"><i class="bi bi-info-circle me-2"></i>Excel File Format</h6>
                        <p class="mb-2">Your Excel file should have the following columns:</p>
                        <ul class="mb-0">
                            <li><strong>ADMISSION NO</strong> - Student's admission number (required)</li>
                            <li><strong>NAME</strong> - Student's full name (for reference)</li>
                            <li><strong>ROUTE</strong> - Drop-off point name (e.g., REGEN, RUKUBI, or OWN)</li>
                            <li><strong>CLASS</strong> - Student's class (for reference)</li>
                            <li><strong>VEHICLE</strong> - Vehicle and trip (e.g., "KDR TRIP 1", "KCB TRIP 2", or "OWN")</li>
                        </ul>
                    </div>

                    <div class="alert alert-warning">
                        <h6 class="alert-heading"><i class="bi bi-exclamation-triangle me-2"></i>Important Notes</h6>
                        <ul class="mb-0">
                            <li>Students marked as "OWN" will be skipped (they use their own transport)</li>
                            <li>If a student's route differs from the system, you'll be asked to resolve the conflict</li>
                            <li>Vehicles must already exist in the system (KDR, KCB, KAQ, KCA, KCF)</li>
                            <li>Trips (TRIP 1, TRIP 2, TRIP 3) will be created automatically if they don't exist</li>
                            <li>Drop-off points will be created automatically if they don't exist</li>
                        </ul>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-settings-primary">
                            <i class="bi bi-eye me-2"></i> Preview Import
                        </button>
                        <a href="{{ route('transport.index') }}" class="btn btn-ghost-strong">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Recent Imports --}}
        @if($recentImports->count() > 0)
        <div class="settings-card mt-3">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i> Recent Imports</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Filename</th>
                                <th>Imported By</th>
                                <th>Total Rows</th>
                                <th>Success</th>
                                <th>Updated</th>
                                <th>Errors</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentImports as $import)
                            <tr>
                                <td>{{ $import->created_at->format('M d, Y H:i') }}</td>
                                <td>{{ $import->filename }}</td>
                                <td>{{ $import->importedBy->name ?? 'N/A' }}</td>
                                <td>{{ $import->total_rows }}</td>
                                <td><span class="badge bg-success">{{ $import->success_count }}</span></td>
                                <td><span class="badge bg-info">{{ $import->updated_count }}</span></td>
                                <td>
                                    @if($import->error_count > 0)
                                        <span class="badge bg-danger">{{ $import->error_count }}</span>
                                    @else
                                        <span class="badge bg-secondary">0</span>
                                    @endif
                                </td>
                                <td>
                                    @if($import->status === 'completed')
                                        <span class="badge bg-success">Completed</span>
                                    @elseif($import->status === 'failed')
                                        <span class="badge bg-danger">Failed</span>
                                    @else
                                        <span class="badge bg-warning">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('transport.import.log', $import->id) }}" 
                                       class="btn btn-sm btn-ghost-strong">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                </td>
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

