<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Details Update</title>
    @php $hasVite = file_exists(public_path('build/manifest.json')); @endphp
    @if($hasVite)
        @vite(['resources/css/app.css','resources/js/app.js'])
    @elseif(file_exists(public_path('css/app.css')))
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @endif
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm form-card">
                <div class="card-header bg-primary text-white d-flex justify-content-between">
                    <div>
                        <h5 class="mb-0">Student Details Update</h5>
                        <small>Family ID: {{ $family->id }}</small>
                    </div>
                    <span class="badge bg-light text-primary">Secure Link</span>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    <form action="{{ route('family-update.submit', $link->token) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @foreach($students as $stu)
                            <h6 class="text-uppercase text-muted mb-3">Student Details</h6>
                            <div class="border rounded-3 p-3 mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="fw-semibold">{{ $stu->first_name }} {{ $stu->last_name }}</div>
                                    <span class="badge bg-light text-dark">Admission #{{ $stu->admission_number }}</span>
                                </div>
                                <input type="hidden" name="students[{{ $stu->id }}][id]" value="{{ $stu->id }}">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">First Name</label>
                                        <input type="text" name="students[{{ $stu->id }}][first_name]" class="form-control" value="{{ old('students.'.$stu->id.'.first_name', $stu->first_name) }}" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Middle Name</label>
                                        <input type="text" name="students[{{ $stu->id }}][middle_name]" class="form-control" value="{{ old('students.'.$stu->id.'.middle_name', $stu->middle_name) }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" name="students[{{ $stu->id }}][last_name]" class="form-control" value="{{ old('students.'.$stu->id.'.last_name', $stu->last_name) }}" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Gender</label>
                                        <select name="students[{{ $stu->id }}][gender]" class="form-select" required>
                                            <option value="Male" @selected(old('students.'.$stu->id.'.gender', $stu->gender)=='Male')>Male</option>
                                            <option value="Female" @selected(old('students.'.$stu->id.'.gender', $stu->gender)=='Female')>Female</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Date of Birth</label>
                                        <input type="date" name="students[{{ $stu->id }}][dob]" class="form-control" value="{{ old('students.'.$stu->id.'.dob', $stu->dob) }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Classroom (view only)</label>
                                        <input type="text" class="form-control" value="{{ $stu->classroom->name ?? '—' }}" disabled>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="students[{{ $stu->id }}][has_allergies]" value="1" id="has_allergies_{{ $stu->id }}" @checked(old('students.'.$stu->id.'.has_allergies', $stu->has_allergies))>
                                            <label class="form-check-label" for="has_allergies_{{ $stu->id }}">Has allergies?</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="students[{{ $stu->id }}][is_fully_immunized]" value="1" id="fully_immunized_{{ $stu->id }}" @checked(old('students.'.$stu->id.'.is_fully_immunized', $stu->is_fully_immunized))>
                                            <label class="form-check-label" for="fully_immunized_{{ $stu->id }}">Fully immunized</label>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Allergies Notes</label>
                                        <textarea name="students[{{ $stu->id }}][allergies_notes]" class="form-control" rows="2">{{ old('students.'.$stu->id.'.allergies_notes', $stu->allergies_notes) }}</textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Passport Photo</label>
                                        <input type="file" name="students[{{ $stu->id }}][passport_photo]" class="form-control" accept="image/*" capture="environment">
                                        @if($stu->photo_path)
                                            <small class="text-muted d-block mt-1"><a target="_blank" href="{{ Storage::url($stu->photo_path) }}">Current photo</a></small>
                                        @endif
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Birth Certificate / Notification</label>
                                        <input type="file" name="students[{{ $stu->id }}][birth_certificate]" class="form-control" accept=".pdf,.jpg,.jpeg,.png" capture="environment">
                                        @if($stu->birth_certificate_path)
                                            <small class="text-muted d-block mt-1"><a target="_blank" href="{{ Storage::url($stu->birth_certificate_path) }}">Current file</a></small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <h6 class="text-uppercase text-muted mb-3">Parent / Guardian</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Father Name</label>
                                <input type="text" name="father_name" class="form-control" value="{{ old('father_name', $family->students->first()->parent->father_name ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Father Phone</label>
                                <div class="input-group">
                                    <select name="father_phone_country_code" class="form-select flex-grow-0" style="max-width:140px">
@foreach($countryCodes as $code => $label)
    <option value="{{ $code }}" @selected(old('father_phone_country_code', $family->students->first()->parent->father_phone_country_code ?? '+254')==$code)>{{ $label }}</option>
@endforeach
                                    </select>
                                    <input type="text" name="father_phone" class="form-control" value="{{ old('father_phone', $family->students->first()->parent->father_phone ?? '') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Father WhatsApp</label>
                        <input type="text" name="father_whatsapp" class="form-control" value="{{ old('father_whatsapp', $family->students->first()->parent->father_whatsapp ?? '') }}" placeholder="Same code as phone">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Father Email</label>
                                <input type="email" name="father_email" class="form-control" value="{{ old('father_email', $family->students->first()->parent->father_email ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Father ID Document</label>
                                <input type="file" name="father_id_document" class="form-control" accept=".pdf,.jpg,.jpeg,.png" capture="environment">
                                @if(optional($family->students->first()->parent)->father_id_document)
                                    <small class="text-muted d-block mt-1"><a target="_blank" href="{{ Storage::url($family->students->first()->parent->father_id_document) }}">Current file</a></small>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Mother Name</label>
                                <input type="text" name="mother_name" class="form-control" value="{{ old('mother_name', $family->students->first()->parent->mother_name ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mother Phone</label>
                                <div class="input-group">
                                    <select name="mother_phone_country_code" class="form-select flex-grow-0" style="max-width:140px">
@foreach($countryCodes as $code => $label)
    <option value="{{ $code }}" @selected(old('mother_phone_country_code', $family->students->first()->parent->mother_phone_country_code ?? '+254')==$code)>{{ $label }}</option>
@endforeach
                                    </select>
                                    <input type="text" name="mother_phone" class="form-control" value="{{ old('mother_phone', $family->students->first()->parent->mother_phone ?? '') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mother WhatsApp</label>
                        <input type="text" name="mother_whatsapp" class="form-control" value="{{ old('mother_whatsapp', $family->students->first()->parent->mother_whatsapp ?? '') }}" placeholder="Same code as phone">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mother Email</label>
                                <input type="email" name="mother_email" class="form-control" value="{{ old('mother_email', $family->students->first()->parent->mother_email ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mother ID Document</label>
                                <input type="file" name="mother_id_document" class="form-control" accept=".pdf,.jpg,.jpeg,.png" capture="environment">
                                @if(optional($family->students->first()->parent)->mother_id_document)
                                    <small class="text-muted d-block mt-1"><a target="_blank" href="{{ Storage::url($family->students->first()->parent->mother_id_document) }}">Current file</a></small>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Guardian Name</label>
                                <input type="text" name="guardian_name" class="form-control" value="{{ old('guardian_name', $family->students->first()->parent->guardian_name ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Guardian Phone</label>
                                <div class="input-group">
                                    <select name="guardian_phone_country_code" class="form-select flex-grow-0" style="max-width:140px">
@foreach($countryCodes as $code => $label)
    <option value="{{ $code }}" @selected(old('guardian_phone_country_code', $family->students->first()->parent->guardian_phone_country_code ?? '+254')==$code)>{{ $label }}</option>
@endforeach
                                    </select>
                                    <input type="text" name="guardian_phone" class="form-control" value="{{ old('guardian_phone', $family->students->first()->parent->guardian_phone ?? '') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Guardian Relationship</label>
                                <input type="text" name="guardian_relationship" class="form-control" value="{{ old('guardian_relationship', $family->students->first()->parent->guardian_relationship ?? '') }}">
                            </div>
                    <div class="col-md-6">
                        <label class="form-label">Marital Status</label>
                        <select name="marital_status" class="form-select">
                            <option value="">Select</option>
                            <option value="married" @selected(old('marital_status', $family->students->first()->parent->marital_status ?? '')=='married')>Married</option>
                            <option value="single_parent" @selected(old('marital_status', $family->students->first()->parent->marital_status ?? '')=='single_parent')>Single Parent</option>
                            <option value="co_parenting" @selected(old('marital_status', $family->students->first()->parent->marital_status ?? '')=='co_parenting')>Co-parenting</option>
                        </select>
                    </div>
                        </div>

                        <h6 class="text-uppercase text-muted mb-3">Emergency & Medical</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Emergency Contact Name</label>
                                <small class="text-muted d-block mb-1">Person we call if parents/guardians cannot be reached.</small>
                                <input type="text" name="emergency_contact_name" class="form-control" value="{{ old('emergency_contact_name', $family->students->first()->emergency_contact_name ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Emergency Contact Phone</label>
                        <input type="text" name="emergency_contact_phone" class="form-control" value="{{ old('emergency_contact_phone', $family->students->first()->emergency_contact_phone ?? '') }}" placeholder="+2547XXXXXXXX">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Preferred Hospital / Medical Facility</label>
                                <input type="text" name="preferred_hospital" class="form-control" value="{{ old('preferred_hospital', $family->students->first()->preferred_hospital ?? '') }}">
                            </div>
                        </div>

                        <h6 class="text-uppercase text-muted mb-3">Residential</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Residential Area</label>
                                <input type="text" name="residential_area" class="form-control" value="{{ old('residential_area', $family->students->first()->residential_area ?? '') }}">
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">Save Updates</button>
                            <div class="text-muted small text-center">You can revisit this link anytime to make further updates.</div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>

