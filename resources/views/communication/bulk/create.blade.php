@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Send Bulk {{ ucfirst($type) }}</h1>
        <a href="{{ route('communication.bulk.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('communication.bulk.store') }}" method="POST">
                @csrf
                <input type="hidden" name="type" value="{{ $type }}">

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Target Audience <span class="text-danger">*</span></label>
                        <select name="target" id="target" class="form-select @error('target') is-invalid @enderror" required>
                            <option value="all_students" {{ old('target') == 'all_students' ? 'selected' : '' }}>All Students</option>
                            <option value="selected_students" {{ old('target') == 'selected_students' ? 'selected' : '' }}>Selected Students</option>
                            <option value="classroom" {{ old('target') == 'classroom' ? 'selected' : '' }}>By Classroom</option>
                        </select>
                        @error('target')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6" id="classroom-field" style="display: none;">
                        <label class="form-label">Classroom <span class="text-danger">*</span></label>
                        <select name="classroom_id" class="form-select">
                            <option value="">Select Classroom</option>
                            @foreach($classrooms as $classroom)
                                <option value="{{ $classroom->id }}" {{ old('classroom_id') == $classroom->id ? 'selected' : '' }}>
                                    {{ $classroom->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mb-3" id="students-field" style="display: none;">
                    <label class="form-label">Select Students <span class="text-danger">*</span></label>
                    <select name="student_ids[]" class="form-select" multiple style="min-height: 200px;">
                        @foreach($students as $student)
                            <option value="{{ $student->id }}" {{ in_array($student->id, old('student_ids', [])) ? 'selected' : '' }}>
                                {{ $student->first_name }} {{ $student->last_name }} - {{ $student->classroom->name ?? 'No Class' }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Hold Ctrl/Cmd to select multiple students</small>
                </div>

                @if($type === 'email')
                <div class="mb-3">
                    <label class="form-label">Subject <span class="text-danger">*</span></label>
                    <input type="text" name="subject" class="form-control @error('subject') is-invalid @enderror" 
                           value="{{ old('subject') }}" required>
                    @error('subject')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                @endif

                <div class="mb-3">
                    <label class="form-label">Template (Optional)</label>
                    <select name="template_id" class="form-select">
                        <option value="">No Template</option>
                        @foreach($templates as $template)
                            <option value="{{ $template->id }}" {{ old('template_id') == $template->id ? 'selected' : '' }}>
                                {{ $template->title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Message <span class="text-danger">*</span></label>
                    <textarea name="message" class="form-control @error('message') is-invalid @enderror" rows="8" required>{{ old('message') }}</textarea>
                    <small class="text-muted">You can use placeholders like {student_name}, {class_name}, etc.</small>
                    @error('message')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('communication.bulk.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Send {{ ucfirst($type) }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('target').addEventListener('change', function() {
        const classroomField = document.getElementById('classroom-field');
        const studentsField = document.getElementById('students-field');
        
        if (this.value === 'classroom') {
            classroomField.style.display = 'block';
            studentsField.style.display = 'none';
        } else if (this.value === 'selected_students') {
            classroomField.style.display = 'none';
            studentsField.style.display = 'block';
        } else {
            classroomField.style.display = 'none';
            studentsField.style.display = 'none';
        }
    });
    
    // Trigger on page load
    document.getElementById('target').dispatchEvent(new Event('change'));
</script>
@endsection

