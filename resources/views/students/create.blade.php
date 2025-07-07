@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Student Admission</h1>

@if(can_access('students', 'manage_students', 'add'))
    <form action="{{ route('students.store') }}" method="POST" enctype="multipart/form-data">
@endif
        @csrf

        {{-- Student Information --}}
        <h4>Student Information</h4>
        <div class="mb-3">
            <label>First Name</label>
            <input type="text" name="first_name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Middle Name (Optional)</label>
            <input type="text" name="middle_name" class="form-control">
        </div>

        <div class="mb-3">
            <label>Last Name</label>
            <input type="text" name="last_name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Gender</label>
            <select name="gender" class="form-control" required>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
            </select>
        </div>
        <div class="mb-3">
            <label>Date of Birth</label>
            <input type="date" name="dob" class="form-control">
        </div>

        {{-- Class and Category --}}
        <div class="mb-3">
            <label>Class</label>
            <select id="classroom" name="classroom_id" class="form-control" required>
                <option value="">Select Class</option>
                @foreach ($classrooms as $classroom)
                    <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Stream</label>
            <select id="stream" name="stream_id" class="form-control">
                <option value="">Select Stream (Optional)</option>
            </select>
        </div>

        <div class="mb-3">
            <label>Student Category</label>
            <select name="category_id" class="form-control">
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- NEMIS and KNEC --}}
        <div class="mb-3">
            <label>NEMIS Number</label>
            <input type="text" name="nemis_number" class="form-control">
        </div>
        <div class="mb-3">
            <label>KNEC Assessment Number</label>
            <input type="text" name="knec_assessment_number" class="form-control">
        </div>

        {{-- Sibling Management --}}
        <h4>Sibling Management</h4>
        <label>Link to Sibling (Optional)</label>
        <select name="sibling_id" class="form-control" id="sibling-select">
            <option value="">Select Sibling</option>
            @foreach ($students as $student)
                <option value="{{ $student->id }}">{{ $student->first_name }} {{ $student->last_name }} (Adm: {{ $student->admission_number }})</option>
            @endforeach
        </select>
        <p id="parent-info" class="text-muted">Parent details will be populated if a sibling is selected. You can still edit the details below if necessary.</p>

        {{-- Parent Information --}}
        <h4>Parent/Guardian Information</h4>

        {{-- Father's Details --}}
        <h5>Father's Information</h5>
        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="father_name" class="form-control parent-field" required>
        </div>
        <div class="mb-3">
            <label>Phone</label>
            <input type="text" name="father_phone" class="form-control parent-field" required>
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="father_email" class="form-control parent-field">
        </div>
        <div class="mb-3">
            <label>ID Number</label>
            <input type="text" name="father_id_number" class="form-control parent-field">
        </div>

        {{-- Mother's Details --}}
        <h5>Mother's Information</h5>
        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="mother_name" class="form-control parent-field">
        </div>
        <div class="mb-3">
            <label>Phone</label>
            <input type="text" name="mother_phone" class="form-control parent-field">
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="mother_email" class="form-control parent-field">
        </div>
        <div class="mb-3">
            <label>ID Number</label>
            <input type="text" name="mother_id_number" class="form-control parent-field">
        </div>

        {{-- Guardian Details --}}
        <h5>Guardian (Optional)</h5>
        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="guardian_name" class="form-control parent-field">
        </div>
        <div class="mb-3">
            <label>Phone</label>
            <input type="text" name="guardian_phone" class="form-control parent-field">
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="guardian_email" class="form-control parent-field">
        </div>
        <div class="mb-3">
            <label>Relationship</label>
            <input type="text" name="guardian_relationship" class="form-control parent-field">
        </div>

        {{-- Document Uploads --}}
        <h4>Document Uploads</h4>
        <div class="mb-3">
            <label>Passport Photo</label>
            <input type="file" name="passport_photo" class="form-control">
        </div>
        <div class="mb-3">
            <label>Birth Certificate</label>
            <input type="file" name="birth_certificate" class="form-control">
        </div>
        <div class="mb-3">
            <label>Parent's ID (PDF or Image)</label>
            <input type="file" name="parent_id_card" class="form-control">
        </div>

        {{-- Submit --}}
        <button type="submit" class="btn btn-success">Submit Admission</button>
    </form>
</div>

<script>
document.getElementById('sibling-select').addEventListener('change', function() {
    const siblingId = this.value;
    if (siblingId) {
        fetch(`/api/siblings/${siblingId}`)
            .then(response => response.json())
            .then(data => {
                document.querySelector("input[name='father_name']").value = data.father_name;
                document.querySelector("input[name='father_phone']").value = data.father_phone;
                document.querySelector("input[name='mother_name']").value = data.mother_name;
                document.querySelector("input[name='mother_phone']").value = data.mother_phone;
                document.querySelector("input[name='guardian_name']").value = data.guardian_name;
                document.querySelector("input[name='guardian_phone']").value = data.guardian_phone;
            })
            .catch(error => {
                console.error("Error fetching sibling data:", error);
            });
    }
});
document.getElementById('classroom').addEventListener('change', function() {
    const classroomId = this.value;
    const streamSelect = document.getElementById('stream');

    // Clear previous options
    streamSelect.innerHTML = '<option value="">Select Stream (Optional)</option>';

    if (classroomId) {
        fetch('/get-streams', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name=_token]').value
            },
            body: JSON.stringify({ classroom_id: classroomId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.length > 0) {
                data.forEach(stream => {
                    const option = document.createElement('option');
                    option.value = stream.id;
                    option.textContent = stream.name;
                    streamSelect.appendChild(option);
                });
            } else {
                // Show no streams available and allow form submission without it
                const option = document.createElement('option');
                option.textContent = 'No Active Streams';
                option.disabled = true;
                streamSelect.appendChild(option);
            }
        })
        .catch(error => console.error('Error fetching streams:', error));
    }
});

</script>
@endsection
