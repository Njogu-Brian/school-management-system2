@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @include('finance.partials.header', [
        'title' => 'New Credit / Debit Adjustment',
        'icon' => 'bi bi-arrow-left-right',
        'subtitle' => 'Create credit or debit adjustments for student invoices',
        'actions' => '<a href="' . route('finance.journals.index') . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-arrow-left"></i> Back to Adjustments</a>'
    ])

    @includeIf('finance.invoices.partials.alerts')

    <div class="finance-card finance-animate">
        <div class="finance-card-header">
            <i class="bi bi-file-earmark-text me-2"></i> Adjustment Information
        </div>
        <div class="finance-card-body">
            <form method="POST" action="{{ route('finance.journals.store') }}" class="row g-3">
                @csrf

                {{-- Student picker (uses modal) --}}
                <div class="col-md-12">
                    <label class="finance-form-label">Student <span class="text-danger">*</span></label>
                    <div class="input-group">
                        {{-- This hidden input is what the controller reads --}}
                        <input type="hidden" id="selectedStudentId" name="student_id" value="{{ old('student_id') }}" required>

                        {{-- Readonly display field for name (filled by modal) --}}
                        <input type="text" id="selectedStudentName" class="finance-form-control"
                               value="{{ old('student_id') ? (optional(\App\Models\Student::find(old('student_id')))->full_name . ' (' . optional(\App\Models\Student::find(old('student_id')))->admission_number . ')') : '' }}"
                               placeholder="Search by name or admission #"
                               readonly>

                        <button type="button" class="btn btn-finance btn-finance-primary"
                                data-bs-toggle="modal" data-bs-target="#studentSearchModal">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                    <small class="text-muted">Pick a student using the search button</small>
                </div>

                <div class="col-md-6 col-lg-4">
                    <label class="finance-form-label">Votehead <span class="text-danger">*</span></label>
                    <select name="votehead_id" class="finance-form-select" required>
                        <option value="">-- Select Votehead --</option>
                        @foreach(\App\Models\Votehead::orderBy('name')->get() as $vh)
                            <option value="{{ $vh->id }}" @selected(old('votehead_id')==$vh->id)>{{ $vh->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 col-lg-2">
                    <label class="finance-form-label">Year <span class="text-danger">*</span></label>
                    <input type="number" name="year" class="finance-form-control"
                           value="{{ old('year', now()->year) }}" required>
                </div>

                <div class="col-md-6 col-lg-2">
                    <label class="finance-form-label">Term <span class="text-danger">*</span></label>
                    <select name="term" class="finance-form-select" required>
                        @for($i=1;$i<=3;$i++)
                            <option value="{{ $i }}" @selected(old('term')==$i)>Term {{ $i }}</option>
                        @endfor
                    </select>
                </div>

                <div class="col-md-6 col-lg-2">
                    <label class="finance-form-label">Type <span class="text-danger">*</span></label>
                    <select name="type" class="finance-form-select" required>
                        <option value="debit"  @selected(old('type')==='debit')>Debit (+)</option>
                        <option value="credit" @selected(old('type')==='credit')>Credit (-)</option>
                    </select>
                </div>

                <div class="col-md-6 col-lg-2">
                    <label class="finance-form-label">Amount <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text" style="background: var(--finance-primary-color); color: white; border-color: var(--finance-primary-color);">Ksh</span>
                        <input type="number" step="0.01" min="0.01" name="amount"
                               class="finance-form-control" value="{{ old('amount') }}" required>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <label class="finance-form-label">Effective Date</label>
                    <input type="date" name="effective_date" class="finance-form-control"
                           value="{{ old('effective_date') }}">
                    <small class="text-muted">Leave empty to apply today</small>
                </div>

                <div class="col-md-12">
                    <label class="finance-form-label">Reason <span class="text-danger">*</span></label>
                    <input type="text" name="reason" class="finance-form-control" maxlength="255"
                           value="{{ old('reason') }}" required placeholder="Enter reason for this adjustment">
                </div>

                <div class="col-12 mt-4">
                    <button class="btn btn-finance btn-finance-primary" type="submit">
                        <i class="bi bi-check-circle"></i> Create & Apply
                    </button>

                    <a class="btn btn-finance btn-finance-outline" href="{{ route('finance.journals.index') }}">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>

                    <a class="btn btn-finance btn-finance-outline" href="{{ route('finance.journals.bulk.form') }}">
                        <i class="bi bi-upload"></i> Bulk Import (Excel/CSV)
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Include the student search modal partial --}}
@include('partials.student_search_modal')
@endsection

