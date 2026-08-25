@extends('layouts.app')

@php
    $ownMeansId = (int) $ownMeansPoint->id;
    $pointRates = $dropOffPoints->mapWithKeys(fn ($p) => [
        (string) $p->id => [
            'id' => $p->id,
            'name' => $p->name,
            'own' => $p->isOwnMeans(),
            'two_way' => $p->two_way_amount !== null ? (float) $p->two_way_amount : null,
            'one_way' => $p->one_way_amount !== null ? (float) $p->one_way_amount : null,
        ],
    ]);
@endphp

@push('styles')
    @include('transport.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header">
            <div>
                <p class="eyebrow text-muted mb-1">Transport</p>
                <h1 class="mb-1">Assignments</h1>
                <p class="text-muted mb-0">
                    Assign morning pickup, evening drop-off, and both vehicle trips in one place.
                    Stops and trips are for viewing after that.
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('transport.trips.index') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-bus-front"></i> Trips
                </a>
                <a href="{{ route('transport.dropoffpoints.index') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-geo-alt"></i> Pickup / drop-off points
                </a>
            </div>
        </div>

        @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
        @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div>   @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
            </div>
        @endif

        <nav class="transport-tabs" aria-label="Assignment mode">
            <a href="{{ route('transport.student-assignments.index', array_filter(['tab' => 'class', 'classroom_id' => $selectedClassroomId, 'stream_id' => $selectedStreamId])) }}"
               class="{{ $tab === 'class' ? 'active' : '' }}">
                <i class="bi bi-people"></i> By class
            </a>
            <a href="{{ route('transport.student-assignments.index', ['tab' => 'student']) }}"
               class="{{ $tab === 'student' ? 'active' : '' }}">
                <i class="bi bi-person-vcard"></i> By student
            </a>
        </nav>

        @if($tab === 'class')
            <div class="settings-card mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('transport.student-assignments.index') }}" class="row g-3 align-items-end">
                        <input type="hidden" name="tab" value="class">
                        <div class="col-md-4">
                            <label for="classroom_id" class="form-label fw-semibold">Class <span class="text-danger">*</span></label>
                            <select name="classroom_id" id="classroom_id" class="form-select" required onchange="this.form.submit()">
                                <option value="">Select class</option>
                                @foreach($classrooms as $classroom)
                                    <option value="{{ $classroom->id }}" @selected((int) $selectedClassroomId === (int) $classroom->id)>
                                        {{ $classroom->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="stream_id" class="form-label fw-semibold">Stream</label>
                            <select name="stream_id" id="stream_id" class="form-select" onchange="this.form.submit()">
                                <option value="">All streams</option>
                                @if($selectedClassroomId)
                                    @foreach($streams->where('classroom_id', $selectedClassroomId) as $stream)
                                        <option value="{{ $stream->id }}" @selected((int) $selectedStreamId === (int) $stream->id)>
                                            {{ $stream->name }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="incomplete" value="1" id="incomplete" @checked($incompleteOnly) onchange="this.form.submit()">
                                <label class="form-check-label" for="incomplete">Incomplete only</label>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-settings-primary w-100">Load</button>
                        </div>
                    </form>
                </div>
            </div>

            @if($students->isNotEmpty())
                <form method="POST" action="{{ route('transport.student-assignments.bulk-assign.store') }}" id="bulkAssignForm">
                    @csrf
                    <input type="hidden" name="classroom_id" value="{{ $selectedClassroomId }}">
                    <input type="hidden" name="stream_id" value="{{ $selectedStreamId }}">

                    <div class="settings-card mb-3">
                        <div class="card-header">
                            <h5 class="mb-0">Apply to class</h5>
                            <small class="text-muted">Set once, then fill empty rows or overwrite the whole class.</small>
                        </div>
                        <div class="card-body">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold" for="fillMorningPoint">Morning pickup</label>
                                    <select id="fillMorningPoint" class="form-select form-select-sm">
                                        <option value="">—</option>
                                        @foreach($dropOffPoints as $point)
                                            <option value="{{ $point->id }}">{{ $point->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold" for="fillMorningTrip">Morning trip</label>
                                    <select id="fillMorningTrip" class="form-select form-select-sm">
                                        <option value="">—</option>
                                        @foreach($morningTrips as $trip)
                                            <option value="{{ $trip->id }}">{{ \App\Services\TransportAssignmentWriter::tripLabel($trip) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold" for="fillEveningPoint">Evening drop-off</label>
                                    <select id="fillEveningPoint" class="form-select form-select-sm">
                                        <option value="">—</option>
                                        @foreach($dropOffPoints as $point)
                                            <option value="{{ $point->id }}">{{ $point->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold" for="fillEveningTrip">Evening trip</label>
                                    <select id="fillEveningTrip" class="form-select form-select-sm">
                                        <option value="">—</option>
                                        @foreach($eveningTrips as $trip)
                                            <option value="{{ $trip->id }}">{{ \App\Services\TransportAssignmentWriter::tripLabel($trip) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold" for="fillAmount">Amount</label>
                                    <input type="number" step="0.01" min="0" id="fillAmount" class="form-control form-control-sm" placeholder="0 allowed">
                                </div>
                                <div class="col-md-2 d-flex gap-2">
                                    <button type="button" class="btn btn-ghost-strong btn-sm" id="fillEmptyBtn">Fill empty</button>
                                    <button type="button" class="btn btn-ghost-strong btn-sm" id="fillAllBtn">Overwrite all</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="settings-card">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h5 class="mb-0">{{ $students->count() }} student(s)</h5>
                            <button type="submit" class="btn btn-settings-primary">
                                <i class="bi bi-save"></i> Save assignments
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-modern mb-0 align-middle" id="classAssignTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Student</th>
                                            <th>Morning pickup</th>
                                            <th>Morning trip</th>
                                            <th>Evening drop-off</th>
                                            <th>Evening trip</th>
                                            <th style="min-width:110px;">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($students as $index => $student)
                                            @php
                                                $assignment = $assignments[$student->id] ?? $student->assignment;
                                                $fee = $fees[$student->id] ?? null;
                                                $incomplete = ! $assignment
                                                    || ! $assignment->morning_drop_off_point_id
                                                    || ! $assignment->evening_drop_off_point_id
                                                    || ((int) $assignment->morning_drop_off_point_id !== $ownMeansId && ! $assignment->morning_trip_id)
                                                    || ((int) $assignment->evening_drop_off_point_id !== $ownMeansId && ! $assignment->evening_trip_id);
                                            @endphp
                                            <tr class="js-assign-row {{ $incomplete ? 'assignment-row-incomplete' : '' }}">
                                                <td class="text-muted">{{ $index + 1 }}</td>
                                                <td>
                                                    <div class="fw-semibold">{{ $student->full_name }}</div>
                                                    <small class="text-muted">{{ $student->admission_number }} @if($student->stream)· {{ $student->stream->name }}@endif</small>
                                                    <input type="hidden" name="assignments[{{ $student->id }}][student_id]" value="{{ $student->id }}">
                                                </td>
                                                <td>
                                                    <select name="assignments[{{ $student->id }}][morning_drop_off_point_id]" class="form-select form-select-sm js-morning-point">
                                                        <option value="">—</option>
                                                        @foreach($dropOffPoints as $point)
                                                            <option value="{{ $point->id }}" @selected((int) optional($assignment)->morning_drop_off_point_id === (int) $point->id)>{{ $point->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>
                                                    <select name="assignments[{{ $student->id }}][morning_trip_id]" class="form-select form-select-sm js-morning-trip">
                                                        <option value="">—</option>
                                                        @foreach($morningTrips as $trip)
                                                            <option value="{{ $trip->id }}" @selected((int) optional($assignment)->morning_trip_id === (int) $trip->id)>{{ \App\Services\TransportAssignmentWriter::tripLabel($trip) }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>
                                                    <select name="assignments[{{ $student->id }}][evening_drop_off_point_id]" class="form-select form-select-sm js-evening-point">
                                                        <option value="">—</option>
                                                        @foreach($dropOffPoints as $point)
                                                            <option value="{{ $point->id }}" @selected((int) optional($assignment)->evening_drop_off_point_id === (int) $point->id)>{{ $point->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>
                                                    <select name="assignments[{{ $student->id }}][evening_trip_id]" class="form-select form-select-sm js-evening-trip">
                                                        <option value="">—</option>
                                                        @foreach($eveningTrips as $trip)
                                                            <option value="{{ $trip->id }}" @selected((int) optional($assignment)->evening_trip_id === (int) $trip->id)>{{ \App\Services\TransportAssignmentWriter::tripLabel($trip) }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="number" step="0.01" min="0" name="assignments[{{ $student->id }}][amount]" class="form-control form-control-sm js-amount"
                                                           value="{{ old('assignments.'.$student->id.'.amount', $fee?->amount) }}" placeholder="0">
                                                    <small class="text-muted js-suggested"></small>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </form>
            @elseif($selectedClassroomId)
                <div class="settings-card">
                    <div class="card-body text-center py-5 text-muted">
                        No students in this class{{ $incompleteOnly ? ' matching incomplete filter' : '' }}.
                    </div>
                </div>
            @endif
        @else
            <div class="settings-card mb-3">
                <div class="card-body">
                    <label class="form-label fw-semibold" for="studentSearch">Search by name or admission number</label>
                    <div class="position-relative">
                        <input type="search" id="studentSearch" class="form-control" value="{{ $searchQ }}" placeholder="Type at least 2 characters…" autocomplete="off">
                        <div id="studentSearchResults" class="transport-search-results d-none"></div>
                    </div>
                </div>
            </div>

            @if($selectedStudent)
                @php $assignment = $selectedAssignment; $fee = $selectedFee; @endphp
                <form method="POST" action="{{ route('transport.student-assignments.store') }}" id="individualAssignForm" class="settings-card">
                    @csrf
                    <input type="hidden" name="student_id" value="{{ $selectedStudent->id }}">
                    <div class="card-header">
                        <h5 class="mb-0">{{ $selectedStudent->full_name }}</h5>
                        <small class="text-muted">{{ $selectedStudent->admission_number }} · {{ optional($selectedStudent->classroom)->name ?? 'No class' }}</small>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="morning_drop_off_point_id">Morning pickup <span class="text-danger">*</span></label>
                                <select name="morning_drop_off_point_id" id="morning_drop_off_point_id" class="form-select js-morning-point" required>
                                    <option value="">Select pickup point</option>
                                    @foreach($dropOffPoints as $point)
                                        <option value="{{ $point->id }}" @selected(old('morning_drop_off_point_id', $assignment?->morning_drop_off_point_id) == $point->id)>{{ $point->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="morning_trip_id">Morning trip</label>
                                <select name="morning_trip_id" id="morning_trip_id" class="form-select js-morning-trip">
                                    <option value="">Select morning trip</option>
                                    @foreach($morningTrips as $trip)
                                        <option value="{{ $trip->id }}" @selected(old('morning_trip_id', $assignment?->morning_trip_id) == $trip->id)>{{ \App\Services\TransportAssignmentWriter::tripLabel($trip) }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Not required if morning pickup is OWN MEANS.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="evening_drop_off_point_id">Evening drop-off <span class="text-danger">*</span></label>
                                <select name="evening_drop_off_point_id" id="evening_drop_off_point_id" class="form-select js-evening-point" required>
                                    <option value="">Select drop-off point</option>
                                    @foreach($dropOffPoints as $point)
                                        <option value="{{ $point->id }}" @selected(old('evening_drop_off_point_id', $assignment?->evening_drop_off_point_id) == $point->id)>{{ $point->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="evening_trip_id">Evening trip</label>
                                <select name="evening_trip_id" id="evening_trip_id" class="form-select js-evening-trip">
                                    <option value="">Select evening trip</option>
                                    @foreach($eveningTrips as $trip)
                                        <option value="{{ $trip->id }}" @selected(old('evening_trip_id', $assignment?->evening_trip_id) == $trip->id)>{{ \App\Services\TransportAssignmentWriter::tripLabel($trip) }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Not required if evening drop-off is OWN MEANS.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" for="amount">Amount (KES / term) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" name="amount" id="amount" class="form-control js-amount" required
                                       value="{{ old('amount', $fee?->amount ?? 0) }}">
                                <div class="form-text">Zero is allowed. Suggested: <span class="js-suggested">—</span>
                                    <button type="button" class="btn btn-link btn-sm p-0 js-use-suggested">Use suggested</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-end">
                        <button type="submit" class="btn btn-settings-primary">Save assignment</button>
                    </div>
                </form>
            @else
                <div class="settings-card">
                    <div class="card-body text-center py-5 text-muted">
                        Search for a child, then assign morning and evening pickup/drop-off, trips, and amount.
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const ownMeansId = String(@json($ownMeansId));
    const points = @json($pointRates);
    const searchUrl = @json(route('transport.student-assignments.search'));
    const assignUrl = @json(route('transport.student-assignments.index'));

    function quote(morningId, eveningId) {
        const m = points[String(morningId)];
        const e = points[String(eveningId)];
        if (!m || !e) return null;
        if (m.own && e.own) return 0;
        if (m.own) return e.one_way;
        if (e.own) return m.one_way;
        if (String(m.id) === String(e.id)) return m.two_way;
        if (m.two_way == null || e.two_way == null) return null;
        return Math.round(((m.two_way / 2) + (e.two_way / 2)) * 100) / 100;
    }

    function syncLeg(pointSelect, tripSelect) {
        if (!pointSelect || !tripSelect) return;
        const isOwn = String(pointSelect.value) === ownMeansId;
        tripSelect.disabled = isOwn;
        if (isOwn) tripSelect.value = '';
    }

    function refreshRow(row) {
        const morningPoint = row.querySelector('.js-morning-point');
        const eveningPoint = row.querySelector('.js-evening-point');
        const morningTrip = row.querySelector('.js-morning-trip');
        const eveningTrip = row.querySelector('.js-evening-trip');
        syncLeg(morningPoint, morningTrip);
        syncLeg(eveningPoint, eveningTrip);
        const suggested = quote(morningPoint?.value, eveningPoint?.value);
        const hint = row.querySelector('.js-suggested');
        if (hint) hint.textContent = suggested == null ? '' : ('Suggested ' + Number(suggested).toFixed(2));
        row.dataset.suggested = suggested == null ? '' : String(suggested);
    }

    document.querySelectorAll('.js-assign-row, #individualAssignForm').forEach((scope) => {
        refreshRow(scope);
        scope.addEventListener('change', (e) => {
            if (e.target.matches('.js-morning-point, .js-evening-point, .js-morning-trip, .js-evening-trip')) {
                refreshRow(scope);
            }
        });
    });

    function applyFill(emptyOnly) {
        const values = {
            morningPoint: document.getElementById('fillMorningPoint')?.value || '',
            morningTrip: document.getElementById('fillMorningTrip')?.value || '',
            eveningPoint: document.getElementById('fillEveningPoint')?.value || '',
            eveningTrip: document.getElementById('fillEveningTrip')?.value || '',
            amount: document.getElementById('fillAmount')?.value ?? '',
        };
        document.querySelectorAll('.js-assign-row').forEach((row) => {
            const setIf = (sel, value) => {
                if (!value) return;
                const el = row.querySelector(sel);
                if (!el) return;
                if (!emptyOnly || !el.value) el.value = value;
            };
            setIf('.js-morning-point', values.morningPoint);
            setIf('.js-morning-trip', values.morningTrip);
            setIf('.js-evening-point', values.eveningPoint);
            setIf('.js-evening-trip', values.eveningTrip);
            if (values.amount !== '') {
                const amount = row.querySelector('.js-amount');
                if (amount && (!emptyOnly || amount.value === '')) amount.value = values.amount;
            }
            refreshRow(row);
        });
    }

    document.getElementById('fillEmptyBtn')?.addEventListener('click', () => applyFill(true));
    document.getElementById('fillAllBtn')?.addEventListener('click', () => applyFill(false));

    document.querySelectorAll('.js-use-suggested').forEach((btn) => {
        btn.addEventListener('click', () => {
            const form = btn.closest('form');
            const suggested = form?.dataset.suggested;
            const amount = form?.querySelector('.js-amount');
            if (amount && suggested !== '') amount.value = suggested;
        });
    });

    const searchInput = document.getElementById('studentSearch');
    const resultsBox = document.getElementById('studentSearchResults');
    let timer = null;

    function hideResults() {
        if (resultsBox) resultsBox.classList.add('d-none');
    }

    searchInput?.addEventListener('input', () => {
        clearTimeout(timer);
        const q = searchInput.value.trim();
        if (q.length < 2) {
            hideResults();
            return;
        }
        timer = setTimeout(async () => {
            const res = await fetch(searchUrl + '?q=' + encodeURIComponent(q), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const rows = await res.json();
            if (!resultsBox) return;
            if (!rows.length) {
                resultsBox.innerHTML = '<div class="p-3 text-muted">No matching students.</div>';
                resultsBox.classList.remove('d-none');
                return;
            }
            resultsBox.innerHTML = rows.map((s) => (
                '<button type="button" data-id="' + s.id + '">' +
                    '<strong>' + (s.name || '') + '</strong><br>' +
                    '<small class="text-muted">' + (s.admission_number || '') +
                    (s.classroom ? ' · ' + s.classroom : '') + '</small>' +
                '</button>'
            )).join('');
            resultsBox.classList.remove('d-none');
            resultsBox.querySelectorAll('button').forEach((btn) => {
                btn.addEventListener('click', () => {
                    window.location.href = assignUrl + '?tab=student&student_id=' + btn.dataset.id;
                });
            });
        }, 220);
    });

    document.addEventListener('click', (e) => {
        if (resultsBox && !resultsBox.contains(e.target) && e.target !== searchInput) hideResults();
    });
})();
</script>
@endpush
