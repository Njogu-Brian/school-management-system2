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
                    <input type="hidden" id="selectedStudentId" name="student_id" value="{{ request('student_id') }}" required>
                    <input type="text" id="studentLiveSearch" class="finance-form-control"
                           value="{{ request('student_id') ? (optional(\App\Models\Student::find(request('student_id')))->full_name . ' (' . optional(\App\Models\Student::find(request('student_id')))->admission_number . ')') : '' }}"
                           placeholder="Type name or admission # and pause to search">
                    <div id="studentLiveResults" class="list-group shadow-sm mt-1 d-none" style="max-height: 220px; overflow-y: auto;"></div>
                    <small class="text-muted">Start typing; results appear below automatically.</small>
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
            <script>
                (function(){
                    const input = document.getElementById('studentLiveSearch');
                    const results = document.getElementById('studentLiveResults');
                    const hiddenId = document.getElementById('selectedStudentId');
                    const viewBtn = document.getElementById('viewStatementBtn');
                    let t=null;

                    const render = (items) => {
                        results.innerHTML = '';
                        if (!items.length) {
                            results.classList.add('d-none');
                            return;
                        }
                        items.forEach(stu => {
                            const a = document.createElement('a');
                            a.href = '#';
                            a.className = 'list-group-item list-group-item-action';
                            a.textContent = `${stu.full_name} (${stu.admission_number})`;
                            a.addEventListener('click', (e)=>{
                                e.preventDefault();
                                hiddenId.value = stu.id;
                                input.value = `${stu.full_name} (${stu.admission_number})`;
                                viewBtn.disabled = false;
                                results.classList.add('d-none');
                            });
                            results.appendChild(a);
                        });
                        results.classList.remove('d-none');
                    };

                    input.addEventListener('input', ()=>{
                        clearTimeout(t);
                        t = setTimeout(async ()=>{
                            const q = input.value.trim();
                            hiddenId.value = '';
                            viewBtn.disabled = true;
                            if (q.length < 2) { results.classList.add('d-none'); return; }
                            try {
                                const res = await fetch(`{{ route('students.search') }}?q=${encodeURIComponent(q)}`, { headers: { 'Accept': 'application/json' }});
                                if (!res.ok) throw new Error('search failed');
                                const data = await res.json();
                                render(data);
                            } catch (e) {
                                results.classList.add('d-none');
                            }
                        }, 600); // wait a bit after typing
                    });
                })();
            </script>
        </div>
    </div>
@endsection

