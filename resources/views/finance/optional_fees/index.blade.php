@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Optional Fees',
        'icon' => 'bi bi-toggle-on',
        'subtitle' => 'Manage optional fees for classes and individual students',
    ])

    {{-- Tabs --}}
    <ul class="nav nav-tabs mb-3" id="optionalFeeTabs">
        <li class="nav-item">
            <a class="nav-link {{ request()->has('student_id') ? '' : 'active' }}" data-bs-toggle="tab" href="#class">Class-Based</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->has('student_id') ? 'active' : '' }}" data-bs-toggle="tab" href="#student">Student-Based</a>
        </li>
    </ul>

    <div class="tab-content">
        {{-- === CLASS-BASED === --}}
        <div class="tab-pane fade {{ request()->has('student_id') ? '' : 'show active' }}" id="class">
            <form method="GET" action="{{ route('finance.optional_fees.class_view') }}" class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Classroom</label>
                    <select name="classroom_id" class="form-select" required>
                        <option value="">Select Class</option>
                        @foreach($classrooms as $classroom)
                            <option value="{{ $classroom->id }}" {{ request('classroom_id') == $classroom->id ? 'selected' : '' }}>
                                {{ $classroom->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Year</label>
                    <select name="year" class="form-select" required>
                        @foreach(\App\Models\AcademicYear::orderByDesc('year')->get() as $ay)
                            <option value="{{ $ay->year }}" {{ (request('year', $defaultYear ?? now()->year) == $ay->year) ? 'selected' : '' }}>{{ $ay->year }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Term</label>
                    <select name="term" class="form-select" required>
                        @php
                            $reqYear = request('year', $defaultYear ?? now()->year);
                            $termsForYear = ($allTerms ?? collect())->filter(fn($t) => $t->academicYear && $t->academicYear->year == $reqYear);
                            $termNum = fn($t) => (int) preg_replace('/[^0-9]/', '', $t->name) ?: 1;
                        @endphp
                        @foreach($termsForYear as $t)
                            <option value="{{ $termNum($t) }}" {{ (request('term', $defaultTerm ?? 1) == $termNum($t)) ? 'selected' : '' }}>{{ $t->name }}</option>
                        @endforeach
                        @if($termsForYear->isEmpty())
                            <option value="{{ request('term', $defaultTerm ?? 1) }}">Term {{ request('term', $defaultTerm ?? 1) }}</option>
                        @endif
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Optional Fee (Votehead)</label>
                    <select name="votehead_id" class="form-select" required>
                        <option value="">Select Votehead</option>
                        @foreach($optionalVoteheads as $votehead)
                            <option value="{{ $votehead->id }}" {{ request('votehead_id') == $votehead->id ? 'selected' : '' }}>
                                {{ $votehead->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-1 d-flex align-items-end">
                    <button id="loadOptionalFee" type="submit" class="btn btn-primary w-100" disabled>Load</button>
                </div>
            </form>

            {{-- Enable Load button only when all fields are filled --}}
            <script>
            document.addEventListener('DOMContentLoaded', function () {
                const classroomSelect = document.querySelector('select[name="classroom_id"]');
                const termSelect = document.querySelector('select[name="term"]');
                const yearInput = document.querySelector('input[name="year"]');
                const voteheadSelect = document.querySelector('select[name="votehead_id"]');
                const loadBtn = document.querySelector('#loadOptionalFee');

                function checkFormFields() {
                    const isComplete = classroomSelect.value && termSelect.value && yearInput.value && voteheadSelect.value;
                    if (loadBtn) loadBtn.disabled = !isComplete;
                }
                checkFormFields();
                classroomSelect?.addEventListener('change', checkFormFields);
                termSelect?.addEventListener('change', checkFormFields);
                yearInput?.addEventListener('input', checkFormFields);
                voteheadSelect?.addEventListener('change', checkFormFields);
            });
            </script>

            {{-- Only render after filters are chosen --}}
            @if(request()->filled(['classroom_id', 'term', 'year', 'votehead_id']))
                @include('finance.optional_fees.partials.class_view', [
                    'term' => request('term'),
                    'year' => request('year'),
                    'students' => $students,
                    'optionalVoteheads' => $optionalVoteheads,
                    'statuses' => $statuses
                ])
            @endif
        </div>

        {{-- === STUDENT-BASED === --}}
        <div class="tab-pane fade {{ request()->has('student_id') ? 'show active' : '' }}" id="student">
            {{-- Always include the partial so the search form shows; the table inside is gated --}}
            @include('finance.optional_fees.partials.student_view', [
                'voteheads' => $optionalVoteheads,
                'student'   => $student ?? null,
                'statuses'  => $statuses ?? [],
                'defaultYear' => $defaultYear ?? now()->year,
                'defaultTerm' => $defaultTerm ?? 1,
                'allTerms' => $allTerms ?? collect(),
            ])
        </div>
    </div>

    {{-- Import + History + Duplicate --}}
    <div class="row mt-4">
        <div class="col-lg-6">
            @include('finance.optional_fees.partials.import_tabs', [
                'defaultYear' => $defaultYear ?? now()->year,
                'defaultTerm' => $defaultTerm ?? 1,
                'futureTerms' => $futureTerms ?? collect(),
            ])
        </div>
        <div class="col-lg-6">
            @include('finance.optional_fees.partials.duplicate_form', [
                'classrooms' => $classrooms,
                'optionalVoteheads' => $optionalVoteheads,
                'defaultYear' => $defaultYear ?? now()->year,
                'defaultTerm' => $defaultTerm ?? 1,
                'termsByYear' => $termsByYear ?? collect(),
                'futureTerms' => $futureTerms ?? collect(),
            ])
        </div>
    </div>
  </div>
</div>

{{-- Auto-switch to Student tab when a student_id exists (e.g., after submit) --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const hasStudent = '{{ request("student_id") }}';
    if (hasStudent) {
        const tabTrigger = document.querySelector('a[href="#student"]');
        if (tabTrigger) {
            const tab = new bootstrap.Tab(tabTrigger);
            tab.show();
        }
    }
});
</script>
@endsection
