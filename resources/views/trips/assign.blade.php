@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <p class="eyebrow text-muted mb-1">Transport / Trips</p>
                <h1 class="mb-1">Assign Students — {{ $trip->trip_name }}</h1>
                <p class="text-muted mb-0">
                    Vehicle: {{ $trip->vehicle->vehicle_number ?? 'N/A' }}
                    · Direction: {{ $trip->direction ? ucfirst($trip->direction) : 'All' }}
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('transport.student-assignments.bulk-assign') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-people"></i> Bulk by class
                </a>
                <a href="{{ route('transport.trips.index') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-arrow-left"></i> Back to trips
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('transport.trips.assign.store', $trip) }}" id="tripAssignForm">
            @csrf
            <div id="draftHiddenInputs"></div>

            <div class="settings-card mb-3">
                <div class="card-body">
                    <div class="row g-3 align-items-start">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold mb-1" for="assignLeg">Assign as</label>
                            <select name="leg" id="assignLeg" class="form-select" required>
                                <option value="morning" @selected(old('leg', $defaultLeg) === 'morning')>Morning trip (pickup)</option>
                                <option value="evening" @selected(old('leg', $defaultLeg) === 'evening')>Evening trip (drop-off)</option>
                            </select>
                        </div>
                        <div class="col-md-9">
                            <label class="form-label fw-semibold mb-1" for="tripStudentSearch">Search students (name or admission #)</label>
                            <input type="text" id="tripStudentSearch" class="form-control" placeholder="Type name or admission number…" autocomplete="off">
                        </div>
                        <div class="col-12">
                            <small class="text-muted">Check students to add them to the draft below. Edit pickup/drop-off in the draft, then save once.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="settings-card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">Search results</h5>
                    <button type="button" class="btn btn-sm btn-ghost-strong" id="addAllResults">Add all to draft</button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-modern mb-0 align-middle" id="searchResultsTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:40px;"></th>
                                    <th>Name</th>
                                    <th>Admission</th>
                                    <th>Class</th>
                                    <th>Stream</th>
                                    <th class="js-leg-point-col">Morning pickup</th>
                                </tr>
                            </thead>
                            <tbody id="searchResultsBody">
                                <tr class="text-muted">
                                    <td colspan="6" class="text-center py-4">Start typing to search students.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="settings-card mb-3" id="suggestionsCard" style="display:none;">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="mb-0">Suggested — same stop</h5>
                        <small class="text-muted" id="suggestionsHint"></small>
                    </div>
                    <button type="button" class="btn btn-sm btn-ghost-strong" id="addAllSuggestions">Add all suggestions</button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-modern mb-0 align-middle" id="suggestionsTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:40px;"></th>
                                    <th>Name</th>
                                    <th>Admission</th>
                                    <th>Class</th>
                                    <th>Stream</th>
                                    <th class="js-leg-point-col">Morning pickup</th>
                                </tr>
                            </thead>
                            <tbody id="suggestionsBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="settings-card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="mb-0">Draft to assign</h5>
                        <small class="text-muted">Own-means students can be drafted — choose a real stop for this trip leg before saving.</small>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <span class="input-chip" id="draftCount">0 selected</span>
                        <button type="button" class="btn btn-sm btn-ghost-strong text-danger" id="clearDraftBtn">Clear draft</button>
                        <button type="submit" class="btn btn-settings-primary" id="saveAssignBtn" disabled>
                            <i class="bi bi-save"></i> Save draft to trip
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-modern mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Admission</th>
                                    <th>Class</th>
                                    <th>Stream</th>
                                    <th style="min-width:180px;">Morning pickup</th>
                                    <th style="min-width:180px;">Evening drop-off</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="draftBody">
                                <tr class="text-muted" id="draftEmptyRow">
                                    <td colspan="7" class="text-center py-4">No students in draft yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </form>

        <form method="POST" action="{{ route('transport.trips.assign.points', $trip) }}" id="assignedPointsForm">
            @csrf
            <div class="settings-card">
                <div class="card-header d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <h5 class="mb-1">Currently on this trip</h5>
                        <small class="text-muted">Edit points, then save. Choose <strong>Own means</strong> for a leg they do not use school transport on (fees become one-way for the other leg).</small>
                    </div>
                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <span class="input-chip">{{ $assigned->count() }} student(s)</span>
                        @if($assigned->isNotEmpty())
                            <button type="submit" class="btn btn-sm btn-settings-primary">
                                <i class="bi bi-geo-alt"></i> Save point changes
                            </button>
                        @endif
                    </div>
                </div>
                @if($stopCounts->isNotEmpty())
                    <div class="px-3 pt-3 d-flex flex-wrap gap-2">
                        @foreach($stopCounts as $stopName => $count)
                            <span class="input-chip">
                                <strong>{{ $stopName }}</strong>: {{ $count }}
                            </span>
                        @endforeach
                    </div>
                @endif
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-modern mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:52px;">#</th>
                                    <th>Name</th>
                                    <th>Admission</th>
                                    <th>Class</th>
                                    <th>Stream</th>
                                    <th style="min-width:180px;">Morning pickup</th>
                                    <th style="min-width:180px;">Evening drop-off</th>
                                    <th>Leg</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $prevStop = null;
                                    $groupNum = 0;
                                    $overallNum = 0;
                                @endphp
                                @forelse($assigned as $idx => $row)
                                    @php
                                        $student = $row->student;
                                        $morningId = $row->morning_drop_off_point_id
                                            ?: ($student->drop_off_point_id ?: $ownMeansPointId);
                                        $eveningId = $row->evening_drop_off_point_id
                                            ?: ($student->drop_off_point_id ?: $ownMeansPointId);
                                        $stopName = $defaultLeg === 'evening'
                                            ? (optional($row->eveningDropOffPoint)->name
                                                ?? optional($student->dropOffPoint)->name
                                                ?? $student->drop_off_point_other
                                                ?? 'Unassigned')
                                            : (optional($row->morningDropOffPoint)->name
                                                ?? optional($student->dropOffPoint)->name
                                                ?? $student->drop_off_point_other
                                                ?? 'Unassigned');
                                        $stopName = trim((string) $stopName) ?: 'Unassigned';
                                        if ($prevStop !== $stopName) {
                                            $prevStop = $stopName;
                                            $groupNum = 0;
                                        }
                                        $groupNum++;
                                        $overallNum++;
                                    @endphp
                                    @if($groupNum === 1)
                                        <tr class="table-light">
                                            <td colspan="9" class="fw-semibold py-2">
                                                {{ $stopLegLabel }}: {{ $stopName }}
                                                <span class="text-muted fw-normal">({{ $stopCounts[$stopName] ?? $groupNum }})</span>
                                            </td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td class="text-muted">{{ $overallNum }}</td>
                                        <td class="fw-semibold">
                                            {{ $student->full_name }}
                                            <input type="hidden" name="points[{{ $idx }}][student_id]" value="{{ $student->id }}">
                                        </td>
                                        <td>{{ $student->admission_number }}</td>
                                        <td>{{ optional($student->classroom)->name ?? '—' }}</td>
                                        <td>{{ optional($student->stream)->name ?? '—' }}</td>
                                        <td>
                                            <select name="points[{{ $idx }}][morning_drop_off_point_id]" class="form-select form-select-sm">
                                                <option value="{{ $ownMeansPointId }}" @selected((int) $morningId === (int) $ownMeansPointId)>Own means (no morning transport)</option>
                                                @foreach($dropOffPoints as $point)
                                                    @continue((int) $point->id === (int) $ownMeansPointId)
                                                    <option value="{{ $point->id }}" @selected((int) $morningId === (int) $point->id)>{{ $point->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="points[{{ $idx }}][evening_drop_off_point_id]" class="form-select form-select-sm">
                                                <option value="{{ $ownMeansPointId }}" @selected((int) $eveningId === (int) $ownMeansPointId)>Own means (no evening transport)</option>
                                                @foreach($dropOffPoints as $point)
                                                    @continue((int) $point->id === (int) $ownMeansPointId)
                                                    <option value="{{ $point->id }}" @selected((int) $eveningId === (int) $point->id)>{{ $point->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            @if($row->morning_trip_id == $trip->id)
                                                <span class="badge bg-primary">Morning</span>
                                            @endif
                                            @if($row->evening_trip_id == $trip->id)
                                                <span class="badge bg-info">Evening</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <button type="button"
                                                    class="btn btn-sm btn-ghost-strong text-danger js-unassign-student"
                                                    data-action="{{ route('transport.trips.unassign', [$trip, $student]) }}">
                                                <i class="bi bi-x-circle"></i> Remove
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">No students assigned to this trip yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const searchUrl = @json(route('transport.trips.assign.search', $trip));
    const suggestUrl = @json(route('transport.trips.assign.suggest', $trip));
    const dropOffPoints = @json($dropOffPoints->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values());
    const ownMeansPointId = @json((int) $ownMeansPointId);
    const resolvePointUrl = @json(route('transport.dropoffpoints.resolve'));
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('#tripAssignForm input[name="_token"]')?.value
        || '';
    const searchInput = document.getElementById('tripStudentSearch');
    const resultsBody = document.getElementById('searchResultsBody');
    const suggestionsCard = document.getElementById('suggestionsCard');
    const suggestionsBody = document.getElementById('suggestionsBody');
    const suggestionsHint = document.getElementById('suggestionsHint');
    const draftBody = document.getElementById('draftBody');
    const draftHidden = document.getElementById('draftHiddenInputs');
    const draftCount = document.getElementById('draftCount');
    const saveBtn = document.getElementById('saveAssignBtn');
    const legSelect = document.getElementById('assignLeg');
    const currentTripName = @json($trip->trip_name);
    const alreadyAssigned = new Set((@json($assigned->pluck('student_id')->values())).map(Number));
    const draft = new Map();
    let debounceTimer = null;
    let lastSuggestFor = null;
    let lastSearchItems = [];
    let lastSuggestItems = [];

    const currentLeg = () => (legSelect.value === 'evening' ? 'evening' : 'morning');

    const syncLegHeaders = () => {
        const label = currentLeg() === 'evening' ? 'Evening drop-off' : 'Morning pickup';
        document.querySelectorAll('.js-leg-point-col').forEach((el) => { el.textContent = label; });
    };

    const needsStopForLeg = (stu) => {
        const leg = currentLeg();
        const pointId = leg === 'evening' ? stu.evening_point_id : stu.morning_point_id;
        return !pointId || Number(pointId) === Number(ownMeansPointId);
    };

    const validateDraftStops = () => {
        const missing = [];
        draft.forEach((stu) => {
            if (needsStopForLeg(stu)) {
                missing.push(stu.full_name || ('Student #' + stu.id));
            }
        });
        return missing;
    };

    const isLockedForLeg = (stu) => {
        const leg = currentLeg();
        if (leg === 'evening') {
            return Boolean(stu.on_trip_evening || stu.evening_trip_id || stu.evening_trip_name);
        }
        return Boolean(stu.on_trip_morning || stu.morning_trip_id || stu.morning_trip_name);
    };

    const tripBadgeForLeg = (stu) => {
        const leg = currentLeg();
        const tripName = leg === 'evening'
            ? (stu.evening_trip_name || (stu.on_trip_evening ? currentTripName : null))
            : (stu.morning_trip_name || (stu.on_trip_morning ? currentTripName : null));
        if (!tripName) return '';
        const onThis = leg === 'evening' ? stu.on_trip_evening : stu.on_trip_morning;
        const cls = onThis ? 'bg-secondary' : 'bg-warning text-dark';
        const prefix = leg === 'evening' ? 'Evening' : 'Morning';
        return ` <span class="badge ${cls}">${prefix}: ${escapeHtml(tripName)}</span>`;
    };

    const escapeHtml = (s) => {
        if (s == null || s === '') return '';
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    };

    const pointNameById = (id) => {
        if (!id) return null;
        const found = dropOffPoints.find((p) => Number(p.id) === Number(id));
        return found ? found.name : null;
    };

    const rememberPoint = (point) => {
        if (!dropOffPoints.some((p) => Number(p.id) === Number(point.id))) {
            dropOffPoints.push({ id: point.id, name: point.name });
            dropOffPoints.sort((a, b) => String(a.name).localeCompare(String(b.name)));
        }
    };

    const resolveNewPoint = async (name) => {
        const res = await fetch(resolvePointUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ name }),
        });
        if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            throw new Error(err.message || 'Could not create drop-off point.');
        }
        const point = await res.json();
        rememberPoint(point);
        return point;
    };

    const pointSelectHtml = (name, selectedId) => {
        const selected = selectedId ? Number(selectedId) : ownMeansPointId;
        const ownLabel = name === 'evening'
            ? 'Own means (no evening transport)'
            : 'Own means (no morning transport)';
        const opts = [
            `<option value="${ownMeansPointId}"${selected === Number(ownMeansPointId) ? ' selected' : ''}>${ownLabel}</option>`,
        ].concat(dropOffPoints.filter((p) => Number(p.id) !== Number(ownMeansPointId)).map((p) => {
            const sel = selected === Number(p.id) ? ' selected' : '';
            return `<option value="${p.id}"${sel}>${escapeHtml(p.name)}</option>`;
        }));
        opts.push('<option value="__new__">＋ Create new point…</option>');
        return `<select class="form-select form-select-sm" data-point-field="${name}" data-prev="${selected}">${opts.join('')}</select>`;
    };

    const syncDraftForm = () => {
        draftHidden.innerHTML = '';
        draft.forEach((stu) => {
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'student_ids[]';
            idInput.value = String(stu.id);
            draftHidden.appendChild(idInput);

            const morningInput = document.createElement('input');
            morningInput.type = 'hidden';
            morningInput.name = `morning_drop_off_point_ids[${stu.id}]`;
            morningInput.value = stu.morning_point_id ? String(stu.morning_point_id) : '';
            draftHidden.appendChild(morningInput);

            const eveningInput = document.createElement('input');
            eveningInput.type = 'hidden';
            eveningInput.name = `evening_drop_off_point_ids[${stu.id}]`;
            eveningInput.value = stu.evening_point_id ? String(stu.evening_point_id) : '';
            draftHidden.appendChild(eveningInput);
        });
        draftCount.textContent = draft.size + (draft.size === 1 ? ' selected' : ' selected');
        saveBtn.disabled = draft.size === 0;
        renderDraft();
    };

    const renderDraft = () => {
        const empty = document.getElementById('draftEmptyRow');
        draftBody.querySelectorAll('tr[data-draft-id]').forEach((tr) => tr.remove());
        if (draft.size === 0) {
            if (empty) empty.style.display = '';
            return;
        }
        if (empty) empty.style.display = 'none';
        draft.forEach((stu) => {
            const tr = document.createElement('tr');
            tr.dataset.draftId = String(stu.id);
            const needsStop = needsStopForLeg(stu);
            if (needsStop) {
                tr.classList.add('table-warning');
            }
            tr.innerHTML = `
                <td class="fw-semibold">${escapeHtml(stu.full_name)}${needsStop ? ' <span class="badge bg-warning text-dark">Pick stop</span>' : ''}</td>
                <td>${escapeHtml(stu.admission_number || '')}</td>
                <td>${escapeHtml(stu.classroom_name || '—')}</td>
                <td>${escapeHtml(stu.stream_name || '—')}</td>
                <td>${pointSelectHtml('morning', stu.morning_point_id)}</td>
                <td>${pointSelectHtml('evening', stu.evening_point_id)}</td>
                <td class="text-end">
                    <button type="button" class="btn btn-sm btn-ghost-strong text-danger" data-remove="${stu.id}">
                        <i class="bi bi-x-circle"></i> Remove
                    </button>
                </td>
            `;
            draftBody.appendChild(tr);

            tr.querySelectorAll('[data-point-field]').forEach((sel) => {
                sel.addEventListener('change', async () => {
                    const field = sel.getAttribute('data-point-field');
                    const current = draft.get(Number(stu.id));
                    if (!current) return;

                    if (sel.value === '__new__') {
                        const name = prompt('New drop-off point name:');
                        if (!name || !name.trim()) {
                            sel.value = sel.dataset.prev || String(ownMeansPointId);
                            return;
                        }
                        try {
                            const point = await resolveNewPoint(name.trim());
                            if (field === 'morning') {
                                current.morning_point_id = point.id;
                                current.morning_point = point.name;
                            } else {
                                current.evening_point_id = point.id;
                                current.evening_point = point.name;
                            }
                            draft.set(Number(stu.id), current);
                            syncDraftForm();
                            lastSuggestFor = null;
                            loadSuggestions(current);
                        } catch (e) {
                            alert(e.message || 'Failed to create point.');
                            sel.value = sel.dataset.prev || String(ownMeansPointId);
                        }
                        return;
                    }

                    const val = sel.value ? Number(sel.value) : ownMeansPointId;
                    sel.dataset.prev = String(val);
                    if (field === 'morning') {
                        current.morning_point_id = val || ownMeansPointId;
                        current.morning_point = pointNameById(current.morning_point_id) || 'Own means';
                    } else {
                        current.evening_point_id = val || ownMeansPointId;
                        current.evening_point = pointNameById(current.evening_point_id) || 'Own means';
                    }
                    draft.set(Number(stu.id), current);
                    syncDraftFormWithoutRerender();
                    lastSuggestFor = null;
                    loadSuggestions(current);
                });
            });
        });
        draftBody.querySelectorAll('[data-remove]').forEach((btn) => {
            btn.addEventListener('click', () => {
                draft.delete(Number(btn.getAttribute('data-remove')));
                syncDraftForm();
                refreshChecks();
            });
        });
    };

    const syncDraftFormWithoutRerender = () => {
        draftHidden.innerHTML = '';
        draft.forEach((stu) => {
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'student_ids[]';
            idInput.value = String(stu.id);
            draftHidden.appendChild(idInput);

            const morningInput = document.createElement('input');
            morningInput.type = 'hidden';
            morningInput.name = `morning_drop_off_point_ids[${stu.id}]`;
            morningInput.value = stu.morning_point_id ? String(stu.morning_point_id) : '';
            draftHidden.appendChild(morningInput);

            const eveningInput = document.createElement('input');
            eveningInput.type = 'hidden';
            eveningInput.name = `evening_drop_off_point_ids[${stu.id}]`;
            eveningInput.value = stu.evening_point_id ? String(stu.evening_point_id) : '';
            draftHidden.appendChild(eveningInput);
        });
        draftCount.textContent = draft.size + (draft.size === 1 ? ' selected' : ' selected');
        saveBtn.disabled = draft.size === 0;
    };

    const addToDraft = (stu, { suggest = true } = {}) => {
        const id = Number(stu.id);
        if (!id || draft.has(id) || isLockedForLeg(stu) || alreadyAssigned.has(id)) return false;
        draft.set(id, {
            ...stu,
            morning_point_id: stu.morning_point_id || null,
            evening_point_id: stu.evening_point_id || null,
        });
        syncDraftForm();
        if (suggest) loadSuggestions(stu);
        return true;
    };

    const refreshChecks = () => {
        document.querySelectorAll('tr[data-search-row]').forEach((tr) => {
            const stu = tr._studentData;
            const cb = tr.querySelector('input[data-student-check]');
            if (!stu || !cb) return;
            const locked = isLockedForLeg(stu);
            const id = Number(stu.id);
            cb.checked = draft.has(id);
            cb.disabled = locked;
            tr.classList.toggle('table-secondary', locked);
            tr.style.opacity = locked ? '0.65' : '';
        });
    };

    const studentRowHtml = (stu) => {
        const locked = isLockedForLeg(stu);
        const inDraft = draft.has(Number(stu.id));
        const leg = currentLeg();
        const point = leg === 'evening' ? (stu.evening_point || '—') : (stu.morning_point || '—');
        const badges = [];
        const tripBadge = tripBadgeForLeg(stu);
        if (tripBadge) badges.push(tripBadge.trim());
        const ownMeansOnLeg = needsStopForLeg(stu) && !locked;
        if (ownMeansOnLeg) {
            badges.push('<span class="badge bg-light text-dark border">Own means</span>');
        }
        if (inDraft && !locked) badges.push('<span class="badge bg-success">In draft</span>');
        const badgeHtml = badges.length ? ' ' + badges.join(' ') : '';
        return `
            <td>
                <input type="checkbox" class="form-check-input" data-student-check value="${stu.id}"
                    ${locked ? 'disabled' : ''} ${inDraft && !locked ? 'checked' : ''}>
            </td>
            <td class="fw-semibold">${escapeHtml(stu.full_name)}${badgeHtml}</td>
            <td>${escapeHtml(stu.admission_number || '')}</td>
            <td>${escapeHtml(stu.classroom_name || '—')}</td>
            <td>${escapeHtml(stu.stream_name || '—')}</td>
            <td>${escapeHtml(point)}</td>
        `;
    };

    const bindChecks = (container) => {
        container.querySelectorAll('input[data-student-check]').forEach((cb) => {
            cb.addEventListener('change', () => {
                const id = Number(cb.value);
                const row = cb.closest('tr');
                const stu = row && row._studentData ? row._studentData : { id };
                if (isLockedForLeg(stu)) {
                    cb.checked = false;
                    cb.disabled = true;
                    return;
                }
                if (cb.checked) {
                    addToDraft(stu, { suggest: true });
                } else {
                    draft.delete(id);
                    syncDraftForm();
                }
                refreshChecks();
            });
        });
    };

    const renderTable = (tbody, items, emptyMsg) => {
        tbody.innerHTML = '';
        if (!items.length) {
            tbody.innerHTML = `<tr class="text-muted"><td colspan="6" class="text-center py-4">${escapeHtml(emptyMsg)}</td></tr>`;
            return;
        }
        items.forEach((stu) => {
            const tr = document.createElement('tr');
            tr.dataset.searchRow = '1';
            tr._studentData = stu;
            if (isLockedForLeg(stu)) {
                tr.classList.add('table-secondary');
                tr.style.opacity = '0.65';
            }
            tr.innerHTML = studentRowHtml(stu);
            tbody.appendChild(tr);
        });
        bindChecks(tbody);
    };

    const search = async (q) => {
        if (!q || q.trim().length < 1) {
            lastSearchItems = [];
            resultsBody.innerHTML = '<tr class="text-muted"><td colspan="6" class="text-center py-4">Start typing to search students.</td></tr>';
            return;
        }
        resultsBody.innerHTML = '<tr class="text-muted"><td colspan="6" class="text-center py-4">Searching…</td></tr>';
        try {
            const res = await fetch(`${searchUrl}?q=${encodeURIComponent(q.trim())}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            const data = await res.json();
            lastSearchItems = Array.isArray(data) ? data : [];
            renderTable(resultsBody, lastSearchItems, 'No students found.');
        } catch (e) {
            resultsBody.innerHTML = '<tr class="text-danger"><td colspan="6" class="text-center py-4">Search failed. Try again.</td></tr>';
        }
    };

    const loadSuggestions = async (stu) => {
        const id = Number(stu.id);
        if (!id || lastSuggestFor === id) return;
        lastSuggestFor = id;
        const leg = currentLeg();
        const pointId = leg === 'evening' ? (stu.evening_point_id || '') : (stu.morning_point_id || '');
        try {
            let url = `${suggestUrl}?student_id=${id}&leg=${encodeURIComponent(leg)}`;
            if (pointId) url += `&point_id=${encodeURIComponent(pointId)}`;
            const res = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            const data = await res.json();
            lastSuggestItems = Array.isArray(data.students) ? data.students : [];
            const list = lastSuggestItems.filter((s) => !draft.has(Number(s.id)) && !isLockedForLeg(s));
            if (!list.length) {
                suggestionsCard.style.display = 'none';
                return;
            }
            suggestionsHint.textContent = data.point_label
                ? `Same ${leg === 'evening' ? 'evening drop-off' : 'morning pickup'}: ${data.point_label}`
                : 'Similar stop points';
            renderTable(suggestionsBody, list, 'No suggestions.');
            suggestionsCard.style.display = '';
        } catch (e) {
            suggestionsCard.style.display = 'none';
        }
    };

    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => search(searchInput.value), 350);
    });

    legSelect.addEventListener('change', () => {
        syncLegHeaders();
        lastSuggestFor = null;
        if (lastSearchItems.length) {
            renderTable(resultsBody, lastSearchItems, 'No students found.');
        }
        if (suggestionsCard.style.display !== 'none' && lastSuggestItems.length) {
            const list = lastSuggestItems.filter((s) => !draft.has(Number(s.id)) && !isLockedForLeg(s));
            if (list.length) {
                renderTable(suggestionsBody, list, 'No suggestions.');
            } else {
                suggestionsCard.style.display = 'none';
            }
        }
        refreshChecks();
    });

    document.getElementById('addAllResults').addEventListener('click', () => {
        resultsBody.querySelectorAll('tr').forEach((tr) => {
            if (tr._studentData && !isLockedForLeg(tr._studentData)) {
                addToDraft(tr._studentData, { suggest: false });
            }
        });
        refreshChecks();
        const first = [...draft.values()].slice(-1)[0];
        if (first) loadSuggestions(first);
    });

    document.getElementById('addAllSuggestions').addEventListener('click', () => {
        suggestionsBody.querySelectorAll('tr').forEach((tr) => {
            if (tr._studentData && !isLockedForLeg(tr._studentData)) {
                addToDraft(tr._studentData, { suggest: false });
            }
        });
        refreshChecks();
        suggestionsCard.style.display = 'none';
    });

    document.getElementById('clearDraftBtn').addEventListener('click', () => {
        draft.clear();
        lastSuggestFor = null;
        suggestionsCard.style.display = 'none';
        syncDraftForm();
        refreshChecks();
    });

    // Dedicated unassign forms (avoid clashing with points save form).
    document.querySelectorAll('.js-unassign-student').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (!confirm('Remove this student from the trip?')) return;
            const action = btn.getAttribute('data-action');
            const f = document.createElement('form');
            f.method = 'POST';
            f.action = action;
            f.style.display = 'none';
            const csrf = document.createElement('input');
            csrf.name = '_token';
            csrf.value = document.querySelector('meta[name="csrf-token"]')?.content
                || document.querySelector('#assignedPointsForm input[name="_token"]')?.value
                || '';
            const method = document.createElement('input');
            method.name = '_method';
            method.value = 'DELETE';
            f.appendChild(csrf);
            f.appendChild(method);
            document.body.appendChild(f);
            f.submit();
        });
    });

    document.getElementById('tripAssignForm')?.addEventListener('submit', (e) => {
        const missing = validateDraftStops();
        if (!missing.length) return;
        e.preventDefault();
        const legLabel = currentLeg() === 'evening' ? 'evening drop-off' : 'morning pickup';
        alert(`Select a real ${legLabel} (not Own means) for:\n\n` + missing.slice(0, 12).join('\n') + (missing.length > 12 ? '\n…' : ''));
        renderDraft();
    });

    syncLegHeaders();
    syncDraftForm();
})();
</script>
@endpush
