{{-- Fee balance only + Exclude students + Exclude staff (shared by Email, SMS, WhatsApp, Print Notes) --}}
<div class="col-12 mb-2">
    <div class="d-flex flex-wrap align-items-center gap-3">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="fee_balance_only" value="1" id="feeBalanceOnly" {{ old('fee_balance_only') ? 'checked' : '' }}>
            <label class="form-check-label" for="feeBalanceOnly">
                <strong>Only recipients with fee balance</strong>
                <span class="text-muted small d-block">Restrict to parents/students who have an outstanding invoice balance.</span>
            </label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="exclude_staff" value="1" id="excludeStaff" {{ old('exclude_staff') ? 'checked' : '' }}>
            <label class="form-check-label" for="excludeStaff">
                <strong>Exclude staff (children in staff category)</strong>
                <span class="text-muted small d-block">Do not send to parents of students in the Staff student category.</span>
            </label>
        </div>
        <div class="ms-md-auto">
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#excludeStudentSelectorModal">
                <i class="bi bi-person-x"></i> Exclude students
            </button>
            <input type="hidden" name="exclude_student_ids" id="excludeStudentIds" value="{{ old('exclude_student_ids') }}">
            @php $excludeIds = old('exclude_student_ids') ? array_filter(explode(',', old('exclude_student_ids'))) : []; @endphp
            <span id="excludeStudentsDisplay" class="ms-2 {{ count($excludeIds) ? '' : 'd-none' }}">
                <span class="badge bg-secondary" id="excludeStudentsBadge">{{ count($excludeIds) }}</span> excluded
            </span>
        </div>
    </div>
</div>
