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
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Assign as</label>
                            <select name="leg" id="assignLeg" class="form-select" required>
                                <option value="morning" @selected(old('leg', $defaultLeg) === 'morning')>Morning trip (pickup)</option>
                                <option value="evening" @selected(old('leg', $defaultLeg) === 'evening')>Evening trip (drop-off)</option>
                            </select>
                        </div>
                        <div class="col-md-9">
                            <label class="form-label fw-semibold">Search students (name or admission #)</label>
                            <input type="text" id="tripStudentSearch" class="form-control" placeholder="Type name or admission number…" autocomplete="off">
                            <small class="text-muted">Check students to add them to the draft below. Keep searching — nothing saves until you click Save draft.</small>
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
                        <table class="table table-modern mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:40px;"></th>
                                    <th>Name</th>
                                    <th>Admission</th>
                                    <th>Class</th>
                                    <th>Stream</th>
                                    <th>Morning pickup</th>
                                    <th>Evening drop-off</th>
                                </tr>
                            </thead>
                            <tbody id="searchResultsBody">
                                <tr class="text-muted">
                                    <td colspan="7" class="text-center py-4">Start typing to search students.</td>
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
                        <table class="table table-modern mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:40px;"></th>
                                    <th>Name</th>
                                    <th>Admission</th>
                                    <th>Class</th>
                                    <th>Stream</th>
                                    <th>Morning pickup</th>
                                    <th>Evening drop-off</th>
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
                        <small class="text-muted">Students accumulate here as you check them. Save once when ready.</small>
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
                                    <th>Morning pickup</th>
                                    <th>Evening drop-off</th>
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

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Currently on this trip</h5>
                <span class="input-chip">{{ $assigned->count() }} student(s)</span>
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
                                <th>Morning pickup</th>
                                <th>Evening drop-off</th>
                                <th>Leg</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assigned as $row)
                                @php
                                    $student = $row->student;
                                    $morningPoint = optional($row->morningDropOffPoint)->name
                                        ?? optional($student->dropOffPoint)->name
                                        ?? $student->drop_off_point_other
                                        ?? '—';
                                    $eveningPoint = optional($row->eveningDropOffPoint)->name
                                        ?? optional($student->dropOffPoint)->name
                                        ?? $student->drop_off_point_other
                                        ?? '—';
                                @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $student->full_name }}</td>
                                    <td>{{ $student->admission_number }}</td>
                                    <td>{{ optional($student->classroom)->name ?? '—' }}</td>
                                    <td>{{ optional($student->stream)->name ?? '—' }}</td>
                                    <td>{{ $morningPoint }}</td>
                                    <td>{{ $eveningPoint }}</td>
                                    <td>
                                        @if($row->morning_trip_id == $trip->id)
                                            <span class="badge bg-primary">Morning</span>
                                        @endif
                                        @if($row->evening_trip_id == $trip->id)
                                            <span class="badge bg-info">Evening</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <form action="{{ route('transport.trips.unassign', [$trip, $student]) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Remove this student from the trip?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-ghost-strong text-danger">
                                                <i class="bi bi-x-circle"></i> Remove
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No students assigned to this trip yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const searchUrl = @json(route('transport.trips.assign.search', $trip));
    const suggestUrl = @json(route('transport.trips.assign.suggest', $trip));
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
    const alreadyAssigned = new Set((@json($assigned->pluck('student_id')->values())).map(Number));
    const draft = new Map();
    let debounceTimer = null;
    let lastSuggestFor = null;

    const escapeHtml = (s) => {
        if (s == null || s === '') return '';
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    };

    const syncDraftForm = () => {
        draftHidden.innerHTML = '';
        draft.forEach((stu) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'student_ids[]';
            input.value = String(stu.id);
            draftHidden.appendChild(input);
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
            tr.innerHTML = `
                <td class="fw-semibold">${escapeHtml(stu.full_name)}</td>
                <td>${escapeHtml(stu.admission_number || '')}</td>
                <td>${escapeHtml(stu.classroom_name || '—')}</td>
                <td>${escapeHtml(stu.stream_name || '—')}</td>
                <td>${escapeHtml(stu.morning_point || '—')}</td>
                <td>${escapeHtml(stu.evening_point || '—')}</td>
                <td class="text-end">
                    <button type="button" class="btn btn-sm btn-ghost-strong text-danger" data-remove="${stu.id}">
                        <i class="bi bi-x-circle"></i> Remove
                    </button>
                </td>
            `;
            draftBody.appendChild(tr);
        });
        draftBody.querySelectorAll('[data-remove]').forEach((btn) => {
            btn.addEventListener('click', () => {
                draft.delete(Number(btn.getAttribute('data-remove')));
                syncDraftForm();
                refreshChecks();
            });
        });
    };

    const addToDraft = (stu, { suggest = true } = {}) => {
        const id = Number(stu.id);
        if (!id || alreadyAssigned.has(id) || draft.has(id)) return false;
        draft.set(id, stu);
        syncDraftForm();
        if (suggest) loadSuggestions(stu);
        return true;
    };

    const refreshChecks = () => {
        document.querySelectorAll('input[data-student-check]').forEach((cb) => {
            const id = Number(cb.value);
            cb.checked = draft.has(id);
            cb.disabled = alreadyAssigned.has(id);
        });
    };

    const studentRowHtml = (stu) => {
        const onTrip = alreadyAssigned.has(Number(stu.id)) || stu.on_trip;
        const inDraft = draft.has(Number(stu.id));
        return `
            <td>
                <input type="checkbox" class="form-check-input" data-student-check value="${stu.id}"
                    ${onTrip ? 'disabled' : ''} ${inDraft ? 'checked' : ''}>
            </td>
            <td class="fw-semibold">${escapeHtml(stu.full_name)}${onTrip ? ' <span class="badge bg-secondary">On trip</span>' : ''}${inDraft && !onTrip ? ' <span class="badge bg-success">In draft</span>' : ''}</td>
            <td>${escapeHtml(stu.admission_number || '')}</td>
            <td>${escapeHtml(stu.classroom_name || '—')}</td>
            <td>${escapeHtml(stu.stream_name || '—')}</td>
            <td>${escapeHtml(stu.morning_point || '—')}</td>
            <td>${escapeHtml(stu.evening_point || '—')}</td>
        `;
    };

    const bindChecks = (container) => {
        container.querySelectorAll('input[data-student-check]').forEach((cb) => {
            cb.addEventListener('change', () => {
                const id = Number(cb.value);
                if (cb.checked) {
                    const row = cb.closest('tr');
                    const stu = row && row._studentData ? row._studentData : { id };
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
            tbody.innerHTML = `<tr class="text-muted"><td colspan="7" class="text-center py-4">${escapeHtml(emptyMsg)}</td></tr>`;
            return;
        }
        items.forEach((stu) => {
            const tr = document.createElement('tr');
            tr._studentData = stu;
            tr.innerHTML = studentRowHtml(stu);
            tbody.appendChild(tr);
        });
        bindChecks(tbody);
    };

    const search = async (q) => {
        if (!q || q.trim().length < 1) {
            resultsBody.innerHTML = '<tr class="text-muted"><td colspan="7" class="text-center py-4">Start typing to search students.</td></tr>';
            return;
        }
        resultsBody.innerHTML = '<tr class="text-muted"><td colspan="7" class="text-center py-4">Searching…</td></tr>';
        try {
            const res = await fetch(`${searchUrl}?q=${encodeURIComponent(q.trim())}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            const data = await res.json();
            renderTable(resultsBody, Array.isArray(data) ? data : [], 'No students found.');
        } catch (e) {
            resultsBody.innerHTML = '<tr class="text-danger"><td colspan="7" class="text-center py-4">Search failed. Try again.</td></tr>';
        }
    };

    const loadSuggestions = async (stu) => {
        const id = Number(stu.id);
        if (!id || lastSuggestFor === id) return;
        lastSuggestFor = id;
        const leg = legSelect.value || 'morning';
        try {
            const res = await fetch(`${suggestUrl}?student_id=${id}&leg=${encodeURIComponent(leg)}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            const data = await res.json();
            const list = Array.isArray(data.students) ? data.students.filter((s) => !draft.has(Number(s.id)) && !alreadyAssigned.has(Number(s.id))) : [];
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

    document.getElementById('addAllResults').addEventListener('click', () => {
        resultsBody.querySelectorAll('tr').forEach((tr) => {
            if (tr._studentData) addToDraft(tr._studentData, { suggest: false });
        });
        refreshChecks();
        const first = [...draft.values()].slice(-1)[0];
        if (first) loadSuggestions(first);
    });

    document.getElementById('addAllSuggestions').addEventListener('click', () => {
        suggestionsBody.querySelectorAll('tr').forEach((tr) => {
            if (tr._studentData) addToDraft(tr._studentData, { suggest: false });
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

    syncDraftForm();
})();
</script>
@endpush
