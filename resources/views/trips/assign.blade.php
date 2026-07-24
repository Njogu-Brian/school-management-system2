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
            <div class="settings-card mb-3">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Assign as</label>
                            <select name="leg" id="assignLeg" class="form-select" required>
                                <option value="morning" @selected(old('leg', $defaultLeg) === 'morning')>Morning trip</option>
                                <option value="evening" @selected(old('leg', $defaultLeg) === 'evening')>Evening trip</option>
                            </select>
                            <small class="text-muted">Pickup trips usually use morning; drop-off uses evening.</small>
                        </div>
                        <div class="col-md-9">
                            <label class="form-label fw-semibold">Search students (name or admission #)</label>
                            <input type="text" id="tripStudentSearch" class="form-control" placeholder="Type name or admission number…" autocomplete="off">
                            <small class="text-muted">Results appear below. Check students, then save.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="settings-card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Search results</h5>
                    <div class="d-flex gap-2 align-items-center">
                        <button type="button" class="btn btn-sm btn-ghost-strong" id="selectAllResults">Select all</button>
                        <button type="submit" class="btn btn-settings-primary" id="saveAssignBtn" disabled>
                            <i class="bi bi-save"></i> Save selected
                        </button>
                    </div>
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
                                </tr>
                            </thead>
                            <tbody id="searchResultsBody">
                                <tr class="text-muted">
                                    <td colspan="5" class="text-center py-4">Start typing to search students.</td>
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
                                <th>Leg</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assigned as $row)
                                @php $student = $row->student; @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $student->full_name }}</td>
                                    <td>{{ $student->admission_number }}</td>
                                    <td>{{ optional($student->classroom)->name ?? '—' }}</td>
                                    <td>{{ optional($student->stream)->name ?? '—' }}</td>
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
                                    <td colspan="6" class="text-center text-muted py-4">No students assigned to this trip yet.</td>
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
    const searchInput = document.getElementById('tripStudentSearch');
    const resultsBody = document.getElementById('searchResultsBody');
    const saveBtn = document.getElementById('saveAssignBtn');
    const selectAllBtn = document.getElementById('selectAllResults');
    const alreadyAssigned = new Set((@json($assigned->pluck('student_id')->values())).map(Number));
    let debounceTimer = null;

    const escapeHtml = (s) => {
        if (s == null || s === '') return '';
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    };

    const updateSaveState = () => {
        const checked = resultsBody.querySelectorAll('input[name="student_ids[]"]:checked').length;
        saveBtn.disabled = checked === 0;
    };

    const renderResults = (items) => {
        resultsBody.innerHTML = '';
        if (!items.length) {
            resultsBody.innerHTML = '<tr class="text-muted"><td colspan="5" class="text-center py-4">No students found.</td></tr>';
            updateSaveState();
            return;
        }
        items.forEach((stu) => {
            const tr = document.createElement('tr');
            const onTrip = alreadyAssigned.has(Number(stu.id));
            tr.innerHTML = `
                <td>
                    <input type="checkbox" class="form-check-input" name="student_ids[]" value="${stu.id}"
                        ${onTrip ? 'disabled' : ''}>
                </td>
                <td class="fw-semibold">${escapeHtml(stu.full_name)}${onTrip ? ' <span class="badge bg-secondary">On trip</span>' : ''}</td>
                <td>${escapeHtml(stu.admission_number || '')}</td>
                <td>${escapeHtml(stu.classroom_name || '—')}</td>
                <td>${escapeHtml(stu.stream_name || '—')}</td>
            `;
            resultsBody.appendChild(tr);
        });
        resultsBody.querySelectorAll('input[name="student_ids[]"]').forEach((cb) => {
            cb.addEventListener('change', updateSaveState);
        });
        updateSaveState();
    };

    const search = async (q) => {
        if (!q || q.trim().length < 1) {
            resultsBody.innerHTML = '<tr class="text-muted"><td colspan="5" class="text-center py-4">Start typing to search students.</td></tr>';
            updateSaveState();
            return;
        }
        resultsBody.innerHTML = '<tr class="text-muted"><td colspan="5" class="text-center py-4">Searching…</td></tr>';
        try {
            const res = await fetch(`/students/search?q=${encodeURIComponent(q.trim())}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            const data = await res.json();
            renderResults(Array.isArray(data) ? data : []);
        } catch (e) {
            resultsBody.innerHTML = '<tr class="text-danger"><td colspan="5" class="text-center py-4">Search failed. Try again.</td></tr>';
            updateSaveState();
        }
    };

    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => search(searchInput.value), 350);
    });

    selectAllBtn.addEventListener('click', () => {
        resultsBody.querySelectorAll('input[name="student_ids[]"]:not(:disabled)').forEach((cb) => {
            cb.checked = true;
        });
        updateSaveState();
    });
})();
</script>
@endpush
