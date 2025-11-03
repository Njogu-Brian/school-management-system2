@extends('layouts.app')

@section('content')
<div class="container">
    <h3 class="mb-3">New Credit / Debit Adjustment</h3>

    @includeIf('finance.invoices.partials.alerts')

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('finance.journals.store') }}" class="row g-3">
                @csrf

                {{-- Student picker (uses modal) --}}
                <div class="col-md-6">
                    <label class="form-label">Student</label>
                    <div class="input-group">
                        {{-- This hidden input is what the controller reads --}}
                        <input type="hidden" id="selectedStudentId" name="student_id" value="{{ old('student_id') }}" required>

                        {{-- Readonly display field for name (filled by modal) --}}
                        <input type="text" id="selectedStudentName" class="form-control"
                               value="{{ old('student_id') ? (optional(\App\Models\Student::find(old('student_id')))->full_name . ' (' . optional(\App\Models\Student::find(old('student_id')))->admission_number . ')') : '' }}"
                               placeholder="Search by name or admission #"
                               readonly>

                        <button type="button" class="btn btn-outline-secondary"
                                data-bs-toggle="modal" data-bs-target="#studentSearchModal">
                            Search
                        </button>
                    </div>
                    <div class="form-text">Pick a student using the search button.</div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Votehead</label>
                    <select name="votehead_id" class="form-select" required>
                        @foreach(\App\Models\Votehead::orderBy('name')->get() as $vh)
                            <option value="{{ $vh->id }}" @selected(old('votehead_id')==$vh->id)>{{ $vh->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-1">
                    <label class="form-label">Year</label>
                    <input type="number" name="year" class="form-control"
                           value="{{ old('year', now()->year) }}" required>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Term</label>
                    <select name="term" class="form-select" required>
                        @for($i=1;$i<=3;$i++)
                            <option value="{{ $i }}" @selected(old('term')==$i)>Term {{ $i }}</option>
                        @endfor
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select" required>
                        <option value="debit"  @selected(old('type')==='debit')>Debit (+)</option>
                        <option value="credit" @selected(old('type')==='credit')>Credit (-)</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.01" min="0.01" name="amount"
                           class="form-control" value="{{ old('amount') }}" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Effective Date</label>
                    <input type="date" name="effective_date" class="form-control"
                           value="{{ old('effective_date') }}">
                    <div class="form-text">Leave empty to apply today.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Reason</label>
                    <input type="text" name="reason" class="form-control" maxlength="255"
                           value="{{ old('reason') }}" required>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-plus-circle"></i> Create & Apply
                    </button>

                    <a class="btn btn-outline-secondary" href="{{ route('finance.invoices.index') }}">
                        Back to Invoices
                    </a>

                    <a class="btn btn-outline-info" href="{{ route('finance.journals.bulk.form') }}">
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
