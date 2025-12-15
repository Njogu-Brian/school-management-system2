@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-upload me-2"></i>Import Fee Structures
                    </h4>
                    <div>
                        <a href="{{ route('finance.fee-structures.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Back to Fee Structures
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('warning'))
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('warning') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('import_result'))
                        @php $result = session('import_result'); @endphp
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle me-2"></i>Import Summary</h5>
                            <ul class="mb-0">
                                <li><strong>Successfully Created:</strong> {{ $result['success'] }}</li>
                                <li><strong>Updated:</strong> {{ $result['updated'] }}</li>
                                <li><strong>Failed:</strong> {{ $result['failed'] }}</li>
                            </ul>
                        </div>

                        @if(!empty($result['errors']))
                            <div class="alert alert-danger mt-3">
                                <h5><i class="fas fa-times-circle me-2"></i>Import Errors</h5>
                                <div class="table-responsive mt-3" style="max-height: 400px; overflow-y: auto;">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light sticky-top">
                                            <tr>
                                                <th>Structure</th>
                                                <th>Row</th>
                                                <th>Error</th>
                                                <th>Data</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($result['errors'] as $error)
                                                <tr>
                                                    <td>{{ $error['structure'] ?? 'N/A' }}</td>
                                                    <td>{{ $error['row'] ?? 'N/A' }}</td>
                                                    <td>
                                                        @if(isset($error['error']))
                                                            {{ $error['error'] }}
                                                        @elseif(isset($error['errors']))
                                                            <ul class="mb-0">
                                                                @foreach($error['errors'] as $err)
                                                                    <li>{{ $err }}</li>
                                                                @endforeach
                                                            </ul>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <pre class="mb-0 small" style="max-width: 300px; overflow-x: auto;">{{ json_encode($error['data'] ?? [], JSON_PRETTY_PRINT) }}</pre>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    @endif

                    <div class="row">
                        <div class="col-md-8">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="fas fa-file-csv me-2"></i>Upload CSV File
                                    </h5>
                                    <p class="text-muted mb-3">
                                        Upload a CSV file containing fee structure data. Download the template below to see the required format.
                                    </p>

                                    <form action="{{ route('finance.fee-structures.process-import') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="csv_file" class="form-label">CSV File</label>
                                            <input type="file" class="form-control @error('csv_file') is-invalid @enderror" 
                                                   id="csv_file" name="csv_file" accept=".csv,.txt" required>
                                            @error('csv_file')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="form-text text-muted">Maximum file size: 10MB. File must be in CSV format.</small>
                                        </div>

                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-upload me-1"></i>Import Fee Structures
                                            </button>
                                            <a href="{{ route('finance.fee-structures.download-template') }}" class="btn btn-outline-primary">
                                                <i class="fas fa-download me-1"></i>Download Template
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="fas fa-info-circle me-2"></i>Import Instructions
                                    </h5>
                                    <ul class="small mb-0">
                                        <li>Download the template CSV file</li>
                                        <li>Fill in the fee structure data</li>
                                        <li>Multiple rows with same structure identifier will be grouped</li>
                                        <li>Save the file as CSV format</li>
                                        <li>Upload the file using the form</li>
                                    </ul>

                                    <hr>

                                    <h6 class="mt-3">Required Fields:</h6>
                                    <ul class="small mb-0">
                                        <li><strong>classroom</strong> - Classroom name or ID (required)</li>
                                        <li><strong>votehead</strong> - Votehead name, code, or ID (required for charges)</li>
                                        <li><strong>term_1, term_2, term_3</strong> - Amounts for each term</li>
                                    </ul>

                                    <h6 class="mt-3">Optional Fields:</h6>
                                    <ul class="small mb-0">
                                        <li><strong>academic_year</strong> - Academic year</li>
                                        <li><strong>term</strong> - Term name</li>
                                        <li><strong>stream</strong> - Stream name</li>
                                        <li><strong>structure_name</strong> - Custom structure name</li>
                                        <li><strong>votehead_code</strong> - Votehead code (alternative to votehead)</li>
                                        <li><strong>is_active</strong> - 1 or 0 (default: 1)</li>
                                    </ul>

                                    <div class="alert alert-info mt-3 small mb-0">
                                        <strong>Note:</strong> Rows with the same classroom, academic_year, term, and stream will be grouped into one fee structure. Each row represents a charge (votehead) for that structure.
                                    </div>

                                    <div class="alert alert-warning mt-3 small mb-0">
                                        <strong>Warning:</strong> If a fee structure already exists for the same classroom/year/term/stream combination, it will be updated and all existing charges will be replaced.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Reference Tables -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Available Classrooms</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead class="table-light sticky-top">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Name</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($classrooms as $classroom)
                                                    <tr>
                                                        <td>{{ $classroom->id }}</td>
                                                        <td>{{ $classroom->name }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Available Voteheads</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead class="table-light sticky-top">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Code</th>
                                                    <th>Name</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($voteheads as $votehead)
                                                    <tr>
                                                        <td>{{ $votehead->id }}</td>
                                                        <td>{{ $votehead->code ?? '-' }}</td>
                                                        <td>{{ $votehead->name }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

