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
                    @include('partials.student_live_search', [
                        'hiddenInputId' => 'selectedStudentId',
                        'displayInputId' => 'studentLiveSearch',
                        'resultsId' => 'studentLiveResults',
                        'enableButtonId' => 'viewStatementBtn',
                        'initialLabel' => request('student_id') ? optional(\App\Models\Student::find(request('student_id')))->search_display : ''
                    ])
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
@endsection

