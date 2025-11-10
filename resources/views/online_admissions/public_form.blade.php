<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Admission Application - {{ config('app.name', 'School Management System') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        .form-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 2rem;
            max-width: 900px;
            margin: 0 auto;
        }
        .form-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #667eea;
        }
        .form-header h1 {
            color: #667eea;
            font-weight: 600;
        }
        .section-title {
            color: #667eea;
            font-weight: 600;
            margin-top: 2rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e9ecef;
        }
        .required-field::after {
            content: " *";
            color: red;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <div class="form-header">
                <h1><i class="bi bi-mortarboard"></i> Online Admission Application</h1>
                <p class="text-muted">Please fill in all required fields marked with <span class="text-danger">*</span></p>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <h6><i class="bi bi-exclamation-triangle"></i> Please correct the following errors:</h6>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('online-admissions.public-submit') }}" method="POST" enctype="multipart/form-data">
                @csrf

                {{-- Student Information --}}
                <h5 class="section-title"><i class="bi bi-person"></i> Student Information</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label required-field">First Name</label>
                        <input type="text" name="first_name" class="form-control" value="{{ old('first_name') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Middle Name</label>
                        <input type="text" name="middle_name" class="form-control" value="{{ old('middle_name') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required-field">Last Name</label>
                        <input type="text" name="last_name" class="form-control" value="{{ old('last_name') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required-field">Date of Birth</label>
                        <input type="date" name="dob" class="form-control" value="{{ old('dob') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required-field">Gender</label>
                        <select name="gender" class="form-select" required>
                            <option value="">Select Gender</option>
                            <option value="Male" @selected(old('gender')=='Male')>Male</option>
                            <option value="Female" @selected(old('gender')=='Female')>Female</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">NEMIS Number</label>
                        <input type="text" name="nemis_number" class="form-control" value="{{ old('nemis_number') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">KNEC Assessment Number</label>
                        <input type="text" name="knec_assessment_number" class="form-control" value="{{ old('knec_assessment_number') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Preferred Classroom</label>
                        <select name="classroom_id" class="form-select">
                            <option value="">Select Classroom</option>
                            @foreach($classrooms as $classroom)
                                <option value="{{ $classroom->id }}" @selected(old('classroom_id')==$classroom->id)>{{ $classroom->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Preferred Stream</label>
                        <select name="stream_id" class="form-select">
                            <option value="">Select Stream</option>
                            @foreach($streams as $stream)
                                <option value="{{ $stream->id }}" @selected(old('stream_id')==$stream->id)>{{ $stream->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Father Information --}}
                <h5 class="section-title"><i class="bi bi-person-badge"></i> Father Information</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Father's Name</label>
                        <input type="text" name="father_name" class="form-control" value="{{ old('father_name') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Father's Phone</label>
                        <input type="text" name="father_phone" class="form-control" value="{{ old('father_phone') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Father's Email</label>
                        <input type="email" name="father_email" class="form-control" value="{{ old('father_email') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Father's ID Number</label>
                        <input type="text" name="father_id_number" class="form-control" value="{{ old('father_id_number') }}">
                    </div>
                </div>

                {{-- Mother Information --}}
                <h5 class="section-title"><i class="bi bi-person-badge"></i> Mother Information</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Mother's Name</label>
                        <input type="text" name="mother_name" class="form-control" value="{{ old('mother_name') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mother's Phone</label>
                        <input type="text" name="mother_phone" class="form-control" value="{{ old('mother_phone') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mother's Email</label>
                        <input type="email" name="mother_email" class="form-control" value="{{ old('mother_email') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mother's ID Number</label>
                        <input type="text" name="mother_id_number" class="form-control" value="{{ old('mother_id_number') }}">
                    </div>
                </div>

                {{-- Guardian Information --}}
                <h5 class="section-title"><i class="bi bi-shield-check"></i> Guardian Information</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Guardian's Name</label>
                        <input type="text" name="guardian_name" class="form-control" value="{{ old('guardian_name') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Guardian's Phone</label>
                        <input type="text" name="guardian_phone" class="form-control" value="{{ old('guardian_phone') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Guardian's Email</label>
                        <input type="email" name="guardian_email" class="form-control" value="{{ old('guardian_email') }}">
                    </div>
                </div>

                {{-- Documents --}}
                <h5 class="section-title"><i class="bi bi-file-earmark"></i> Documents</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Passport Photo</label>
                        <input type="file" name="passport_photo" class="form-control" accept="image/*">
                        <small class="text-muted">Max 2MB (JPG, PNG)</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Birth Certificate</label>
                        <input type="file" name="birth_certificate" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted">Max 5MB (PDF, JPG, PNG)</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Parent ID Card</label>
                        <input type="file" name="parent_id_card" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted">Max 5MB (PDF, JPG, PNG)</small>
                    </div>
                </div>

                <div class="alert alert-info mt-4">
                    <i class="bi bi-info-circle"></i> <strong>Note:</strong> After submission, your application will be reviewed. You will be contacted via phone or email regarding the status of your application.
                </div>

                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-send"></i> Submit Application
                    </button>
                    <a href="{{ route('online-admissions.public-form') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Reset Form
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

