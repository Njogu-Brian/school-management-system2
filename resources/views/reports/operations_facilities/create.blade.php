@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-1">New Operations & Facilities Report</h3>
            <div class="text-muted">Weekly facilities status</div>
        </div>
        <a href="{{ route('reports.operations-facilities.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('reports.operations-facilities.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Week Ending</label>
                        <input type="date" name="week_ending" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Campus</label>
                        <select name="campus" class="form-select">
                            <option value="">Select campus</option>
                            <option value="lower">Lower</option>
                            <option value="upper">Upper</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Area</label>
                        <input type="text" name="area" class="form-control" placeholder="Toilets/Classrooms/Desks/Kitchen/Water/Transport/Security" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Select</option>
                            <option value="Good">Good</option>
                            <option value="Fair">Fair</option>
                            <option value="Poor">Poor</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Resolved</label>
                        <select name="resolved" class="form-select">
                            <option value="">Select</option>
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Responsible Person</label>
                        <input type="text" name="responsible_person" class="form-control">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Issue Noted</label>
                        <textarea name="issue_noted" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Action Needed</label>
                        <textarea name="action_needed" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>

                <div class="mt-4">
                    <button class="btn btn-primary">Save Report</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
