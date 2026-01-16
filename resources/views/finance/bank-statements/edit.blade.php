@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'Edit Transaction #' . $bankStatement->id,
        'icon' => 'bi bi-pencil',
        'subtitle' => 'Manually match transaction to student',
        'actions' => '<a href="' . route('finance.bank-statements.show', $bankStatement) . '" class="btn btn-finance btn-finance-secondary"><i class="bi bi-arrow-left"></i> Back</a>'
    ])

    <div class="row">
        <div class="col-md-8 mx-auto">
            <!-- Transaction Details -->
            <div class="finance-card finance-animate mb-4 shadow-sm rounded-4 border-0">
                <div class="finance-card-header">
                    <h5 class="mb-0">Transaction Details</h5>
                </div>
                <div class="finance-card-body p-4">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Date:</dt>
                        <dd class="col-sm-8">{{ $bankStatement->transaction_date->format('d M Y') }}</dd>

                        <dt class="col-sm-4">Amount:</dt>
                        <dd class="col-sm-8">
                            <strong>Ksh {{ number_format($bankStatement->amount, 2) }}</strong>
                        </dd>

                        <dt class="col-sm-4">Description:</dt>
                        <dd class="col-sm-8">{{ $bankStatement->description }}</dd>

                        <dt class="col-sm-4">Phone Number:</dt>
                        <dd class="col-sm-8">{{ $bankStatement->phone_number ?? 'N/A' }}</dd>

                        <dt class="col-sm-4">Reference:</dt>
                        <dd class="col-sm-8"><code>{{ $bankStatement->reference_number ?? 'N/A' }}</code></dd>
                    </dl>
                </div>
            </div>

            <!-- Potential Matches -->
            @if(count($potentialMatches) > 0)
            <div class="finance-card finance-animate mb-4 shadow-sm rounded-4 border-0">
                <div class="finance-card-header">
                    <h5 class="mb-0">Potential Matches (by Phone Number)</h5>
                </div>
                <div class="finance-card-body p-4">
                    <div class="list-group">
                        @foreach($potentialMatches as $student)
                            <a href="#" class="list-group-item list-group-item-action" onclick="selectStudent({{ $student->id }}, '{{ $student->first_name }} {{ $student->last_name }}', '{{ $student->admission_number }}'); return false;">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong>{{ $student->first_name }} {{ $student->last_name }}</strong>
                                        <br><small class="text-muted">Admission: {{ $student->admission_number }}</small>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-finance btn-finance-primary">Select</button>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Manual Assignment Form -->
            <div class="finance-card finance-animate mb-4 shadow-sm rounded-4 border-0">
                <div class="finance-card-header">
                    <h5 class="mb-0">Assign to Student</h5>
                </div>
                <div class="finance-card-body p-4">
                    <form method="POST" action="{{ route('finance.bank-statements.update', $bankStatement) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label class="finance-form-label">Student</label>
                            @include('partials.student_live_search', [
                                'hiddenInputId' => 'student_id',
                                'displayInputId' => 'studentSearch',
                                'resultsId' => 'studentResults',
                                'placeholder' => 'Type name or admission number',
                                'initialLabel' => $bankStatement->student ? ($bankStatement->student->first_name . ' ' . $bankStatement->student->last_name . ' (' . $bankStatement->student->admission_number . ')') : '',
                                'includeAlumniArchived' => true
                            ])
                            <small class="form-text text-muted">Search and select a student to assign this transaction</small>
                        </div>

                        <div class="mb-4">
                            <label class="finance-form-label">Notes</label>
                            <textarea name="match_notes" class="finance-form-control" rows="3" placeholder="Optional notes about this match...">{{ old('match_notes', $bankStatement->match_notes) }}</textarea>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('finance.bank-statements.show', $bankStatement) }}" class="btn btn-finance btn-finance-secondary">Cancel</a>
                            <button type="submit" class="btn btn-finance btn-finance-primary">
                                <i class="bi bi-save"></i> Save Assignment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function selectStudent(studentId, name, admission) {
            document.getElementById('student_id').value = studentId;
            document.getElementById('studentSearch').value = name + ' (' + admission + ')';
            document.getElementById('studentResults').innerHTML = '';
        }
    </script>
@endsection

