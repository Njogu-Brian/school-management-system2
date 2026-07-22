@extends('layouts.app')

@php
  $routePrefix = request()->routeIs('senior_teacher.*') ? 'senior_teacher.advances' : 'teacher.advances';
@endphp

@push('styles')
    @if(request()->routeIs('senior_teacher.*'))
        @include('senior_teacher.partials.styles')
    @endif
@endpush

@section('content')
<div class="{{ request()->routeIs('senior_teacher.*') ? 'senior-teacher-page' : '' }}">
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="{{ route($routePrefix . '.index') }}" class="text-decoration-none">
            <i class="bi bi-arrow-left"></i> Back to requests
        </a>
        <h1 class="h4 mb-0">Request Salary Advance</h1>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <p class="text-muted small mb-3">
                Request a specific amount. Installment repayment plans are set by admin when approving.
            </p>
            <form method="POST" action="{{ route($routePrefix . '.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Amount (KES) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" step="0.01" min="0.01" class="form-control" value="{{ old('amount') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Advance Date <span class="text-danger">*</span></label>
                        <input type="date" name="advance_date" class="form-control" value="{{ old('advance_date', now()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Purpose</label>
                        <input type="text" name="purpose" class="form-control" value="{{ old('purpose') }}" placeholder="Reason for the advance">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Additional Details</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Optional details">{{ old('description') }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes to Finance Team</label>
                        <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route($routePrefix . '.index') }}" class="btn btn-light">Cancel</a>
                    <button class="btn btn-primary">
                        <i class="bi bi-send"></i> Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
@endsection
