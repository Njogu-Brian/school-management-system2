@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <a href="{{ route('pos.public-links.index') }}" class="text-decoration-none">
            <i class="bi bi-arrow-left"></i> Back to Links
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="h4 mb-3">Create Public Shop Link</h1>

                    <form method="POST" action="{{ route('pos.public-links.store') }}">
                        @csrf

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Link Name (Optional)</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g., Grade 1 Shop Link">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Access Type <span class="text-danger">*</span></label>
                                <select name="access_type" class="form-select" required id="accessType">
                                    <option value="student" @selected(old('access_type') === 'student')>Student-Specific</option>
                                    <option value="class" @selected(old('access_type') === 'class')>Class-Wide</option>
                                    <option value="public" @selected(old('access_type') === 'public')>Public</option>
                                </select>
                            </div>
                            <div class="col-12" id="studentField" style="display: none;">
                                <label class="form-label">Student <span class="text-danger">*</span></label>
                                <select name="student_id" class="form-select">
                                    <option value="">Select Student</option>
                                    @foreach($students as $student)
                                        <option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>
                                            {{ $student->first_name }} {{ $student->last_name }} ({{ $student->admission_number }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12" id="classroomField" style="display: none;">
                                <label class="form-label">Classroom <span class="text-danger">*</span></label>
                                <select name="classroom_id" class="form-select">
                                    <option value="">Select Classroom</option>
                                    @foreach($classrooms as $classroom)
                                        <option value="{{ $classroom->id }}" @selected(old('classroom_id') == $classroom->id)>{{ $classroom->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="show_requirements_only" id="showRequirementsOnly" value="1" @checked(old('show_requirements_only'))>
                                    <label class="form-check-label" for="showRequirementsOnly">
                                        Show Requirements Only (Hide other products)
                                    </label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="allow_custom_items" id="allowCustomItems" value="1" @checked(old('allow_custom_items', true))>
                                    <label class="form-check-label" for="allowCustomItems">
                                        Allow Custom Items (Not just requirements)
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Expires At (Optional)</label>
                                <input type="date" name="expires_at" class="form-control" value="{{ old('expires_at') }}">
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1" @checked(old('is_active', true))>
                                    <label class="form-check-label" for="isActive">Active</label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('pos.public-links.index') }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary">Create Link</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('accessType').addEventListener('change', function() {
    const accessType = this.value;
    document.getElementById('studentField').style.display = accessType === 'student' ? 'block' : 'none';
    document.getElementById('classroomField').style.display = accessType === 'class' ? 'block' : 'none';
});
// Trigger on page load
document.getElementById('accessType').dispatchEvent(new Event('change'));
</script>
@endsection



