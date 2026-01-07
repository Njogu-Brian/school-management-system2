@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
    <style>
        .conflict-row {
            background-color: #fff3cd;
        }
        .error-row {
            background-color: #f8d7da;
        }
        .ready-row {
            background-color: #d1e7dd;
        }
        .skipped-row {
            background-color: #e2e3e5;
        }
    </style>
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header">
            <div class="crumb">
                <a href="{{ route('transport.dashboard') }}">Transport</a> / 
                <a href="{{ route('transport.import.form') }}">Import</a> / Preview
            </div>
            <h1>Import Preview</h1>
            <p>Review the data before importing. Resolve any conflicts below.</p>
        </div>

        {{-- Summary Cards --}}
        <div class="row g-3 mt-3">
            <div class="col-md-3">
                <div class="settings-card">
                    <div class="card-body text-center">
                        <div class="text-success fw-bold fs-3">{{ count(array_filter($previewData, fn($d) => $d['status'] === 'ready')) }}</div>
                        <div class="text-muted small">Ready to Import</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="settings-card">
                    <div class="card-body text-center">
                        <div class="text-warning fw-bold fs-3">{{ count($conflicts) }}</div>
                        <div class="text-muted small">Conflicts</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="settings-card">
                    <div class="card-body text-center">
                        <div class="text-danger fw-bold fs-3">{{ count($errors) }}</div>
                        <div class="text-muted small">Errors</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="settings-card">
                    <div class="card-body text-center">
                        <div class="text-secondary fw-bold fs-3">{{ $skipped }}</div>
                        <div class="text-muted small">Skipped (OWN)</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Missing Students - Manual Linking Required --}}
        @if(isset($missingStudents) && count($missingStudents) > 0)
        <div class="settings-card mt-3">
            <div class="card-header bg-warning">
                <h5 class="mb-0"><i class="bi bi-person-x me-2"></i> Students Not Found - Manual Linking Required</h5>
            </div>
            <div class="card-body">
                <p class="mb-3">The following students were not found by name. Please search and select the correct student from the system:</p>
                
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Row</th>
                                <th>Excel Name</th>
                                <th>Route</th>
                                <th>Vehicle</th>
                                <th>Search & Link Student</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($missingStudents as $index => $missing)
                            <tr>
                                <td>{{ $missing['row'] }}</td>
                                <td><strong>{{ $missing['name'] }}</strong></td>
                                <td><span class="badge bg-info">{{ $missing['route'] }}</span></td>
                                <td><span class="badge bg-secondary">{{ $missing['vehicle'] }}</span></td>
                                <td>
                                    <input type="hidden" name="student_link_data[{{ $missing['row'] }}]" 
                                           value="{{ json_encode([
                                               'name' => $missing['name'],
                                               'route' => $missing['route'],
                                               'vehicle' => $missing['vehicle']
                                           ]) }}">
                                    <div class="input-group input-group-sm">
                                        <input type="text" 
                                               class="form-control student-search" 
                                               placeholder="Search by name or admission..."
                                               data-row="{{ $missing['row'] }}"
                                               autocomplete="off">
                                        <select name="student_links[{{ $missing['row'] }}]" 
                                                class="form-select student-select" 
                                                id="student-select-{{ $missing['row'] }}"
                                                style="display:none;">
                                            <option value="">-- Select Student --</option>
                                        </select>
                                    </div>
                                    <div class="search-results mt-1" id="search-results-{{ $missing['row'] }}" style="display:none;"></div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        @if(isset($feeConflicts) && count($feeConflicts) > 0)
        <div class="settings-card mt-3">
            <div class="card-header bg-info">
                <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i> Transport Fee Conflicts Detected</h5>
            </div>
            <div class="card-body">
                <p class="mb-3">The following students have transport fees with different drop-off points. You can choose to sync transport fees with the new routes:</p>
                
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Admission No</th>
                                <th>Student Name</th>
                                <th>Fee Drop-off Point</th>
                                <th>Excel Route</th>
                                <th>Current Fee Amount</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($feeConflicts as $conflict)
                            <tr>
                                <td>{{ $conflict['admission_number'] }}</td>
                                <td>{{ $conflict['student_name'] }}</td>
                                <td><span class="badge bg-primary">{{ $conflict['existing_fee_route'] }}</span></td>
                                <td><span class="badge bg-info">{{ $conflict['new_route'] }}</span></td>
                                <td>KES {{ number_format($conflict['fee_amount'], 2) }}</td>
                                <td>
                                    <input type="hidden" name="fee_conflict_data[{{ $conflict['student_id'] }}]" 
                                           value="{{ json_encode([
                                               'transport_fee_id' => $conflict['transport_fee_id'],
                                               'drop_off_point_id' => $conflict['drop_off_point_id'],
                                               'drop_off_point_name' => $conflict['new_route']
                                           ]) }}">
                                    <select name="fee_conflict_resolutions[{{ $conflict['student_id'] }}]" 
                                            class="form-select form-select-sm">
                                        <option value="keep_fee">Keep Fee Route (No Change)</option>
                                        <option value="update_fee">Update Fee to Excel Route</option>
                                    </select>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        {{-- Conflicts Resolution --}}
        @if(count($conflicts) > 0)
        <div class="settings-card mt-3">
            <div class="card-header bg-warning">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i> Route Conflicts - Action Required</h5>
            </div>
            <div class="card-body">
                <p class="mb-3">The following students have different routes in the system vs the Excel file. Please choose which one to use:</p>
                
                <form action="{{ route('transport.import.process') }}" method="POST" id="importForm">
                    @csrf
                    <input type="hidden" name="filename" value="{{ $filename }}">
                    <input type="hidden" name="year" value="{{ $year ?? '' }}">
                    <input type="hidden" name="term" value="{{ $term ?? '' }}">
                    
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Row</th>
                                    <th>Admission No</th>
                                    <th>Student Name</th>
                                    <th>System Route</th>
                                    <th>Excel Route</th>
                                    <th>Resolution</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($conflicts as $conflict)
                                <tr class="conflict-row">
                                    <td>{{ $conflict['row'] }}</td>
                                    <td>{{ $conflict['admission_number'] }}</td>
                                    <td>{{ $conflict['student_name'] }}</td>
                                    <td><span class="badge bg-primary">{{ $conflict['existing_route'] }}</span></td>
                                    <td><span class="badge bg-info">{{ $conflict['new_route'] }}</span></td>
                                    <td>
                                        <input type="hidden" name="conflict_data[{{ $conflict['student_id'] }}]" 
                                               value="{{ json_encode(['drop_off_point_id' => $conflict['drop_off_point_id']]) }}">
                                        <select name="conflict_resolutions[{{ $conflict['student_id'] }}]" 
                                                class="form-select form-select-sm" required>
                                            <option value="">-- Choose --</option>
                                            <option value="use_system">Use System ({{ $conflict['existing_route'] }})</option>
                                            <option value="use_excel">Use Excel ({{ $conflict['new_route'] }})</option>
                                        </select>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
        </div>
        @endif

        {{-- Preview Data --}}
        <div class="settings-card mt-3">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-table me-2"></i> Preview Data</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Row</th>
                                <th>Admission No</th>
                                <th>Student Name</th>
                                <th>Route</th>
                                <th>Class</th>
                                <th>Vehicle</th>
                                <th>Trip</th>
                                <th>Status</th>
                                <th>Message</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($previewData as $data)
                            <tr class="{{ 
                                $data['status'] === 'conflict' ? 'conflict-row' : 
                                ($data['status'] === 'ready' ? 'ready-row' : 
                                ($data['status'] === 'skipped' ? 'skipped-row' : '')) 
                            }}">
                                <td>{{ $data['row'] }}</td>
                                <td>{{ $data['admission_number'] }}</td>
                                <td>{{ $data['student_name'] }}</td>
                                <td>{{ $data['route'] }}</td>
                                <td>{{ $data['class'] }}</td>
                                <td>{{ $data['vehicle'] }}</td>
                                <td>{{ $data['trip'] }}</td>
                                <td>
                                    @if($data['status'] === 'ready')
                                        <span class="badge bg-success">Ready</span>
                                    @elseif($data['status'] === 'conflict')
                                        <span class="badge bg-warning">Conflict</span>
                                    @elseif($data['status'] === 'skipped')
                                        <span class="badge bg-secondary">Skipped</span>
                                    @elseif($data['status'] === 'fee_conflict')
                                        <span class="badge bg-info">Fee Conflict</span>
                                    @else
                                        <span class="badge bg-info">{{ ucfirst($data['status']) }}</span>
                                    @endif
                                </td>
                                <td><small>{{ $data['message'] }}</small></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Errors --}}
        @if(count($errors) > 0)
        <div class="settings-card mt-3">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-x-circle me-2"></i> Errors</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Row</th>
                                <th>Error Message</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($errors as $error)
                            <tr class="error-row">
                                <td>{{ $error['row'] }}</td>
                                <td>{{ $error['message'] }}</td>
                                <td><small><code>{{ json_encode($error['data']) }}</code></small></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        {{-- Transport Fee Sync Option --}}
        @if(isset($feeConflicts) && count($feeConflicts) > 0)
        <div class="settings-card mt-3">
            <div class="card-body">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="sync_transport_fees" id="sync_transport_fees" value="1" checked>
                    <label class="form-check-label" for="sync_transport_fees">
                        <strong>Sync Transport Fees</strong> - Update transport fee drop-off points to match Excel routes
                    </label>
                    <div class="form-text">
                        When enabled, transport fees will be updated to use the new drop-off points from Excel. 
                        Fee amounts will be preserved. Only applies to students with fee conflicts above.
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Action Buttons --}}
        <div class="d-flex gap-2 mt-3 mb-4">
            @if(count($conflicts) > 0)
                <button type="submit" form="importForm" class="btn btn-settings-primary">
                    <i class="bi bi-check-circle me-2"></i> Resolve Conflicts & Import
                </button>
            @elseif(count($errors) === 0)
                <form action="{{ route('transport.import.process') }}" method="POST" id="importFormNoConflicts" class="d-inline">
                    @csrf
                    <input type="hidden" name="filename" value="{{ $filename }}">
                    <input type="hidden" name="year" value="{{ $year ?? '' }}">
                    <input type="hidden" name="term" value="{{ $term ?? '' }}">
                    @if(isset($feeConflicts) && count($feeConflicts) > 0)
                        <input type="hidden" name="sync_transport_fees" value="1">
                        @foreach($feeConflicts as $conflict)
                            <input type="hidden" name="fee_conflict_data[{{ $conflict['student_id'] }}]" 
                                   value="{{ json_encode([
                                       'transport_fee_id' => $conflict['transport_fee_id'],
                                       'drop_off_point_id' => $conflict['drop_off_point_id'],
                                       'drop_off_point_name' => $conflict['new_route']
                                   ]) }}">
                            <input type="hidden" name="fee_conflict_resolutions[{{ $conflict['student_id'] }}]" value="update_fee">
                        @endforeach
                    @endif
                    <button type="submit" class="btn btn-settings-primary">
                        <i class="bi bi-upload me-2"></i> Proceed with Import
                    </button>
                </form>
            @endif
            <a href="{{ route('transport.import.form') }}" class="btn btn-ghost-strong">
                <i class="bi bi-arrow-left me-2"></i> Back to Upload
            </a>
        </div>

    </div>
</div>

@push('scripts')
<script>
    // Student search functionality
    let searchTimeout;
    document.querySelectorAll('.student-search').forEach(input => {
        input.addEventListener('input', function() {
            const row = this.dataset.row;
            const searchTerm = this.value.trim();
            const resultsDiv = document.getElementById(`search-results-${row}`);
            const selectElement = document.getElementById(`student-select-${row}`);
            
            clearTimeout(searchTimeout);
            
            if (searchTerm.length < 2) {
                resultsDiv.style.display = 'none';
                resultsDiv.innerHTML = '';
                return;
            }
            
            searchTimeout = setTimeout(() => {
                // Search for students
                fetch(`/api/students/search?q=${encodeURIComponent(searchTerm)}`)
                    .then(response => response.json())
                    .then(students => {
                        if (students.length === 0) {
                            resultsDiv.innerHTML = '<small class="text-muted">No students found</small>';
                            resultsDiv.style.display = 'block';
                            return;
                        }
                        
                        let html = '<div class="list-group">';
                        students.forEach(student => {
                            html += `<button type="button" class="list-group-item list-group-item-action py-2 student-result" 
                                            data-id="${student.id}" 
                                            data-name="${student.name}"
                                            data-admission="${student.admission_number}"
                                            data-class="${student.class_name || ''}"
                                            data-row="${row}">
                                        <strong>${student.name}</strong> 
                                        <span class="badge bg-secondary">${student.admission_number}</span>
                                        ${student.class_name ? `<span class="badge bg-info">${student.class_name}</span>` : ''}
                                    </button>`;
                        });
                        html += '</div>';
                        
                        resultsDiv.innerHTML = html;
                        resultsDiv.style.display = 'block';
                        
                        // Add click handlers
                        resultsDiv.querySelectorAll('.student-result').forEach(btn => {
                            btn.addEventListener('click', function() {
                                const studentId = this.dataset.id;
                                const studentName = this.dataset.name;
                                const row = this.dataset.row;
                                
                                // Update select element
                                const select = document.getElementById(`student-select-${row}`);
                                select.innerHTML = `<option value="${studentId}" selected>${studentName}</option>`;
                                select.style.display = 'block';
                                
                                // Update search input
                                const searchInput = document.querySelector(`.student-search[data-row="${row}"]`);
                                searchInput.value = studentName;
                                searchInput.style.display = 'none';
                                
                                // Hide results
                                resultsDiv.style.display = 'none';
                            });
                        });
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                        resultsDiv.innerHTML = '<small class="text-danger">Search failed</small>';
                        resultsDiv.style.display = 'block';
                    });
            }, 300);
        });
    });

    // Validate that all conflicts are resolved before submitting
    document.getElementById('importForm')?.addEventListener('submit', function(e) {
        const selects = this.querySelectorAll('select[name^="conflict_resolutions"]');
        let allResolved = true;
        
        selects.forEach(select => {
            if (!select.value) {
                allResolved = false;
                select.classList.add('is-invalid');
            } else {
                select.classList.remove('is-invalid');
            }
        });
        
        if (!allResolved) {
            e.preventDefault();
            alert('Please resolve all conflicts before importing.');
        }
    });
</script>
@endpush
@endsection

