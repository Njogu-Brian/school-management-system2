@extends('layouts.app')

@section('content')
<div class="container">
    <h3 class="mb-4">📊 Attendance Reports</h3>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabClass" type="button" role="tab">
                By Class / Stream
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabStudent" type="button" role="tab">
                By Student
            </button>
        </li>
    </ul>

    <div class="tab-content">
        {{-- ==================== CLASS / STREAM ==================== --}}
        <div class="tab-pane fade show active" id="tabClass" role="tabpanel">
            <form method="GET" action="{{ route('attendance.records') }}" class="row g-3 mb-3">
                <div class="col-md-3">
                    <label class="form-label">Class</label>
                    <select name="class" class="form-select" onchange="this.form.submit()">
                        <option value="">All</option>
                        @foreach($classes as $id => $name)
                            <option value="{{ $id }}" {{ $selectedClass == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Stream</label>
                    <select name="stream" class="form-select" onchange="this.form.submit()">
                        <option value="">All</option>
                        @foreach($streams as $id => $name)
                            <option value="{{ $id }}" {{ $selectedStream == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Start</label>
                    <input type="date" name="start" value="{{ $startDate }}" class="form-control" onchange="this.form.submit()">
                </div>
                <div class="col-md-3">
                    <label class="form-label">End</label>
                    <input type="date" name="end" value="{{ $endDate }}" class="form-control" onchange="this.form.submit()">
                </div>
            </form>

            {{-- Summary cards --}}
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="fw-bold">Present</div>
                            <div class="fs-4">{{ $summary['totals']['present'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="fw-bold">Absent</div>
                            <div class="fs-4">{{ $summary['totals']['absent'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="fw-bold">Late</div>
                            <div class="fs-4">{{ $summary['totals']['late'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    @php
                        $t = max(1, $summary['totals']['all'] ?? 0);
                        $pct = round((($summary['totals']['present'] ?? 0) / $t) * 100, 1);
                    @endphp
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="fw-bold">Attendance %</div>
                            <div class="fs-4">{{ $pct }}%</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Gender summary --}}
            <div class="row g-3 mb-4">
                @foreach(['male'=>'Boys','female'=>'Girls','other'=>'Other/Unspecified'] as $gKey => $gLabel)
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-header fw-bold">{{ $gLabel }}</div>
                        <div class="card-body">
                            <div>Present: <span class="badge bg-success">{{ $summary['gender'][$gKey]['present'] ?? 0 }}</span></div>
                            <div>Absent: <span class="badge bg-danger">{{ $summary['gender'][$gKey]['absent'] ?? 0 }}</span></div>
                            <div>Late: <span class="badge bg-warning text-dark">{{ $summary['gender'][$gKey]['late'] ?? 0 }}</span></div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- (Optional) Mini chart --}}
            <div class="card mb-4 shadow-sm">
                <div class="card-body">
                    <canvas id="attMiniChart" height="110"></canvas>
                </div>
            </div>

            {{-- Records grouped by date --}}
            @forelse($groupedByDate as $date => $items)
                <div class="card mb-3 shadow-sm">
                    <div class="card-header fw-bold">{{ $date }}</div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Class</th>
                                    <th>Stream</th>
                                    <th>Status</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $a)
                                    <tr>
                                        <td>{{ $a->student->full_name ?? 'Unknown' }}</td>
                                        <td>{{ $a->student->classroom->name ?? '-' }}</td>
                                        <td>{{ $a->student->stream->name ?? '-' }}</td>
                                        <td>
                                            <span class="badge
                                                {{ $a->status == 'present' ? 'bg-success' : ($a->status == 'late' ? 'bg-warning text-dark' : 'bg-danger') }}">
                                                {{ ucfirst($a->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $a->reason ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="alert alert-info">No records found for this selection.</div>
            @endforelse
        </div>

        {{-- ==================== STUDENT ==================== --}}
        <div class="tab-pane fade" id="tabStudent" role="tabpanel">
            <form method="GET" action="{{ route('attendance.records') }}" class="row g-3 mb-3">
                <input type="hidden" name="start" value="{{ $startDate }}">
                <input type="hidden" name="end" value="{{ $endDate }}">
                <div class="col-md-6">
                    <label class="form-label">Student</label>
                    <div class="input-group">
                        <input type="hidden" id="selectedStudentId" name="student_id" value="{{ $student->id ?? '' }}">
                        <input type="text" id="selectedStudentName"
                               class="form-control" placeholder="Search by name or admission #" 
                               value="{{ $student ? ($student->full_name.' ('.$student->admission_number.')') : '' }}"
                               readonly>
                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#studentSearchModal">
                            Search
                        </button>
                        <button class="btn btn-primary" type="submit">Load</button>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Start</label>
                    <input type="date" name="start" value="{{ $startDate }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">End</label>
                    <input type="date" name="end" value="{{ $endDate }}" class="form-control">
                </div>
            </form>

            @if($student)
                <div class="card mb-3 shadow-sm">
                    <div class="card-body">
                        <div class="fw-bold">{{ $student->full_name ?? 'Unknown' }} ({{ $student->admission_number ?? '-' }})</div>
                        <div class="text-muted">
                            Class: {{ $student->classroom->name ?? 'N/A' }} | Stream: {{ $student->stream->name ?? 'N/A' }}
                        </div>
                        <div class="mt-2">
                            Attendance %: <span class="badge bg-primary">{{ $studentStats['percent'] ?? 0 }}%</span>
                            <span class="ms-3">Present: <span class="badge bg-success">{{ $studentStats['present'] ?? 0 }}</span></span>
                            <span class="ms-2">Absent: <span class="badge bg-danger">{{ $studentStats['absent'] ?? 0 }}</span></span>
                            <span class="ms-2">Late: <span class="badge bg-warning text-dark">{{ $studentStats['late'] ?? 0 }}</span></span>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header fw-bold">Records</div>
                    <div class="card-body table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($studentRecords as $r)
                                    <tr>
                                        <td>{{ $r->date }}</td>
                                        <td>
                                            <span class="badge
                                                {{ $r->status == 'present' ? 'bg-success' : ($r->status == 'late' ? 'bg-warning text-dark' : 'bg-danger') }}">
                                                {{ ucfirst($r->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $r->reason ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center">No attendance found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Student Search Modal --}}
<div class="modal fade" id="studentSearchModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h5 class="modal-title">Search Student</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="text" id="studentSearchInput" class="form-control mb-3" placeholder="Type name or admission number...">
        <ul id="studentSearchResults" class="list-group"></ul>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- tiny chart from summary ---
    const c = document.getElementById('attMiniChart');
    if (c) {
        const chart = new Chart(c, {
            type: 'bar',
            data: {
                labels: ['Present','Absent','Late'],
                datasets: [{
                    label: 'Totals',
                    data: [
                        {{ $summary['totals']['present'] ?? 0 }},
                        {{ $summary['totals']['absent'] ?? 0 }},
                        {{ $summary['totals']['late'] ?? 0 }}
                    ]
                }]
            },
            options: { responsive: true, plugins: { legend: { display:false } } }
        });
    }

    // --- student search ---
    const input = document.getElementById('studentSearchInput');
    const list  = document.getElementById('studentSearchResults');
    if (input) {
        let timer = null;
        input.addEventListener('keyup', function() {
            clearTimeout(timer);
            const q = this.value.trim();
            if (q.length < 2) { list.innerHTML=''; return; }
            timer = setTimeout(() => {
                fetch("{{ route('students.search') }}?q=" + encodeURIComponent(q))
                    .then(r => r.json())
                    .then(rows => {
                        list.innerHTML = rows.length
                          ? rows.map(s => `<li class="list-group-item list-group-item-action pick" data-id="${s.id}" data-name="${s.full_name}" data-adm="${s.admission_number}">
                                ${s.full_name} (${s.admission_number})
                             </li>`).join('')
                          : `<li class="list-group-item text-muted">No results</li>`;

                        document.querySelectorAll('.pick').forEach(el => {
                            el.addEventListener('click', () => {
                                document.getElementById('selectedStudentId').value   = el.dataset.id;
                                document.getElementById('selectedStudentName').value = `${el.dataset.name} (${el.dataset.adm})`;
                                bootstrap.Modal.getInstance(document.getElementById('studentSearchModal')).hide();
                            });
                        });
                    });
            }, 400);
        });
    }
});
</script>
@endpush
@endsection
