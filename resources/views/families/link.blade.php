@extends('layouts.app')

@section('content')
<div class="container">
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ Route::has('dashboard') ? route('dashboard') : url('/') }}"><i class="bi bi-house"></i></a></li>
      <li class="breadcrumb-item"><a href="{{ route('families.index') }}">Families</a></li>
      <li class="breadcrumb-item active">Link Siblings</li>
    </ol>
  </nav>

  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Link Students as Siblings</h1>
    <a href="{{ route('families.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
  </div>

  @include('students.partials.alerts')

  <div class="row">
    <div class="col-lg-6">
      <div class="card mb-3">
        <div class="card-header">1. Search First Student</div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label">Search by name or admission number</label>
            <input type="text" id="studentA_search" class="form-control" placeholder="Type to search...">
          </div>
          <div id="studentA_results" class="list-group"></div>
          <div id="studentA_selected" class="mt-3"></div>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card mb-3">
        <div class="card-header">2. Search Second Student</div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label">Search by name or admission number</label>
            <input type="text" id="studentB_search" class="form-control" placeholder="Type to search..." disabled>
          </div>
          <div id="studentB_results" class="list-group"></div>
          <div id="studentB_selected" class="mt-3"></div>
        </div>
      </div>

      <form action="{{ route('families.link.store') }}" method="POST" id="linkForm" style="display: none;">
        @csrf
        <input type="hidden" name="student_a_id" id="student_a_id">
        <input type="hidden" name="student_b_id" id="student_b_id">
        <button type="submit" class="btn btn-primary w-100">
          <i class="bi bi-link-45deg"></i> Link Students as Siblings
        </button>
      </form>
    </div>
  </div>

  <div class="alert alert-info mt-3">
    <i class="bi bi-info-circle"></i> <strong>Purpose:</strong> Linking students as siblings enables:
    <ul class="mb-0 mt-2">
      <li>Family-level fee billing (one fee per family instead of per student)</li>
      <li>Easy application of sibling discounts</li>
      <li>Unified family communication and reporting</li>
    </ul>
    <div class="mt-2">
      <strong>Note:</strong> Guardian details are automatically pulled from the students' parent records. 
      You can edit them later if needed from the family management page.
    </div>
  </div>
</div>

{{-- Include student search modal --}}
@include('partials.student_search_modal')

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let studentA = null;
    let studentB = null;
    let searchTimeoutA = null;
    let searchTimeoutB = null;

    const searchA = document.getElementById('studentA_search');
    const resultsA = document.getElementById('studentA_results');
    const selectedA = document.getElementById('studentA_selected');
    const searchB = document.getElementById('studentB_search');
    const resultsB = document.getElementById('studentB_results');
    const selectedB = document.getElementById('studentB_selected');
    const linkForm = document.getElementById('linkForm');
    const studentAIdInput = document.getElementById('student_a_id');
    const studentBIdInput = document.getElementById('student_b_id');

    // Search for Student A
    searchA.addEventListener('input', function() {
        clearTimeout(searchTimeoutA);
        const query = this.value.trim();
        
        if (query.length < 2) {
            resultsA.innerHTML = '';
            return;
        }

        searchTimeoutA = setTimeout(async () => {
            resultsA.innerHTML = '<div class="list-group-item text-center">Searching...</div>';
            
            try {
                const res = await fetch(`{{ route('api.students.search') }}?q=${encodeURIComponent(query)}`);
                const data = await res.json();

                if (data.length === 0) {
                    resultsA.innerHTML = '<div class="list-group-item text-center text-muted">No students found.</div>';
                    return;
                }

                resultsA.innerHTML = data.map(stu => `
                    <a href="#" class="list-group-item list-group-item-action selectStudentA" 
                       data-id="${stu.id}" 
                       data-name="${stu.full_name}" 
                       data-adm="${stu.admission_number}">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${stu.admission_number}</strong> — ${stu.full_name}
                                ${stu.classroom_name ? '<br><small class="text-muted">' + stu.classroom_name + '</small>' : ''}
                            </div>
                            <button class="btn btn-sm btn-primary">Select</button>
                        </div>
                    </a>
                `).join('');

                document.querySelectorAll('.selectStudentA').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        studentA = {
                            id: this.dataset.id,
                            name: this.dataset.name,
                            adm: this.dataset.adm
                        };
                        selectedA.innerHTML = `
                            <div class="alert alert-success">
                                <strong>Selected:</strong> ${studentA.adm} — ${studentA.name}
                                <button type="button" class="btn btn-sm btn-outline-danger float-end" onclick="clearStudentA()">Clear</button>
                            </div>
                        `;
                        resultsA.innerHTML = '';
                        searchA.value = '';
                        searchB.disabled = false;
                        studentAIdInput.value = studentA.id;
                        checkFormReady();
                    });
                });
            } catch (error) {
                resultsA.innerHTML = '<div class="list-group-item text-center text-danger">Error searching. Please try again.</div>';
            }
        }, 300);
    });

    // Search for Student B
    searchB.addEventListener('input', function() {
        clearTimeout(searchTimeoutB);
        const query = this.value.trim();
        
        if (query.length < 2) {
            resultsB.innerHTML = '';
            return;
        }

        searchTimeoutB = setTimeout(async () => {
            resultsB.innerHTML = '<div class="list-group-item text-center">Searching...</div>';
            
            try {
                const res = await fetch(`{{ route('api.students.search') }}?q=${encodeURIComponent(query)}`);
                const data = await res.json();

                if (data.length === 0) {
                    resultsB.innerHTML = '<div class="list-group-item text-center text-muted">No students found.</div>';
                    return;
                }

                resultsB.innerHTML = data.map(stu => {
                    const isStudentA = studentA && parseInt(stu.id) === parseInt(studentA.id);
                    return `
                        <a href="#" class="list-group-item list-group-item-action ${isStudentA ? 'disabled' : 'selectStudentB'}" 
                           data-id="${stu.id}" 
                           data-name="${stu.full_name}" 
                           data-adm="${stu.admission_number}"
                           ${isStudentA ? 'style="opacity: 0.5;"' : ''}>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>${stu.admission_number}</strong> — ${stu.full_name}
                                    ${stu.classroom_name ? '<br><small class="text-muted">' + stu.classroom_name + '</small>' : ''}
                                    ${isStudentA ? '<br><small class="text-danger">(Already selected as first student)</small>' : ''}
                                </div>
                                ${!isStudentA ? '<button class="btn btn-sm btn-primary">Select</button>' : ''}
                            </div>
                        </a>
                    `;
                }).join('');

                document.querySelectorAll('.selectStudentB').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        studentB = {
                            id: this.dataset.id,
                            name: this.dataset.name,
                            adm: this.dataset.adm
                        };
                        selectedB.innerHTML = `
                            <div class="alert alert-success">
                                <strong>Selected:</strong> ${studentB.adm} — ${studentB.name}
                                <button type="button" class="btn btn-sm btn-outline-danger float-end" onclick="clearStudentB()">Clear</button>
                            </div>
                        `;
                        resultsB.innerHTML = '';
                        searchB.value = '';
                        studentBIdInput.value = studentB.id;
                        checkFormReady();
                    });
                });
            } catch (error) {
                resultsB.innerHTML = '<div class="list-group-item text-center text-danger">Error searching. Please try again.</div>';
            }
        }, 300);
    });

    function checkFormReady() {
        if (studentA && studentB) {
            linkForm.style.display = 'block';
        } else {
            linkForm.style.display = 'none';
        }
    }

    window.clearStudentA = function() {
        studentA = null;
        selectedA.innerHTML = '';
        searchA.value = '';
        searchB.disabled = true;
        studentAIdInput.value = '';
        checkFormReady();
    };

    window.clearStudentB = function() {
        studentB = null;
        selectedB.innerHTML = '';
        searchB.value = '';
        studentBIdInput.value = '';
        checkFormReady();
    };
});
</script>
@endpush
@endsection

