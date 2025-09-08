<div class="row">
    <div class="col-md-4 mb-3">
        <label>First Name *</label>
        <input type="text" name="first_name" class="form-control"
               value="{{ old('first_name',$staff->first_name ?? '') }}" required>
    </div>
    <div class="col-md-4 mb-3">
        <label>Middle Name</label>
        <input type="text" name="middle_name" class="form-control"
               value="{{ old('middle_name',$staff->middle_name ?? '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label>Last Name *</label>
        <input type="text" name="last_name" class="form-control"
               value="{{ old('last_name',$staff->last_name ?? '') }}" required>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label>Email *</label>
        <input type="email" name="email" class="form-control"
               value="{{ old('email',$staff->email ?? '') }}" required>
    </div>
    <div class="col-md-6 mb-3">
        <label>Phone Number *</label>
        <input type="text" name="phone_number" class="form-control"
               value="{{ old('phone_number',$staff->phone_number ?? '') }}" placeholder="+2547XXXXXXXX" required>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label>ID Number *</label>
        <input type="text" name="id_number" class="form-control"
               value="{{ old('id_number',$staff->id_number ?? '') }}" required>
    </div>
    <div class="col-md-6 mb-3">
        <label>Date of Birth</label>
        <input type="date" name="date_of_birth" class="form-control"
               value="{{ old('date_of_birth',$staff->date_of_birth ?? '') }}">
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label>Gender</label>
        <select name="gender" class="form-control">
            <option value="">-- Select --</option>
            <option value="male" {{ old('gender',$staff->gender ?? '')=='male'?'selected':'' }}>Male</option>
            <option value="female" {{ old('gender',$staff->gender ?? '')=='female'?'selected':'' }}>Female</option>
            <option value="other" {{ old('gender',$staff->gender ?? '')=='other'?'selected':'' }}>Other</option>
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <label>Marital Status</label>
        <input type="text" name="marital_status" class="form-control"
               value="{{ old('marital_status',$staff->marital_status ?? '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label>Address</label>
        <input type="text" name="address" class="form-control"
               value="{{ old('address',$staff->address ?? '') }}">
    </div>
</div>

<h5 class="mt-4">Employment Details</h5>
<div class="row">
    <div class="col-md-4 mb-3">
        <label>Role</label>
        <select name="role_id" class="form-control">
            <option value="">-- Select --</option>
            @foreach($roles as $role)
                <option value="{{ $role->id }}" {{ old('role_id',$staff->role_id ?? '')==$role->id?'selected':'' }}>
                    {{ $role->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <label>Department</label>
        <select name="department_id" class="form-control">
            <option value="">-- Select --</option>
            @foreach($departments as $dept)
                <option value="{{ $dept->id }}" {{ old('department_id',$staff->department_id ?? '')==$dept->id?'selected':'' }}>
                    {{ $dept->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <label>Job Title</label>
        <select name="job_title_id" class="form-control">
            <option value="">-- Select --</option>
            @foreach($jobTitles as $job)
                <option value="{{ $job->id }}" {{ old('job_title_id',$staff->job_title_id ?? '')==$job->id?'selected':'' }}>
                    {{ $job->name }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label>KRA PIN</label>
        <input type="text" name="kra_pin" class="form-control"
               value="{{ old('kra_pin',$staff->kra_pin ?? '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label>NSSF</label>
        <input type="text" name="nssf" class="form-control"
               value="{{ old('nssf',$staff->nssf ?? '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label>NHIF</label>
        <input type="text" name="nhif" class="form-control"
               value="{{ old('nhif',$staff->nhif ?? '') }}">
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label>Bank Name</label>
        <input type="text" name="bank_name" class="form-control"
               value="{{ old('bank_name',$staff->bank_name ?? '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label>Bank Branch</label>
        <input type="text" name="bank_branch" class="form-control"
               value="{{ old('bank_branch',$staff->bank_branch ?? '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label>Bank Account</label>
        <input type="text" name="bank_account" class="form-control"
               value="{{ old('bank_account',$staff->bank_account ?? '') }}">
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label>Supervisor</label>
        <select name="supervisor_id" class="form-control">
            <option value="">-- None --</option>
            @foreach($supervisors as $sup)
                <option value="{{ $sup->id }}" {{ old('supervisor_id',$staff->supervisor_id ?? '')==$sup->id?'selected':'' }}>
                    {{ $sup->first_name }} {{ $sup->last_name }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<h5 class="mt-4">Emergency Contact</h5>
<div class="row">
    <div class="col-md-6 mb-3">
        <label>Contact Name</label>
        <input type="text" name="emergency_contact_name" class="form-control"
               value="{{ old('emergency_contact_name',$staff->emergency_contact_name ?? '') }}">
    </div>
    <div class="col-md-6 mb-3">
        <label>Contact Phone</label>
        <input type="text" name="emergency_contact_phone" class="form-control"
               value="{{ old('emergency_contact_phone',$staff->emergency_contact_phone ?? '') }}">
    </div>
</div>

<div class="mb-3">
    <label>Photo</label>
    <input type="file" name="photo" class="form-control">
    @if(!empty($staff->photo))
        <img src="{{ asset('storage/'.$staff->photo) }}" width="80" class="mt-2 rounded">
    @endif
</div>

<h5 class="mt-4">Custom Fields</h5>
@foreach($customFields as $field)
    <div class="mb-3">
        <label>{{ $field->label }}</label>
        <input type="{{ $field->field_type }}" 
               name="custom_fields[{{ $field->field_key }}]" 
               value="{{ old('custom_fields.'.$field->field_key, $staff->meta->where('field_key',$field->field_key)->first()->field_value ?? '') }}"
               class="form-control" {{ $field->required ? 'required' : '' }}>
    </div>
@endforeach
