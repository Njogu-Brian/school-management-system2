@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'Manage Fee Structure',
        'icon' => 'bi bi-table',
        'subtitle' => 'Configure fee structures for classes by term'
    ])

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show finance-animate" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show finance-animate" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Class & category selection --}}
    <div class="finance-card finance-animate mb-4 shadow-sm rounded-4 border-0">
        <div class="finance-card-header d-flex align-items-center gap-2">
            <i class="bi bi-funnel"></i> <span>Select Class & Category</span>
        </div>
        <div class="finance-card-body p-4">
            <form method="GET" action="{{ route('finance.fee-structures.manage') }}">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="finance-form-label">Select Class</label>
                        <select name="classroom_id" class="finance-form-select" required onchange="this.form.submit()">
                            <option value="">-- Choose Classroom --</option>
                            @foreach($classrooms as $class)
                                <option value="{{ $class->id }}" {{ $selectedClassroom == $class->id ? 'selected' : '' }}>
                                    {{ $class->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="finance-form-label">Student Category <span class="text-danger">*</span></label>
                        <select name="student_category_id" class="finance-form-select" required onchange="this.form.submit()">
                            <option value="">-- Choose Category --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ ($selectedCategory ?? '') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Fee structures must be defined per specific category.</small>
                    </div>
                    <div class="col-md-4">
                        <label class="finance-form-label">Academic Year <span class="text-danger">*</span></label>
                        <select name="academic_year_id" class="finance-form-select" required onchange="this.form.submit()">
                            <option value="">-- Choose Year --</option>
                            @foreach($academicYears as $year)
                                <option value="{{ $year->id }}" {{ ($selectedAcademicYearId ?? '') == $year->id ? 'selected' : '' }}>
                                    {{ $year->year }} {{ $year->is_active ? '(Active)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($selectedClassroom)
    {{-- Fee Structure Form --}}
    <div class="finance-card finance-animate mb-4 shadow-sm rounded-4 border-0">
        <div class="finance-card-header d-flex align-items-center gap-2">
            <i class="bi bi-pencil-square"></i> <span>Fee Structure for {{ $classrooms->firstWhere('id', $selectedClassroom)->name ?? 'Selected Class' }}</span>
        </div>
        <div class="finance-card-body p-4">
            <form action="{{ route('finance.fee-structures.save') }}" method="POST">
                @csrf

                <input type="hidden" name="classroom_id" value="{{ $selectedClassroom }}">
                <input type="hidden" name="student_category_id" value="{{ $selectedCategory }}">
                <input type="hidden" name="academic_year_id" value="{{ $selectedAcademicYearId }}">
                <input type="hidden" name="year" value="{{ $selectedAcademicYear->year ?? date('Y') }}">
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <label class="finance-form-label">Academic Year</label>
                        <div class="form-control-plaintext fw-semibold">
                            {{ $selectedAcademicYear->year ?? '—' }} {{ $selectedAcademicYear?->is_active ? '(Active)' : '' }}
                        </div>
                    </div>
                </div>

                <h5 class="mb-3">Votehead Charges (Ksh)</h5>
                <div class="alert alert-info d-flex align-items-center gap-2">
                    <i class="bi bi-info-circle"></i>
                    <div class="small mb-0">
                        Transport is managed from Finance → Transport Fees. It is intentionally hidden from fee structures.
                    </div>
                </div>
                <div class="finance-table-wrapper">
                    <div class="table-responsive">
                        <table class="finance-table align-middle">
                            <thead>
                                <tr>
                                    <th>Votehead</th>
                                    <th class="text-center">Term 1</th>
                                    <th class="text-center">Term 2</th>
                                    <th class="text-center">Term 3</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($voteheads as $votehead)
                                    @php
                                        $existing = collect($charges)->where('votehead_id', $votehead->id);
                                        $term1Charge = $existing->where('term', 1)->first();
                                        $term2Charge = $existing->where('term', 2)->first();
                                        $term3Charge = $existing->where('term', 3)->first();
                                        $term1 = old("charges.{$loop->index}.term_1", $term1Charge ? $term1Charge->amount : '');
                                        $term2 = old("charges.{$loop->index}.term_2", $term2Charge ? $term2Charge->amount : '');
                                        $term3 = old("charges.{$loop->index}.term_3", $term3Charge ? $term3Charge->amount : '');
                                    @endphp
                                    <tr>
                                        <td>
                                            <strong>{{ $votehead->name }}</strong>
                                            <input type="hidden" name="charges[{{ $loop->index }}][votehead_id]" value="{{ $votehead->id }}">
                                        </td>
                                        <td>
                                            <input type="number" name="charges[{{ $loop->index }}][term_1]" class="finance-form-control text-center" step="0.01" value="{{ $term1 }}" placeholder="0.00">
                                        </td>
                                        <td>
                                            <input type="number" name="charges[{{ $loop->index }}][term_2]" class="finance-form-control text-center" step="0.01" value="{{ $term2 }}" placeholder="0.00">
                                        </td>
                                        <td>
                                            <input type="number" name="charges[{{ $loop->index }}][term_3]" class="finance-form-control text-center" step="0.01" value="{{ $term3 }}" placeholder="0.00">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="mt-4 d-flex gap-3 flex-wrap">
                    <button type="submit" class="btn btn-finance btn-finance-primary">
                        <i class="bi bi-check-circle"></i> Save Fee Structure
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Replication Form --}}
    @if($feeStructure)
    <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
        <div class="finance-card-header secondary d-flex align-items-center gap-2">
            <i class="bi bi-copy"></i> <span>Replicate this Fee Structure</span>
        </div>
        <div class="finance-card-body p-4">
            <form method="POST" action="{{ route('finance.fee-structures.replicate') }}">
                @csrf
                <input type="hidden" name="source_classroom_id" value="{{ $selectedClassroom }}">
                <input type="hidden" name="student_category_id" value="{{ $selectedCategory }}">

                <div class="row g-4">
                    <div class="col-md-12">
                        <label class="finance-form-label">Select Target Classes (hold Ctrl to select multiple)</label>
                        <select name="target_classroom_ids[]" class="finance-form-select" multiple required style="height: 200px;">
                            @foreach($classrooms->where('id', '!=', $selectedClassroom) as $class)
                                <option value="{{ $class->id }}">{{ $class->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Hold Ctrl (Windows) or Cmd (Mac) to select multiple classes</small>
                    </div>
                </div>

                <div class="mt-3 d-flex gap-3 flex-wrap">
                    <button type="submit" class="btn btn-finance btn-finance-warning">
                        <i class="bi bi-copy"></i> Replicate to Selected Classes
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    @endif
@endsection
