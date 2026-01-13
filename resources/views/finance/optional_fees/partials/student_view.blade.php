{{-- Student-Based Optional Fees (attendance-like search) --}}

<form method="GET" action="{{ route('finance.optional_fees.student_view') }}" class="row g-3 mb-3" id="studentForm">
    <div class="col-md-6">
        <label class="form-label">Student</label>
        @include('partials.student_live_search', [
            'hiddenInputId' => 'selectedStudentId',
            'displayInputId' => 'selectedStudentName',
            'resultsId' => 'studentLiveResultsOptionalFees',
            'placeholder' => 'Type name or admission #',
            'initialLabel' => isset($student) ? ($student->full_name.' ('.$student->admission_number.')') : ''
        ])
        <button class="btn btn-primary mt-2" type="submit">Load</button>
    </div>

    <div class="col-md-3">
        <label class="form-label">Term</label>
        <select name="term" id="termSelect" class="form-select" required>
            <option value="">Select</option>
            @for($i = 1; $i <= 3; $i++)
                <option value="{{ $i }}" {{ (request('term', $currentTermNumber ?? 1) == $i) ? 'selected' : '' }}>Term {{ $i }}</option>
            @endfor
        </select>
    </div>

    <div class="col-md-3">
        <label class="form-label">Year</label>
        <input type="number" name="year" id="yearInput" value="{{ request('year', $currentYear ?? now()->year) }}" class="form-control" required>
    </div>
</form>

{{-- Results only when a student + term + year are present --}}
@if(request()->filled(['student_id','term','year']) && isset($student))
    <div class="card">
        <div class="card-header">
            <strong>Optional Fees for:</strong> {{ $student->full_name }} ({{ $student->admission_number }})
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('finance.optional_fees.save_student') }}">
                @csrf
                <input type="hidden" name="student_id" value="{{ $student->id }}">
                <input type="hidden" name="term" value="{{ request('term') }}">
                <input type="hidden" name="year" value="{{ request('year') }}">

                {{-- Linked Activities Info --}}
                @if(isset($linkedActivities) && $linkedActivities->count() > 0)
                <div class="alert alert-info mb-3">
                    <h6 class="mb-2"><i class="bi bi-info-circle"></i> Linked Extracurricular Activities</h6>
                    <p class="mb-2 small">The following activities are automatically linked to optional fees:</p>
                    <ul class="mb-0 small">
                        @foreach($linkedActivities as $voteheadId => $activities)
                            @php $votehead = $activities->first()->votehead; @endphp
                            <li>
                                <strong>{{ $votehead->name }}:</strong>
                                @foreach($activities as $activity)
                                    {{ $activity->activity_name }}
                                    @if($activity->fee_amount)
                                        (KES {{ number_format($activity->fee_amount, 2) }})
                                    @endif
                                    @if(!$loop->last), @endif
                                @endforeach
                            </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Votehead</th>
                            <th>Linked Activities</th>
                            <th class="text-center">Billing Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($voteheads as $votehead)
                            @php 
                                $status = $statuses[$votehead->id] ?? 'exempt';
                                $activities = $linkedActivities[$votehead->id] ?? collect();
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $votehead->name }}</strong>
                                    @if($activities->count() > 0)
                                        <span class="badge bg-info ms-2">{{ $activities->count() }} activity/ies</span>
                                    @endif
                                </td>
                                <td>
                                    @if($activities->count() > 0)
                                        <div class="small">
                                            @foreach($activities as $activity)
                                                <div>
                                                    <i class="bi bi-arrow-right-circle text-primary"></i>
                                                    {{ $activity->activity_name }}
                                                    @if($activity->fee_amount)
                                                        <span class="text-muted">(KES {{ number_format($activity->fee_amount, 2) }})</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted">No linked activities</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio"
                                               name="statuses[{{ $votehead->id }}]"
                                               value="billed" {{ $status == 'billed' ? 'checked' : '' }}>
                                        <label class="form-check-label">Bill</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio"
                                               name="statuses[{{ $votehead->id }}]"
                                               value="exempt" {{ $status == 'exempt' ? 'checked' : '' }}>
                                        <label class="form-check-label">Exempt</label>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <button class="btn btn-success" type="submit">Save</button>
            </form>
        </div>
    </div>
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Auto-submit when term/year change and student already picked
    const form = document.getElementById('studentForm');
    const sid  = document.getElementById('selectedStudentId');
    const term = document.getElementById('termSelect');
    const year = document.getElementById('yearInput');
    function ready(){ return sid.value && term.value && year.value; }
    [term, year].forEach(el => el.addEventListener('change', () => { if (ready()) form.submit(); }));
    
    // When live-search picks a student, auto-submit if term/year present
    window.addEventListener('student-selected', () => {
        if (ready()) form.submit();
    });
});
</script>
@endpush
