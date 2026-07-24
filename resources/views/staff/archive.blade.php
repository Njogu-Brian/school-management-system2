@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">HR & Payroll / Staff</div>
                <h1 class="mb-1">Archive Staff</h1>
                <p class="text-muted mb-0">Archive <strong>{{ $staff->full_name }}</strong> and choose what happens to their teaching assignments.</p>
            </div>
            <a href="{{ route('staff.index') }}" class="btn btn-ghost-strong">
                <i class="bi bi-arrow-left"></i> Back to Staff
            </a>
        </div>

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="settings-card mb-3">
            <div class="card-body d-flex align-items-center gap-3 flex-wrap">
                <div>
                    <div class="fw-semibold">{{ $staff->full_name }}</div>
                    <div class="text-muted small">{{ $staff->work_email }} · {{ $staff->staff_id ?? 'Ref #' . $staff->id }}</div>
                </div>
                @if($staff->user?->roles?->isNotEmpty())
                    @foreach($staff->user->roles as $role)
                        <span class="pill-badge pill-secondary">{{ $role->name }}</span>
                    @endforeach
                @endif
            </div>
        </div>

        <div class="settings-card mb-3">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-mortarboard"></i> Teaching Assignments</h5>
            </div>
            <div class="card-body">
                @if($assignmentSummary['has_assignments'])
                    <p class="text-muted small mb-3">
                        This staff member currently holds <strong>{{ $assignmentSummary['counts']['total'] }}</strong> teaching assignment(s).
                        When archived, these must be cleared or moved to another teacher.
                    </p>
                    <ul class="list-group list-group-flush border rounded mb-0" style="max-height: 280px; overflow-y: auto;">
                        @foreach($assignmentSummary['items'] as $item)
                            <li class="list-group-item py-2 small d-flex align-items-center gap-2">
                                @if($item['type'] === 'class_teacher')
                                    <i class="bi bi-person-check text-success"></i>
                                @elseif($item['type'] === 'assistant_teacher')
                                    <i class="bi bi-person-plus text-info"></i>
                                @else
                                    <i class="bi bi-book text-primary"></i>
                                @endif
                                {{ $item['label'] }}
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="alert alert-info alert-soft border-0 mb-0">
                        <i class="bi bi-info-circle"></i> No active teaching assignments found. You can archive this staff member directly.
                    </div>
                @endif
            </div>
        </div>

        <form action="{{ route('staff.archive', $staff->id) }}" method="POST" class="settings-card" id="archiveStaffForm">
            @csrf
            @method('PATCH')

            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-archive"></i> Assignment handling</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="assignment_action" id="actionLeaveBlank" value="leave_blank"
                            @checked(old('assignment_action', 'leave_blank') === 'leave_blank')>
                        <label class="form-check-label" for="actionLeaveBlank">
                            <strong>Leave assignments blank</strong>
                            <div class="text-muted small">Remove this teacher from all class, stream, and subject slots. Slots will show as unassigned.</div>
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="assignment_action" id="actionTransfer" value="transfer"
                            @checked(old('assignment_action') === 'transfer') @disabled($replacementCandidates->isEmpty())>
                        <label class="form-check-label" for="actionTransfer">
                            <strong>Transfer all assignments to another teacher</strong>
                            <div class="text-muted small">Move every assignment listed above to the selected replacement staff member.</div>
                        </label>
                    </div>
                </div>

                <div id="replacementStaffWrap" class="mb-0" style="display: none;">
                    <label class="form-label" for="replacement_staff_id">Replacement teacher <span class="text-danger">*</span></label>
                    <select name="replacement_staff_id" id="replacement_staff_id" class="form-select">
                        <option value="">— Select staff —</option>
                        @foreach($replacementCandidates as $candidate)
                            <option value="{{ $candidate->id }}" @selected((string) old('replacement_staff_id') === (string) $candidate->id)>
                                {{ $candidate->full_name }}
                                @if($candidate->staff_id) ({{ $candidate->staff_id }}) @endif
                            </option>
                        @endforeach
                    </select>
                    @if($replacementCandidates->isEmpty())
                        <small class="text-muted">No other active teaching staff available for transfer.</small>
                    @endif
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="text-muted small">Archiving does not delete the staff record — it can be restored later.</span>
                <div class="d-flex gap-2">
                    <a href="{{ route('staff.index') }}" class="btn btn-ghost-strong">Cancel</a>
                    <button type="submit" class="btn btn-danger" id="confirmArchiveBtn">
                        <i class="bi bi-archive"></i> Archive Staff
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const form = document.getElementById('archiveStaffForm');
    const leaveBlank = document.getElementById('actionLeaveBlank');
    const transfer = document.getElementById('actionTransfer');
    const wrap = document.getElementById('replacementStaffWrap');
    const select = document.getElementById('replacement_staff_id');

    function syncReplacementField() {
        const show = transfer && transfer.checked;
        wrap.style.display = show ? 'block' : 'none';
        if (select) select.required = show;
    }

    leaveBlank?.addEventListener('change', syncReplacementField);
    transfer?.addEventListener('change', syncReplacementField);
    syncReplacementField();

    form?.addEventListener('submit', function (e) {
        const action = form.querySelector('input[name="assignment_action"]:checked')?.value;
        let msg = 'Archive {{ addslashes($staff->full_name) }}?';
        if (action === 'transfer') {
            const name = select?.selectedOptions?.[0]?.text?.trim();
            msg += name && select.value ? ' All teaching assignments will move to ' + name + '.' : ' Select a replacement teacher first.';
            if (!select?.value) {
                e.preventDefault();
                alert('Please select a replacement teacher.');
                return;
            }
        } else if ({{ $assignmentSummary['has_assignments'] ? 'true' : 'false' }}) {
            msg += ' All teaching assignments will be left unassigned.';
        }
        if (!confirm(msg)) {
            e.preventDefault();
        }
    });
})();
</script>
@endpush
@endsection
