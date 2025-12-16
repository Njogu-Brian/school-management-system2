@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @include('finance.partials.header', [
        'title' => 'Optional Fees',
        'icon' => 'bi bi-toggle-on',
        'subtitle' => 'Manage optional fees for classes and individual students'
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
                    <label class="form-label">Term</label>
                    <select name="term" class="form-select" required>
                        <option value="">Select Term</option>
                        @for($i = 1; $i <= 3; $i++)
                            <option value="{{ $i }}" {{ (request('term', $currentTermNumber ?? 1) == $i) ? 'selected' : '' }}>Term {{ $i }}</option>
                        @endfor
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Year</label>
                    <input type="number" name="year" value="{{ request('year', $currentYear ?? now()->year) }}" class="form-control" required>
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
            ])
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
