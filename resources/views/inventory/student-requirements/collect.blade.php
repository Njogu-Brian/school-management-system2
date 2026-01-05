@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
    <style>
        .quantity-display {
            font-size: 0.875rem;
            color: #6c757d;
        }
        .form-section {
            margin-bottom: 2rem;
        }
        #requirementsForm {
            display: none;
        }
        #requirementsForm.show {
            display: block;
        }
    </style>
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Inventory / Student Requirements / Collect</div>
                <h1>Collect / Verify Items</h1>
                <p>Select class, stream, and student to record collected requirements.</p>
            </div>
            <a href="{{ route('inventory.student-requirements.index') }}" class="btn btn-ghost-strong">
                <i class="bi bi-arrow-left"></i> Requirements Tracker
            </a>
        </div>

        @include('partials.alerts')

        <!-- Class and Stream Selection -->
        <div class="settings-card mb-3">
            <div class="card-body">
                <h5 class="mb-3">Select Class and Stream</h5>
                <form method="GET" class="row g-3 align-items-end" id="filterForm">
                    <div class="col-md-4">
                        <label class="form-label">Class</label>
                        <select name="classroom_id" id="classroomSelect" class="form-select" required>
                            <option value="">Select a class</option>
                            @foreach($classrooms as $classroom)
                                <option value="{{ $classroom->id }}" @selected($selectedClassroomId == $classroom->id)>
                                    {{ $classroom->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Stream</label>
                        <select name="stream_id" id="streamSelect" class="form-select">
                            <option value="">All streams</option>
                            @foreach($streams as $stream)
                                <option value="{{ $stream->id }}" @selected($selectedStreamId == $stream->id)>
                                    {{ $stream->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-settings-primary">
                            <i class="bi bi-search"></i> Load
                        </button>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">Academic Year / Term</label>
                        <div>
                            <span class="input-chip">{{ $currentYear->year ?? 'Not set' }} / {{ $currentTerm->name ?? 'Not set' }}</span>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if($selectedClassroomId)
            @if($templates->isEmpty())
                <div class="alert alert-info">
                    No requirements have been published for this class yet. Ask an administrator to set them up first.
                </div>
            @else
                <!-- Student Selection and Requirements Form -->
                <div class="settings-card">
                    <div class="card-body">
                        <form method="POST" action="{{ route('inventory.student-requirements.collect.store') }}" id="requirementsForm" class="row g-3">
                            @csrf
                            
                            <!-- Student Selection -->
                            <div class="col-12 form-section">
                                <h5 class="mb-3">Select Student</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Student</label>
                                        <select name="student_id" id="studentSelect" class="form-select" required>
                                            <option value="">Select a student</option>
                                            @foreach($students as $student)
                                                <option value="{{ $student->id }}">
                                                    {{ $student->getNameAttribute() }}
                                                    @if($student->stream)
                                                        ({{ $student->stream->name }})
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Requirements Table -->
                            <div class="col-12 form-section">
                                <h5 class="mb-3">Requirements Collection</h5>
                                <div class="table-responsive">
                                    <table class="table table-modern align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 30%;">Requirement Name</th>
                                                <th style="width: 20%;">Recommended Brand</th>
                                                <th style="width: 15%;">Expected Quantity</th>
                                                <th style="width: 15%;">Quantity Brought</th>
                                                <th style="width: 20%;">Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($templates as $index => $template)
                                                <tr data-template-id="{{ $template->id }}">
                                                    <td>
                                                        <div class="fw-semibold">{{ $template->requirementType->name }}</div>
                                                        @if($template->leave_with_teacher)
                                                            <div><span class="badge bg-info">Keep at school</span></div>
                                                        @endif
                                                        @if($template->is_verification_only)
                                                            <div><span class="badge bg-warning">Verification only</span></div>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div>{{ $template->brand ?? 'Any brand' }}</div>
                                                        @if($template->notes)
                                                            <small class="text-muted">{{ Str::limit($template->notes, 50) }}</small>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div class="quantity-display">
                                                            {{ number_format($template->quantity_per_student, 0) }}
                                                            @if($template->quantity_per_student == 1)
                                                                {{ $template->unit }}
                                                            @else
                                                                {{ Str::plural($template->unit) }}
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <input type="hidden" name="requirements[{{ $index }}][template_id]" value="{{ $template->id }}">
                                                        <input 
                                                            type="number" 
                                                            step="0.01" 
                                                            min="0" 
                                                            class="form-control quantity-input" 
                                                            name="requirements[{{ $index }}][quantity_collected]" 
                                                            data-template-id="{{ $template->id }}"
                                                            value="0"
                                                            required>
                                                    </td>
                                                    <td>
                                                        <textarea 
                                                            name="requirements[{{ $index }}][notes]" 
                                                            rows="2" 
                                                            class="form-control notes-input" 
                                                            placeholder="Optional notes or comments"
                                                            data-template-id="{{ $template->id }}"></textarea>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="col-12 d-flex justify-content-end mt-3">
                                <button type="submit" class="btn btn-settings-primary">
                                    <i class="bi bi-check2-circle"></i> Submit Collection & Notify Parent
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        @else
            <div class="alert alert-info">
                Please select a class and click "Load" to begin collecting requirements.
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const classroomSelect = document.getElementById('classroomSelect');
    const streamSelect = document.getElementById('streamSelect');
    const studentSelect = document.getElementById('studentSelect');
    const requirementsForm = document.getElementById('requirementsForm');
    const filterForm = document.getElementById('filterForm');

    // Load streams when classroom changes
    classroomSelect.addEventListener('change', function() {
        const classroomId = this.value;
        streamSelect.innerHTML = '<option value="">All streams</option>';
        studentSelect.innerHTML = '<option value="">Select a student</option>';
        requirementsForm.classList.remove('show');
        
        if (classroomId) {
            fetch(`{{ url('/inventory/student-requirements/load-streams') }}?classroom_id=${classroomId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.streams) {
                        data.streams.forEach(stream => {
                            const option = document.createElement('option');
                            option.value = stream.id;
                            option.textContent = stream.name;
                            streamSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => console.error('Error loading streams:', error));
        }
    });

    // Load students and requirements when student is selected
    studentSelect.addEventListener('change', function() {
        const studentId = this.value;
        
        if (studentId) {
            // Show form
            requirementsForm.classList.add('show');
            
            // Load existing requirement data
            fetch(`{{ url('/inventory/student-requirements/load-student-requirements') }}?student_id=${studentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.requirements) {
                        // Populate form with existing data
                        Object.keys(data.requirements).forEach(templateId => {
                            const req = data.requirements[templateId];
                            const quantityInput = document.querySelector(`input[data-template-id="${templateId}"].quantity-input`);
                            const notesInput = document.querySelector(`textarea[data-template-id="${templateId}"].notes-input`);
                            
                            if (quantityInput) {
                                quantityInput.value = req.quantity_collected || 0;
                            }
                            
                            if (notesInput && req.notes) {
                                notesInput.value = req.notes;
                            }
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading student requirements:', error);
                    // Still show the form even if loading fails
                });
        } else {
            requirementsForm.classList.remove('show');
        }
    });

    // Load students when stream changes
    streamSelect.addEventListener('change', function() {
        const classroomId = classroomSelect.value;
        const streamId = this.value;
        studentSelect.innerHTML = '<option value="">Select a student</option>';
        requirementsForm.classList.remove('show');
        
        if (classroomId) {
            let url = `{{ url('/inventory/student-requirements/load-students') }}?classroom_id=${classroomId}`;
            if (streamId) {
                url += `&stream_id=${streamId}`;
            }
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.students) {
                        data.students.forEach(student => {
                            const option = document.createElement('option');
                            option.value = student.id;
                            option.textContent = `${student.name}${student.stream ? ' (' + student.stream + ')' : ''}`;
                            studentSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => console.error('Error loading students:', error));
        }
    });

    // Reload students when filter form is submitted
    filterForm.addEventListener('submit', function(e) {
        // The form will submit normally to reload the page with filters
    });
    
    // If page loads with selected classroom, load students
    @if($selectedClassroomId)
        const classroomId = '{{ $selectedClassroomId }}';
        const streamId = '{{ $selectedStreamId ?? '' }}';
        
        if (classroomId) {
            let url = `{{ url('/inventory/student-requirements/load-students') }}?classroom_id=${classroomId}`;
            if (streamId) {
                url += `&stream_id=${streamId}`;
            }
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.students) {
                        const studentSelect = document.getElementById('studentSelect');
                        data.students.forEach(student => {
                            const option = document.createElement('option');
                            option.value = student.id;
                            option.textContent = `${student.name}${student.stream ? ' (' + student.stream + ')' : ''}`;
                            studentSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => console.error('Error loading students:', error));
        }
    @endif
});
</script>
@endpush
