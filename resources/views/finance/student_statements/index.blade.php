@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'Student Statements',
        'icon' => 'bi bi-file-text',
        'subtitle' => 'View and export student fee statements',
        'actions' => ''
    ])

    <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
        <div class="finance-card-header d-flex align-items-center gap-2">
            <i class="bi bi-search"></i> <span>Search Student</span>
        </div>
        <div class="finance-card-body p-4">
            <form method="GET" action="{{ route('finance.student-statements.index') }}" class="row g-3" id="studentStatementForm">
                <div class="col-md-12">
                    <label class="finance-form-label">Student <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="hidden" id="selectedStudentId" name="student_id" value="{{ request('student_id') }}" required>
                        <input type="text" id="selectedStudentName" class="finance-form-control"
                               value="{{ request('student_id') ? (optional(\App\Models\Student::find(request('student_id')))->full_name . ' (' . optional(\App\Models\Student::find(request('student_id')))->admission_number . ')') : '' }}"
                               placeholder="Search by name or admission #"
                               readonly>
                        <button type="button" class="btn btn-finance btn-finance-primary"
                                data-bs-toggle="modal" data-bs-target="#studentSearchModal">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                    <small class="text-muted">Select a student to view their statement</small>
                </div>
                
                <div class="col-md-12">
                    <button type="button" id="viewStatementBtn" class="btn btn-finance btn-finance-primary" 
                            {{ request('student_id') ? '' : 'disabled' }}
                            onclick="viewStatement()">
                        <i class="bi bi-eye"></i> View Statement
                    </button>
                </div>
            </form>
            
            <script>
                function viewStatement() {
                    const studentId = document.getElementById('selectedStudentId').value;
                    if (studentId) {
                        window.location.href = '{{ route("finance.student-statements.show", ":id") }}'.replace(':id', studentId);
                    }
                }
            </script>
        </div>
    </div>
@include('partials.student_search_modal')
@endsection

