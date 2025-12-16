@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @include('finance.partials.header', [
        'title' => 'Student Statements',
        'icon' => 'bi bi-file-text',
        'subtitle' => 'View and export student fee statements',
        'actions' => ''
    ])

    <div class="finance-card finance-animate">
        <div class="finance-card-header">
            <i class="bi bi-search me-2"></i> Search Student
        </div>
        <div class="finance-card-body">
            <form method="GET" action="{{ route('finance.student-statements.index') }}" class="row g-3">
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
                
                @if(request('student_id'))
                    <div class="col-md-12">
                        <a href="{{ route('finance.student-statements.show', request('student_id')) }}" class="btn btn-finance btn-finance-primary">
                            <i class="bi bi-eye"></i> View Statement
                        </a>
                    </div>
                @else
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-finance btn-finance-primary" disabled>
                            <i class="bi bi-search"></i> Please select a student first
                        </button>
                    </div>
                @endif
            </form>
        </div>
    </div>
</div>

@include('partials.student_search_modal')
@endsection

