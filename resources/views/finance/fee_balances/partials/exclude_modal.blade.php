{{-- Modal: Select students to EXCLUDE from fee balance export --}}
<div class="modal fade" id="feeBalanceExcludeModal" tabindex="-1" aria-labelledby="feeBalanceExcludeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="feeBalanceExcludeModalLabel">
                    <i class="bi bi-person-x"></i> Exclude Students from Export
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Select students to exclude from the export/print. They will not appear in the PDF or CSV.</p>
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" id="feeBalanceExcludeSearchInput" class="form-control" placeholder="Search by name, admission number, or class...">
                    </div>
                </div>
                <div class="d-flex gap-2 mb-3 flex-wrap">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="feeBalanceExcludeSelectAll"><i class="bi bi-check-all"></i> Select All</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="feeBalanceExcludeClearAll"><i class="bi bi-x-circle"></i> Clear All</button>
                    <span class="badge bg-secondary ms-auto align-self-center" id="feeBalanceExcludeCount">0 excluded</span>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Filter by Class</label>
                    <select id="feeBalanceExcludeClassFilter" class="form-select form-select-sm">
                        <option value="">All Classes</option>
                        @foreach($classrooms as $classroom)
                            <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="feeBalanceExcludeListContainer" style="max-height: 400px; overflow-y: auto;">
                    <div class="list-group" id="feeBalanceExcludeList">
                        @foreach($students as $student)
                            <label class="list-group-item list-group-item-action fee-balance-exclude-item"
                                   data-student-id="{{ $student['id'] }}"
                                   data-student-name="{{ strtolower($student['full_name'] ?? '') }}"
                                   data-admission="{{ strtolower($student['admission_number'] ?? '') }}"
                                   data-class-id="{{ $student['classroom_id'] ?? '' }}"
                                   data-class-name="{{ strtolower($student['classroom'] ?? '') }}">
                                <div class="d-flex align-items-center">
                                    <input class="form-check-input me-3 fee-balance-exclude-checkbox" type="checkbox" value="{{ $student['id'] }}" id="fb_exclude_{{ $student['id'] }}">
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">{{ $student['full_name'] ?? 'N/A' }}</div>
                                        <small class="text-muted">{{ $student['admission_number'] ?? 'N/A' }} @if(!empty($student['classroom']))| {{ $student['classroom'] }}@endif</small>
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div id="feeBalanceExcludeNoResults" class="alert alert-info d-none mt-3"><i class="bi bi-info-circle"></i> No students found matching your search.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="feeBalanceExcludeConfirm"><i class="bi bi-check-lg"></i> Confirm</button>
            </div>
        </div>
    </div>
</div>
