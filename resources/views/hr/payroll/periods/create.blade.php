@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h2 class="mb-0">New Payroll Period</h2>
      <small class="text-muted">Create a new payroll processing period</small>
    </div>
    <a href="{{ route('hr.payroll.periods.index') }}" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left"></i> Back
    </a>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <form action="{{ route('hr.payroll.periods.store') }}" method="POST">
        @csrf

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Year <span class="text-danger">*</span></label>
            <input type="number" name="year" min="2020" max="2100" class="form-control @error('year') is-invalid @enderror" value="{{ old('year', date('Y')) }}" required>
            @error('year')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label">Month <span class="text-danger">*</span></label>
            <select name="month" class="form-select @error('month') is-invalid @enderror" required>
              @for($m = 1; $m <= 12; $m++)
                <option value="{{ $m }}" @selected(old('month', date('n'))==$m)>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
              @endfor
            </select>
            @error('month')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label">Start Date <span class="text-danger">*</span></label>
            <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date') }}" required>
            @error('start_date')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label">End Date <span class="text-danger">*</span></label>
            <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date') }}" required>
            @error('end_date')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label">Pay Date <span class="text-danger">*</span></label>
            <input type="date" name="pay_date" class="form-control @error('pay_date') is-invalid @enderror" value="{{ old('pay_date') }}" required>
            <div class="form-text">Date when payroll will be paid</div>
            @error('pay_date')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
          <a href="{{ route('hr.payroll.periods.index') }}" class="btn btn-outline-secondary">Cancel</a>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> Create Period
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

