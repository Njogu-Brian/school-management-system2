<div class="tab-pane fade" id="ids" role="tabpanel">
    <form action="{{ route('settings.ids.save') }}" method="POST">
        @csrf
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="staff_id_prefix" class="form-label">Staff ID Prefix</label>
                <input type="text" name="staff_id_prefix" id="staff_id_prefix" class="form-control"
                       value="{{ system_setting('staff_id_prefix') }}" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="staff_id_start" class="form-label">Staff ID Start</label>
                <input type="number" name="staff_id_start" id="staff_id_start" class="form-control"
                       value="{{ system_setting('staff_id_start') }}" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="student_id_prefix" class="form-label">Student ID Prefix</label>
                <input type="text" name="student_id_prefix" id="student_id_prefix" class="form-control"
                       value="{{ system_setting('student_id_prefix') }}" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="student_id_start" class="form-label">Student ID Start</label>
                <input type="number" name="student_id_start" id="student_id_start" class="form-control"
                       value="{{ system_setting('student_id_start') }}" required>
            </div>
        </div>
        <button type="submit" class="btn btn-success">Save ID Settings</button>
    </form>
</div>
