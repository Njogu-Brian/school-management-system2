@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <p class="eyebrow text-muted mb-1">Transport</p>
                <h1 class="mb-1">Student Drop-off Points</h1>
                <p class="text-muted mb-0">
                    Set morning pickup and evening drop-off. List prices recalculate from route rates — manage fees in Finance.
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('transport.dropoffpoints.index') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-geo-alt"></i> Point rates
                </a>
                <a href="{{ route('finance.transport-fees.index') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-cash-coin"></i> Transport fees
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
                @if(session('transport_fee_errors'))
                    <ul class="mb-0 mt-2">
                        @foreach(session('transport_fee_errors') as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="settings-card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold" for="classroom_id">Classroom</label>
                        <select name="classroom_id" id="classroom_id" class="form-select" onchange="this.form.submit()">
                            <option value="">Select class to load students</option>
                            @foreach($classrooms as $class)
                                <option value="{{ $class->id }}" @selected((int) $classroomId === (int) $class->id)>{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label fw-semibold" for="quickPointName">Learn / create drop-off point</label>
                        <div class="input-group">
                            <input type="text" id="quickPointName" class="form-control" placeholder="Type a new stop name, e.g. KANYARIRI POLICE" autocomplete="off">
                            <button type="button" class="btn btn-settings-primary" id="quickPointCreateBtn">
                                <i class="bi bi-plus-lg"></i> Add point
                            </button>
                        </div>
                        <small class="text-muted">Creates the stop if it does not exist (case-insensitive). Then pick it in the student rows.</small>
                    </div>
                </form>
            </div>
        </div>

        @if($classroomId && $students->isNotEmpty())
            <form method="POST" action="{{ route('transport.student-dropoffs.update') }}">
                @csrf
                <input type="hidden" name="classroom_id" value="{{ $classroomId }}">

                <div class="settings-card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="mb-0">Students</h5>
                            <small class="text-muted">{{ $students->count() }} student(s)</small>
                        </div>
                        <button type="submit" class="btn btn-settings-primary">
                            <i class="bi bi-save"></i> Save drop-off points
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-modern mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Student</th>
                                        <th>Admission</th>
                                        <th>Stream</th>
                                        <th style="min-width:200px;">Morning pickup</th>
                                        <th style="min-width:200px;">Evening drop-off</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($students as $idx => $student)
                                        @php
                                            $assignment = $assignmentMap[$student->id] ?? null;
                                            $morningId = $assignment?->morning_drop_off_point_id
                                                ?: ($student->drop_off_point_id ?: $ownMeansPointId);
                                            $eveningId = $assignment?->evening_drop_off_point_id
                                                ?: ($student->drop_off_point_id ?: $ownMeansPointId);
                                        @endphp
                                        <tr>
                                            <td class="fw-semibold">
                                                {{ $student->full_name }}
                                                <input type="hidden" name="points[{{ $idx }}][student_id]" value="{{ $student->id }}">
                                            </td>
                                            <td>{{ $student->admission_number }}</td>
                                            <td>{{ optional($student->stream)->name ?? '—' }}</td>
                                            <td>
                                                <select name="points[{{ $idx }}][morning_drop_off_point_id]" class="form-select form-select-sm js-point-select" data-prev="{{ $morningId }}">
                                                    <option value="{{ $ownMeansPointId }}" @selected((int) $morningId === (int) $ownMeansPointId)>Own means (no morning transport)</option>
                                                    @foreach($dropOffPoints as $point)
                                                        @continue((int) $point->id === (int) $ownMeansPointId)
                                                        <option value="{{ $point->id }}" @selected((int) $morningId === (int) $point->id)>{{ $point->name }}</option>
                                                    @endforeach
                                                    <option value="__new__">＋ Create new point…</option>
                                                </select>
                                            </td>
                                            <td>
                                                <select name="points[{{ $idx }}][evening_drop_off_point_id]" class="form-select form-select-sm js-point-select" data-prev="{{ $eveningId }}">
                                                    <option value="{{ $ownMeansPointId }}" @selected((int) $eveningId === (int) $ownMeansPointId)>Own means (no evening transport)</option>
                                                    @foreach($dropOffPoints as $point)
                                                        @continue((int) $point->id === (int) $ownMeansPointId)
                                                        <option value="{{ $point->id }}" @selected((int) $eveningId === (int) $point->id)>{{ $point->name }}</option>
                                                    @endforeach
                                                    <option value="__new__">＋ Create new point…</option>
                                                </select>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </form>
        @elseif($classroomId)
            <div class="settings-card">
                <div class="card-body text-center text-muted py-5">No students found in this classroom.</div>
            </div>
        @else
            <div class="settings-card">
                <div class="card-body text-center text-muted py-5">Select a classroom to manage student drop-off points.</div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const resolveUrl = @json(route('transport.dropoffpoints.resolve'));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('input[name="_token"]')?.value
        || '';

    const addPointToAllSelects = (point, selectToPick) => {
        document.querySelectorAll('select.js-point-select').forEach((sel) => {
            if ([...sel.options].some((o) => Number(o.value) === Number(point.id))) {
                return;
            }
            const opt = document.createElement('option');
            opt.value = String(point.id);
            opt.textContent = point.name;
            const newOpt = sel.querySelector('option[value="__new__"]');
            if (newOpt) {
                sel.insertBefore(opt, newOpt);
            } else {
                sel.appendChild(opt);
            }
        });
        if (selectToPick) {
            selectToPick.value = String(point.id);
            selectToPick.dataset.prev = String(point.id);
        }
    };

    const resolvePoint = async (name) => {
        const res = await fetch(resolveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ name }),
        });
        if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            throw new Error(err.message || 'Could not create drop-off point.');
        }
        return res.json();
    };

    document.getElementById('quickPointCreateBtn')?.addEventListener('click', async () => {
        const input = document.getElementById('quickPointName');
        const name = (input?.value || '').trim();
        if (!name) {
            alert('Enter a drop-off point name.');
            return;
        }
        try {
            const point = await resolvePoint(name);
            addPointToAllSelects(point, null);
            if (input) input.value = '';
            alert(point.created ? `Created “${point.name}”.` : `“${point.name}” already exists.`);
        } catch (e) {
            alert(e.message || 'Failed to create point.');
        }
    });

    document.querySelectorAll('select.js-point-select').forEach((sel) => {
        sel.addEventListener('change', async () => {
            if (sel.value !== '__new__') {
                sel.dataset.prev = sel.value;
                return;
            }
            const name = prompt('New drop-off point name:');
            if (!name || !name.trim()) {
                sel.value = sel.dataset.prev || '';
                return;
            }
            try {
                const point = await resolvePoint(name.trim());
                addPointToAllSelects(point, sel);
            } catch (e) {
                alert(e.message || 'Failed to create point.');
                sel.value = sel.dataset.prev || '';
            }
        });
    });
})();
</script>
@endpush
