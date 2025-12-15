@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-upload me-2"></i>Import Voteheads
                    </h4>
                    <div>
                        <a href="{{ route('finance.voteheads.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Back to Voteheads
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
                                <div class="table-responsive mt-3">
                                    <table class="table table-sm table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Row</th>
                                                <th>Error</th>
                                                <th>Data</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($result['errors'] as $error)
                                                <tr>
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
                                                        <pre class="mb-0 small">{{ json_encode($error['data'] ?? [], JSON_PRETTY_PRINT) }}</pre>
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
                                        <i class="fas fa-file-excel me-2"></i>Upload Excel/CSV File
                                    </h5>
                                    <p class="text-muted mb-3">
                                        Upload an Excel file (.xlsx) or CSV file containing votehead data. The Excel template includes dropdown menus for easy selection.
                                    </p>

                                    <form action="{{ route('finance.voteheads.process-import') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="csv_file" class="form-label">File (Excel or CSV)</label>
                                            <input type="file" class="form-control @error('csv_file') is-invalid @enderror" 
                                                   id="csv_file" name="csv_file" accept=".csv,.txt,.xlsx,.xls" required>
                                            @error('csv_file')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="form-text text-muted">Maximum file size: 10MB. Excel template (.xlsx) includes dropdown menus for categories. CSV format also accepted.</small>
                                        </div>

                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-upload me-1"></i>Import Voteheads
                                            </button>
                                            <a href="{{ route('finance.voteheads.download-template') }}" class="btn btn-outline-primary">
                                                <i class="fas fa-download me-1"></i>Download Excel Template
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
                                    
                                    @if(isset($categories) && $categories->isNotEmpty())
                                        <div class="alert alert-info small mb-3">
                                            <strong>Available Categories:</strong>
                                            <ul class="mb-0 mt-2">
                                                @foreach($categories as $category)
                                                    <li>{{ $category->name }}</li>
                                                @endforeach
                                            </ul>
                                            <small class="d-block mt-2">
                                                <i class="fas fa-lightbulb me-1"></i>
                                                To use dropdowns in Excel: Open CSV in Excel, select category column, 
                                                go to Data → Data Validation → List, and paste category names.
                                            </small>
                                        </div>
                                    @endif
                                    <ul class="small mb-0">
                                        <li>Download the template CSV file</li>
                                        <li>Fill in the votehead data</li>
                                        <li>Save the file as CSV format</li>
                                        <li>Upload the file using the form</li>
                                    </ul>

                                    <hr>

                                    <h6 class="mt-3">Required Fields:</h6>
                                    <ul class="small mb-0">
                                        <li><strong>name</strong> - Votehead name (required)</li>
                                        <li><strong>charge_type</strong> - per_student, once, once_annually, or per_family (required)</li>
                                    </ul>

                                    <h6 class="mt-3">Optional Fields:</h6>
                                    <ul class="small mb-0">
                                        <li><strong>code</strong> - Unique code (auto-generated from name if empty)</li>
                                        <li><strong>description</strong> - Description</li>
                                        <li><strong>category</strong> - Category (see valid categories in template comments)</li>
                                        <li><strong>is_mandatory</strong> - 1 or 0</li>
                                        <li><strong>is_optional</strong> - 1 or 0</li>
                                        <li><strong>is_active</strong> - 1 or 0 (default: 1)</li>
                                    </ul>

                                    <div class="alert alert-info mt-3 small mb-0">
                                        <strong>Note:</strong> The code will be automatically generated from the name if left empty (e.g., "Tuition Fees" → "TUITION_FEES").
                                    </div>

                                    <div class="alert alert-warning mt-3 small mb-0">
                                        <strong>Update Behavior:</strong> If a votehead with the same code or name exists, it will be updated instead of creating a new one.
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

