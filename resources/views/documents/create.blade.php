@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Documents / Upload</div>
                <h1>Upload Document</h1>
                <p>Add a new document and attach it to a student or staff member.</p>
            </div>
            <a href="{{ route('documents.index') }}" class="btn btn-ghost-strong">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>

        <div class="settings-card">
            <div class="card-body">
                <form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data" class="row g-4">
                    @csrf

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title') }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                        <select name="category" class="form-select @error('category') is-invalid @enderror" required id="categorySelect">
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" @selected(old('category') == $cat)>{{ ucwords(str_replace('_',' ', $cat)) }}</option>
                            @endforeach
                            <option value="custom" @selected(old('category')=='custom')>Custom...</option>
                        </select>
                        <input type="text" name="custom_category" id="customCategory" class="form-control mt-2 d-none" placeholder="Enter custom category" value="{{ old('custom_category') }}">
                        @error('category')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @error('custom_category')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Document Type <span class="text-danger">*</span></label>
                        <select name="document_type" class="form-select @error('document_type') is-invalid @enderror" required id="docTypeSelect">
                            @foreach($types as $type)
                                <option value="{{ $type }}" @selected(old('document_type') == $type)>{{ ucwords(str_replace('_',' ', $type)) }}</option>
                            @endforeach
                            <option value="custom" @selected(old('document_type')=='custom')>Custom...</option>
                        </select>
                        <input type="text" name="custom_document_type" id="customDocType" class="form-control mt-2 d-none" placeholder="Enter custom type" value="{{ old('custom_document_type') }}">
                        @error('document_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @error('custom_document_type')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">File <span class="text-danger">*</span></label>
                        <input type="file" name="file" class="form-control @error('file') is-invalid @enderror"
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" required>
                        <small class="text-muted">Max size: 10MB</small>
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Attach To Type <span class="text-danger">*</span></label>
                        <select name="documentable_type" id="documentable_type" class="form-select" required>
                            <option value="">Select Type</option>
                            <option value="App\Models\Student" {{ old('documentable_type') == 'App\Models\Student' ? 'selected' : '' }}>Student</option>
                            <option value="App\Models\Staff" {{ old('documentable_type') == 'App\Models\Staff' ? 'selected' : '' }}>Staff</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Attach To <span class="text-danger">*</span></label>
                        <select name="documentable_id" id="documentable_id" class="form-select" required>
                            <option value="">Select...</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2">
                        <a href="{{ route('documents.index') }}" class="btn btn-ghost-strong">Cancel</a>
                        <button type="submit" class="btn btn-settings-primary">Upload Document</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const students = @json($students->map(fn($s) => ['id' => $s->id, 'name' => $s->first_name . ' ' . $s->last_name]));
    const staff = @json($staff->map(fn($s) => ['id' => $s->id, 'name' => $s->first_name . ' ' . $s->last_name]));

    document.addEventListener('DOMContentLoaded', () => {
        const typeSelect = document.getElementById('documentable_type');
        const attachSelect = document.getElementById('documentable_id');

        const populate = (data) => {
            attachSelect.innerHTML = '<option value="">Select...</option>';
            data.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.name;
                attachSelect.appendChild(option);
            });
        };

        typeSelect.addEventListener('change', function() {
            const data = this.value === 'App\\Models\\Student' ? students : staff;
            populate(data);
        });

        // Repopulate if old selection existed
        const initialType = typeSelect.value;
        if (initialType) {
            populate(initialType === 'App\\Models\\Student' ? students : staff);
            const oldId = "{{ old('documentable_id') }}";
            if (oldId) {
                attachSelect.value = oldId;
            }
        }

        // Custom category/type toggles
        const catSelect = document.getElementById('categorySelect');
        const customCategory = document.getElementById('customCategory');
        const docTypeSelect = document.getElementById('docTypeSelect');
        const customDocType = document.getElementById('customDocType');

        const toggleCustom = (selectEl, inputEl) => {
            if (selectEl.value === 'custom') {
                inputEl.classList.remove('d-none');
                inputEl.required = true;
            } else {
                inputEl.classList.add('d-none');
                inputEl.required = false;
                inputEl.value = '';
            }
        };

        toggleCustom(catSelect, customCategory);
        toggleCustom(docTypeSelect, customDocType);

        catSelect.addEventListener('change', () => toggleCustom(catSelect, customCategory));
        docTypeSelect.addEventListener('change', () => toggleCustom(docTypeSelect, customDocType));
    });
</script>
@endsection

