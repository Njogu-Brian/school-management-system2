@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @include('finance.partials.header', [
        'title' => 'Allocated Discounts',
        'icon' => 'bi bi-list-check',
        'subtitle' => 'Manage and approve discount allocations',
        'actions' => '
            <a href="' . route('finance.discounts.templates.index') . '" class="btn btn-finance btn-finance-outline">
                <i class="bi bi-file-earmark-text"></i> Templates
            </a>
            <a href="' . route('finance.discounts.approvals.index') . '" class="btn btn-finance btn-finance-outline">
                <i class="bi bi-check-circle"></i> Approvals
            </a>
        '
    ])

    @include('finance.invoices.partials.alerts')

    <!-- Tabs for Allocate / View Allocations -->
    <ul class="nav nav-tabs finance-tabs mb-4 finance-animate" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ request('tab') != 'allocate' ? 'active' : '' }}" id="allocations-tab" data-bs-toggle="tab" data-bs-target="#allocations" type="button" role="tab">
                <i class="bi bi-list-check me-2"></i> Allocations
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ request('tab') == 'allocate' ? 'active' : '' }}" id="allocate-tab" data-bs-toggle="tab" data-bs-target="#allocate" type="button" role="tab">
                <i class="bi bi-person-plus me-2"></i> Allocate New Discount
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- Allocations Tab -->
        <div class="tab-pane fade {{ request('tab') != 'allocate' ? 'show active' : '' }}" id="allocations" role="tabpanel">
            <!-- Quick Stats Cards -->
    <div class="row mb-4 finance-animate">
        <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
            <div class="finance-stat-card border-primary">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6>Total Allocations</h6>
                        <h3 class="mb-0">{{ $allocations->total() }}</h3>
                    </div>
                    <div class="text-primary" style="font-size: 2.5rem; opacity: 0.3;">
                        <i class="bi bi-list-check"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
            <div class="finance-stat-card border-success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6>Approved</h6>
                        <h3 class="mb-0 text-success">{{ $allocations->where('approval_status', 'approved')->count() }}</h3>
                    </div>
                    <div class="text-success" style="font-size: 2.5rem; opacity: 0.3;">
                        <i class="bi bi-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
            <div class="finance-stat-card border-warning">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6>Pending</h6>
                        <h3 class="mb-0 text-warning">{{ $allocations->where('approval_status', 'pending')->count() }}</h3>
                    </div>
                    <div class="text-warning" style="font-size: 2.5rem; opacity: 0.3;">
                        <i class="bi bi-clock-history"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
            <div class="finance-stat-card border-danger">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6>Rejected</h6>
                        <h3 class="mb-0 text-danger">{{ $allocations->where('approval_status', 'rejected')->count() }}</h3>
                    </div>
                    <div class="text-danger" style="font-size: 2.5rem; opacity: 0.3;">
                        <i class="bi bi-x-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="finance-filter-card finance-animate mb-4">
        <form method="GET" action="{{ route('finance.discounts.allocations.index') }}" class="row g-3">
            <div class="col-md-3">
                <label class="finance-form-label">Student</label>
                <select name="student_id" class="finance-form-select">
                        <option value="">All Students</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}" {{ request('student_id') == $student->id ? 'selected' : '' }}>
                                {{ $student->first_name }} {{ $student->last_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            <div class="col-md-2">
                <label class="finance-form-label">Term</label>
                <select name="term" class="finance-form-select">
                    <option value="">All Terms</option>
                    <option value="1" {{ request('term') == '1' ? 'selected' : '' }}>Term 1</option>
                    <option value="2" {{ request('term') == '2' ? 'selected' : '' }}>Term 2</option>
                    <option value="3" {{ request('term') == '3' ? 'selected' : '' }}>Term 3</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="finance-form-label">Year</label>
                <input type="number" name="year" class="finance-form-control" value="{{ request('year') }}" placeholder="e.g., 2025">
            </div>
            <div class="col-md-2">
                <label class="finance-form-label">Status</label>
                <select name="approval_status" class="finance-form-select">
                    <option value="">All</option>
                    <option value="pending" {{ request('approval_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('approval_status') == 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('approval_status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-finance btn-finance-primary">
                    <i class="bi bi-search"></i> Filter
                </button>
                <a href="{{ route('finance.discounts.allocations.index') }}" class="btn btn-finance btn-finance-outline">
                    <i class="bi bi-x-circle"></i> Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Bulk Actions Form -->
    <form id="bulkActionsForm" method="POST" action="">
        @csrf
        <div id="bulkRejectReason" class="finance-card finance-animate mb-3" style="display: none;">
            <div class="finance-card-body">
                <label class="finance-form-label">Rejection Reason <span class="text-danger">*</span></label>
                <textarea name="rejection_reason" class="finance-form-control" rows="2" required></textarea>
            </div>
        </div>

        <!-- Allocations Table -->
        <div class="finance-card finance-animate">
            <div class="finance-card-header d-flex justify-content-between align-items-center flex-wrap">
                <span><i class="bi bi-table me-2"></i> Allocations</span>
                <div id="bulkActions" style="display: none;" class="mt-2 mt-md-0">
                    <div class="btn-group flex-wrap">
                        <button type="submit" formaction="{{ route('finance.discounts.allocations.bulk-approve') }}" class="btn btn-sm btn-finance btn-finance-success">
                            <i class="bi bi-check-circle"></i> <span class="d-none d-md-inline">Approve Selected</span>
                        </button>
                        <button type="button" id="bulkRejectBtn" class="btn btn-sm btn-finance btn-finance-danger">
                            <i class="bi bi-x-circle"></i> <span class="d-none d-md-inline">Reject Selected</span>
                        </button>
                        <button type="button" id="clearSelection" class="btn btn-sm btn-finance btn-finance-outline">
                            <i class="bi bi-x"></i> <span class="d-none d-md-inline">Clear</span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="finance-table-wrapper">
                <div class="table-responsive">
                    <table class="finance-table">
                        <thead>
                            <tr>
                                <th width="40">
                                    <input type="checkbox" id="selectAll" title="Select All">
                                </th>
                                <th>Student</th>
                                <th>Template</th>
                                <th>Votehead</th>
                                <th>Term/Year</th>
                                <th class="text-end">Value</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($allocations as $allocation)
                            <tr>
                                <td>
                                    @if($allocation->approval_status === 'pending')
                                        <input type="checkbox" name="allocation_ids[]" value="{{ $allocation->id }}" class="allocation-checkbox">
                                    @endif
                                </td>
                                <td>
                                    @if($allocation->student)
                                        <strong>{{ $allocation->student->first_name }} {{ $allocation->student->last_name }}</strong>
                                        <br><small class="text-muted">{{ $allocation->student->admission_number }}</small>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($allocation->discountTemplate)
                                        <span class="badge bg-info">{{ $allocation->discountTemplate->name }}</span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($allocation->votehead)
                                        <span class="badge bg-secondary">{{ $allocation->votehead->name }}</span>
                                    @else
                                        <span class="text-muted">All</span>
                                    @endif
                                </td>
                                <td>
                                    <strong>Term {{ $allocation->term }}</strong> / {{ $allocation->year }}
                                    @if($allocation->academicYear)
                                        <br><small class="text-muted">{{ $allocation->academicYear->year }}</small>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($allocation->type === 'percentage')
                                        <strong class="text-primary">{{ number_format($allocation->value, 1) }}%</strong>
                                    @else
                                        <strong class="text-primary">Ksh {{ number_format($allocation->value, 2) }}</strong>
                                    @endif
                                </td>
                                <td>
                                    @if($allocation->approval_status === 'pending')
                                        <span class="badge bg-warning"><i class="bi bi-clock"></i> Pending</span>
                                    @elseif($allocation->approval_status === 'approved')
                                        <span class="badge bg-success"><i class="bi bi-check-circle"></i> Approved</span>
                                    @else
                                        <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Rejected</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $allocation->created_at->format('d M Y') }}
                                    @if($allocation->creator)
                                        <br><small class="text-muted">by {{ $allocation->creator->name }}</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('finance.discounts.show', $allocation) }}" class="btn btn-sm btn-outline-primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if($allocation->approval_status === 'pending')
                                            <form action="{{ route('finance.discounts.approve', $allocation) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success" title="Approve">
                                                    <i class="bi bi-check"></i>
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $allocation->id }}" title="Reject">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        @endif
                                        @if($allocation->approval_status === 'approved')
                                            <form action="{{ route('finance.discounts.allocations.reverse', $allocation) }}" 
                                                  method="POST" 
                                                  class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to reverse this discount allocation? This will remove the discount from all related invoices and recalculate them.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Reverse">
                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>

                                    <!-- Reject Modal -->
                                    <div class="modal fade" id="rejectModal{{ $allocation->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Reject Discount Allocation</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="{{ route('finance.discounts.reject', $allocation) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                                                            <textarea name="rejection_reason" class="form-control" rows="3" required></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-danger">Reject</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <p class="text-muted mb-0">No allocations found.</p>
                                    <a href="{{ route('finance.discounts.allocate') }}" class="btn btn-primary btn-sm mt-2">
                                        <i class="bi bi-plus-circle"></i> Allocate First Discount
                                    </a>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($allocations->hasPages())
            <div class="card-footer">
                {{ $allocations->links() }}
            </div>
            @endif
        </div>
    </form>
        </div>

        <!-- Allocate Tab -->
        <div class="tab-pane fade {{ request('tab') == 'allocate' ? 'show active' : '' }}" id="allocate" role="tabpanel">
            @php
                $templates = \App\Models\DiscountTemplate::where('is_active', true)->orderBy('name')->get();
                $students = \App\Models\Student::orderBy('first_name')->get();
                $voteheads = \App\Models\Votehead::orderBy('name')->get();
                $academicYears = \App\Models\AcademicYear::orderByDesc('year')->get();
                $currentYear = \App\Models\AcademicYear::where('is_active', true)->first();
            @endphp

            <form action="{{ route('finance.discounts.allocate.store') }}" method="POST">
                @csrf
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="finance-card finance-animate">
                            <div class="finance-card-header">
                                <i class="bi bi-info-circle me-2"></i> Allocation Details
                            </div>
                            <div class="finance-card-body">
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="finance-form-label">Discount Template <span class="text-danger">*</span></label>
                                        <select name="discount_template_id" id="discount_template_id" class="finance-form-select @error('discount_template_id') is-invalid @enderror" required>
                                            <option value="">-- Select Template --</option>
                                            @foreach($templates as $template)
                                                <option value="{{ $template->id }}" {{ old('discount_template_id', request('template')) == $template->id ? 'selected' : '' }}
                                                    data-scope="{{ $template->scope }}"
                                                    data-type="{{ $template->type }}"
                                                    data-value="{{ $template->value }}">
                                                    {{ $template->name }} 
                                                    @if($template->type === 'percentage')
                                                        ({{ $template->value }}%)
                                                    @else
                                                        (Ksh {{ number_format($template->value, 2) }})
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('discount_template_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-12">
                                        <label class="finance-form-label">Student <span class="text-danger">*</span></label>
                                        <select name="student_id" class="finance-form-select @error('student_id') is-invalid @enderror" required>
                                            <option value="">-- Select Student --</option>
                                            @foreach($students as $student)
                                                <option value="{{ $student->id }}" {{ old('student_id') == $student->id ? 'selected' : '' }}>
                                                    {{ $student->first_name }} {{ $student->last_name }} ({{ $student->admission_number }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('student_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="finance-form-label">Academic Year <span class="text-danger">*</span></label>
                                        <select name="academic_year_id" class="finance-form-select @error('academic_year_id') is-invalid @enderror" required>
                                            <option value="">-- Select Year --</option>
                                            @foreach($academicYears as $year)
                                                <option value="{{ $year->id }}" {{ old('academic_year_id', $currentYear?->id) == $year->id ? 'selected' : '' }}>
                                                    {{ $year->year }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('academic_year_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="finance-form-label">Year <span class="text-danger">*</span></label>
                                        <input type="number" name="year" class="finance-form-control @error('year') is-invalid @enderror" 
                                               value="{{ old('year', $currentYear?->year) }}" 
                                               placeholder="e.g., 2025" required>
                                        @error('year')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="finance-form-label">Term <span class="text-danger">*</span></label>
                                        <select name="term" class="finance-form-select @error('term') is-invalid @enderror" required>
                                            <option value="">-- Select Term --</option>
                                            <option value="1" {{ old('term') == '1' ? 'selected' : '' }}>Term 1</option>
                                            <option value="2" {{ old('term') == '2' ? 'selected' : '' }}>Term 2</option>
                                            <option value="3" {{ old('term') == '3' ? 'selected' : '' }}>Term 3</option>
                                        </select>
                                        @error('term')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="finance-form-label">Start Date</label>
                                        <input type="date" name="start_date" class="finance-form-control @error('start_date') is-invalid @enderror" 
                                               value="{{ old('start_date', date('Y-m-d')) }}">
                                        @error('start_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-12" id="votehead_selector" style="display: none;">
                                        <label class="finance-form-label">Voteheads <span class="text-danger">*</span> <span class="text-muted">(Select one or more)</span></label>
                                        <select name="votehead_ids[]" class="finance-form-select @error('votehead_ids') is-invalid @enderror" multiple size="6">
                                            @foreach($voteheads as $votehead)
                                                <option value="{{ $votehead->id }}" {{ in_array($votehead->id, old('votehead_ids', [])) ? 'selected' : '' }}>
                                                    {{ $votehead->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('votehead_ids')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted">Hold Ctrl (Windows) or Cmd (Mac) to select multiple voteheads</small>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="finance-form-label">End Date</label>
                                        <input type="date" name="end_date" class="finance-form-control @error('end_date') is-invalid @enderror" 
                                               value="{{ old('end_date') }}">
                                        @error('end_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted">Leave blank to use template expiry</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="finance-card finance-animate mb-4">
                            <div class="finance-card-header secondary">
                                <i class="bi bi-info-circle me-2"></i> Template Info
                            </div>
                            <div class="finance-card-body" id="template_info">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> Select a template to see details
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-finance btn-finance-primary">
                                <i class="bi bi-check-circle"></i> Allocate Discount
                            </button>
                            <a href="{{ route('finance.discounts.bulk-allocate-sibling') }}" class="btn btn-finance btn-finance-success">
                                <i class="bi bi-people"></i> Bulk Allocate Sibling Discounts
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Template info update for allocate tab
    const templateSelect = document.getElementById('discount_template_id');
    const voteheadSelector = document.getElementById('votehead_selector');
    const templateInfo = document.getElementById('template_info');

    if (templateSelect) {
        templateSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const scope = selectedOption.getAttribute('data-scope');
            const type = selectedOption.getAttribute('data-type');
            const value = selectedOption.getAttribute('data-value');

            if (voteheadSelector) {
                if (scope === 'votehead') {
                    voteheadSelector.style.display = 'block';
                    if (voteheadSelector.querySelector('select')) {
                        voteheadSelector.querySelector('select').required = true;
                    }
                } else {
                    voteheadSelector.style.display = 'none';
                    if (voteheadSelector.querySelector('select')) {
                        voteheadSelector.querySelector('select').required = false;
                    }
                }
            }

            if (templateInfo) {
                if (this.value) {
                    const templateName = selectedOption.text.split('(')[0].trim();
                    templateInfo.innerHTML = `
                    <div class="mb-2">
                        <strong>Template:</strong><br>
                        <span class="badge bg-info">${templateName}</span>
                    </div>
                    <div class="mb-2">
                        <strong>Scope:</strong><br>
                        <span class="badge bg-primary">${scope ? scope.charAt(0).toUpperCase() + scope.slice(1) : 'N/A'}</span>
                    </div>
                    <div class="mb-2">
                        <strong>Value:</strong><br>
                        <span class="text-primary fw-bold">${type === 'percentage' ? value + '%' : 'Ksh ' + parseFloat(value).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                    </div>
                    `;
                } else {
                    templateInfo.innerHTML = `
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Select a template to see details
                        </div>
                    `;
                }
            }
        });

        if (templateSelect.value) {
            templateSelect.dispatchEvent(new Event('change'));
        }
    }

    // Existing allocations page scripts
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.allocation-checkbox');
    const bulkActions = document.getElementById('bulkActions');
    const bulkRejectBtn = document.getElementById('bulkRejectBtn');
    const bulkRejectReason = document.getElementById('bulkRejectReason');
    const bulkActionsForm = document.getElementById('bulkActionsForm');
    const clearSelection = document.getElementById('clearSelection');

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateBulkActions();
        });
    }

    if (checkboxes.length > 0) {
        checkboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                if (selectAll) selectAll.checked = Array.from(checkboxes).every(c => c.checked);
                updateBulkActions();
            });
        });
    }

    function updateBulkActions() {
        const checked = Array.from(checkboxes).filter(cb => cb.checked);
        if (bulkActions) {
            if (checked.length > 0) {
                bulkActions.style.display = 'block';
            } else {
                bulkActions.style.display = 'none';
                if (bulkRejectReason) bulkRejectReason.style.display = 'none';
            }
        }
    }

    if (bulkRejectBtn) {
        bulkRejectBtn.addEventListener('click', function() {
            if (bulkRejectReason) {
                if (bulkRejectReason.style.display === 'none') {
                    bulkRejectReason.style.display = 'block';
                    bulkRejectReason.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                } else {
                    if (bulkActionsForm) {
                        bulkActionsForm.action = '{{ route('finance.discounts.allocations.bulk-reject') }}';
                        const reasonTextarea = bulkActionsForm.querySelector('textarea[name="rejection_reason"]');
                        if (reasonTextarea && reasonTextarea.value.trim()) {
                            bulkActionsForm.submit();
                        } else {
                            alert('Please provide a rejection reason.');
                        }
                    }
                }
            }
        });
    }

    if (clearSelection) {
        clearSelection.addEventListener('click', function() {
            checkboxes.forEach(cb => cb.checked = false);
            if (selectAll) selectAll.checked = false;
            updateBulkActions();
        });
    }
    
    // Activate allocate tab if tab parameter is set
    @if(request('tab') == 'allocate')
        const allocateTab = document.getElementById('allocate-tab');
        if (allocateTab) {
            const tab = new bootstrap.Tab(allocateTab);
            tab.show();
        }
    @endif
});
</script>
@endpush
@endsection
