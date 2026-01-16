@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Mark Swimming Attendance',
        'icon' => 'bi bi-water',
        'subtitle' => 'Record swimming attendance and charge student wallets',
        'actions' => '<a href="' . route('swimming.attendance.index') . '" class="btn btn-finance btn-finance-secondary"><i class="bi bi-list"></i> View Records</a>'
    ])

    @include('finance.invoices.partials.alerts')

    <!-- Filters -->
    <div class="finance-filter-card finance-animate shadow-sm rounded-4 border-0 mb-4">
        <form method="GET" action="{{ route('swimming.attendance.create') }}" class="row g-3">
            <div class="col-md-4">
                <label class="finance-form-label">Classroom</label>
                <select name="classroom_id" class="finance-form-select" onchange="this.form.submit()">
                    @foreach($classrooms as $classroom)
                        <option value="{{ $classroom->id }}" {{ $selected_classroom->id == $classroom->id ? 'selected' : '' }}>
                            {{ $classroom->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="finance-form-label">Date</label>
                <input type="date" name="date" class="finance-form-control" value="{{ $selected_date }}" onchange="this.form.submit()">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div class="text-muted small">
                    <i class="bi bi-info-circle"></i> Per visit cost: <strong>Ksh {{ number_format($per_visit_cost, 2) }}</strong>
                </div>
            </div>
        </form>
    </div>

    <!-- Attendance Form -->
    @if($students->isNotEmpty())
    <form method="POST" action="{{ route('swimming.attendance.store') }}" id="attendanceForm">
        @csrf
        <input type="hidden" name="classroom_id" value="{{ $selected_classroom->id }}">
        <input type="hidden" name="date" value="{{ $selected_date }}">

        <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mb-3">
            <div class="finance-card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">{{ $selected_classroom->name }} - {{ \Carbon\Carbon::parse($selected_date)->format('d M Y') }}</h5>
                    <p class="text-muted small mb-0">{{ $students->count() }} student(s)</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-finance btn-finance-outline" onclick="selectAll()">
                        <i class="bi bi-check-all"></i> Select All
                    </button>
                    <button type="button" class="btn btn-sm btn-finance btn-finance-outline" onclick="deselectAll()">
                        <i class="bi bi-x-circle"></i> Deselect All
                    </button>
                </div>
            </div>
            <div class="finance-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" id="selectAllCheckbox" onchange="toggleAll(this)">
                                </th>
                                <th>#</th>
                                <th>Admission #</th>
                                <th>Student Name</th>
                                <th class="text-end">Wallet Balance</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $index => $student)
                                @php
                                    $existing = $attendance_records->get($student->id);
                                    $wallet = \App\Models\SwimmingWallet::getOrCreateForStudent($student->id);
                                @endphp
                                <tr>
                                    <td>
                                        <input type="checkbox" 
                                               name="student_ids[]" 
                                               value="{{ $student->id }}" 
                                               class="student-checkbox"
                                               {{ $existing ? 'checked' : '' }}>
                                    </td>
                                    <td>{{ $index + 1 }}</td>
                                    <td><strong>{{ $student->admission_number }}</strong></td>
                                    <td>{{ $student->first_name }} {{ $student->last_name }}</td>
                                    <td class="text-end">
                                        <span class="fw-bold {{ $wallet->balance >= $per_visit_cost ? 'text-success' : 'text-danger' }}">
                                            Ksh {{ number_format($wallet->balance, 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($existing)
                                            <span class="badge bg-success">Already Marked</span>
                                        @else
                                            <span class="badge bg-secondary">Not Marked</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mb-4">
            <div class="finance-card-body">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="finance-form-label">Notes (Optional)</label>
                        <textarea name="notes" class="finance-form-control" rows="2" placeholder="Additional notes about this attendance session">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('swimming.attendance.index') }}" class="btn btn-finance btn-finance-secondary">
                <i class="bi bi-x-circle"></i> Cancel
            </a>
            <button type="submit" class="btn btn-finance btn-finance-primary">
                <i class="bi bi-check-circle"></i> Save Attendance
            </button>
        </div>
    </form>
    @else
        <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
            <div class="finance-card-body text-center py-5">
                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                <p class="text-muted mt-3 mb-0">No students found in this classroom</p>
            </div>
        </div>
    @endif
  </div>
</div>

<script>
function toggleAll(checkbox) {
    const checkboxes = document.querySelectorAll('.student-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
}

function selectAll() {
    document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = true);
    document.getElementById('selectAllCheckbox').checked = true;
}

function deselectAll() {
    document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('selectAllCheckbox').checked = false;
}

document.getElementById('attendanceForm')?.addEventListener('submit', function(e) {
    const checked = document.querySelectorAll('.student-checkbox:checked').length;
    if (checked === 0) {
        e.preventDefault();
        alert('Please select at least one student.');
        return false;
    }
});
</script>
@endsection
