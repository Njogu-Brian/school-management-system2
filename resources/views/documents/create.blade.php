@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Upload Document</h1>
        <a href="{{ route('documents.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" 
                               value="{{ old('title') }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <select name="category" class="form-select @error('category') is-invalid @enderror" required>
                            <option value="student" {{ old('category') == 'student' ? 'selected' : '' }}>Student</option>
                            <option value="staff" {{ old('category') == 'staff' ? 'selected' : '' }}>Staff</option>
                            <option value="academic" {{ old('category') == 'academic' ? 'selected' : '' }}>Academic</option>
                            <option value="financial" {{ old('category') == 'financial' ? 'selected' : '' }}>Financial</option>
                            <option value="administrative" {{ old('category') == 'administrative' ? 'selected' : '' }}>Administrative</option>
                            <option value="other" {{ old('category') == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('category')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Document Type <span class="text-danger">*</span></label>
                        <select name="document_type" class="form-select @error('document_type') is-invalid @enderror" required>
                            <option value="report" {{ old('document_type') == 'report' ? 'selected' : '' }}>Report</option>
                            <option value="certificate" {{ old('document_type') == 'certificate' ? 'selected' : '' }}>Certificate</option>
                            <option value="letter" {{ old('document_type') == 'letter' ? 'selected' : '' }}>Letter</option>
                            <option value="form" {{ old('document_type') == 'form' ? 'selected' : '' }}>Form</option>
                            <option value="policy" {{ old('document_type') == 'policy' ? 'selected' : '' }}>Policy</option>
                            <option value="other" {{ old('document_type') == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('document_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">File <span class="text-danger">*</span></label>
                        <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" 
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" required>
                        <small class="text-muted">Max size: 10MB</small>
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Attach To Type <span class="text-danger">*</span></label>
                        <select name="documentable_type" id="documentable_type" class="form-select" required>
                            <option value="">Select Type</option>
                            <option value="App\Models\Student" {{ old('documentable_type') == 'App\Models\Student' ? 'selected' : '' }}>Student</option>
                            <option value="App\Models\Staff" {{ old('documentable_type') == 'App\Models\Staff' ? 'selected' : '' }}>Staff</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Attach To <span class="text-danger">*</span></label>
                        <select name="documentable_id" id="documentable_id" class="form-select" required>
                            <option value="">Select...</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('documents.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Upload Document</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const students = @json($students->map(function($s) { return ['id' => $s->id, 'name' => $s->first_name . ' ' . $s->last_name]; }));
    const staff = @json($staff->map(function($s) { return ['id' => $s->id, 'name' => $s->first_name . ' ' . $s->last_name]; }));

    document.getElementById('documentable_type').addEventListener('change', function() {
        const select = document.getElementById('documentable_id');
        select.innerHTML = '<option value="">Select...</option>';
        
        const data = this.value === 'App\\Models\\Student' ? students : staff;
        data.forEach(item => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.name;
            select.appendChild(option);
        });
    });
</script>
@endsection

