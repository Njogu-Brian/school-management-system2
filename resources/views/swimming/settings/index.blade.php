@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Swimming Settings',
        'icon' => 'bi bi-gear',
        'subtitle' => 'Configure swimming payment and attendance settings',
        'actions' => '<a href="' . route('swimming.wallets.index') . '" class="btn btn-finance btn-finance-secondary"><i class="bi bi-arrow-left"></i> Back</a>'
    ])

    @include('finance.invoices.partials.alerts')

    <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
        <div class="finance-card-header d-flex align-items-center gap-2">
            <i class="bi bi-sliders"></i> <span>Swimming Configuration</span>
        </div>
        <div class="finance-card-body">
            <form action="{{ route('swimming.settings.update') }}" method="POST">
                @csrf
                <div class="row g-4">
                    <div class="col-md-8">
                        <label class="finance-form-label">Per Visit Cost (Ksh) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Ksh</span>
                            <input type="number" 
                                   name="swimming_per_visit_cost" 
                                   step="0.01" 
                                   min="0" 
                                   class="finance-form-control @error('swimming_per_visit_cost') is-invalid @enderror" 
                                   value="{{ old('swimming_per_visit_cost', $per_visit_cost) }}" 
                                   required>
                        </div>
                        <small class="text-muted">This is the amount charged per swimming session attendance for students paying per visit. It will be deducted from the student's swimming wallet when attendance is marked.</small>
                        @error('swimming_per_visit_cost')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <div class="finance-card" style="background: #f8f9fa; border: 2px dashed #dee2e6;">
                            <div class="finance-card-body">
                                <h6 class="mb-2"><i class="bi bi-info-circle"></i> Information</h6>
                                <p class="small text-muted mb-2">
                                    <strong>Current Setting:</strong><br>
                                    <span class="fw-bold text-primary">Ksh {{ number_format($per_visit_cost, 2) }}</span> per visit
                                </p>
                                <p class="small text-muted mb-0">
                                    This cost is automatically deducted from student wallets when swimming attendance is marked.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mt-2">
                    <div class="col-md-8">
                        <label class="finance-form-label">Termly Fee Student Per Visit Cost (Ksh)</label>
                        <div class="input-group">
                            <span class="input-group-text">Ksh</span>
                            <input type="number" 
                                   name="swimming_termly_per_visit_cost" 
                                   step="0.01" 
                                   min="0" 
                                   class="finance-form-control @error('swimming_termly_per_visit_cost') is-invalid @enderror" 
                                   value="{{ old('swimming_termly_per_visit_cost', $termly_per_visit_cost ?? 0) }}">
                        </div>
                        <small class="text-muted">This is the amount deducted per visit for students who have paid termly swimming fees. If set to 0, termly fee students will not be charged per visit. The termly fee amount will be credited to their wallet when paid.</small>
                        @error('swimming_termly_per_visit_cost')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <div class="finance-card" style="background: #e7f3ff; border: 2px dashed #b3d9ff;">
                            <div class="finance-card-body">
                                <h6 class="mb-2"><i class="bi bi-info-circle"></i> Termly Fee Info</h6>
                                <p class="small text-muted mb-2">
                                    <strong>Current Setting:</strong><br>
                                    <span class="fw-bold text-primary">Ksh {{ number_format($termly_per_visit_cost ?? 0, 2) }}</span> per visit
                                </p>
                                <p class="small text-muted mb-0">
                                    When a student pays termly swimming fee, the full amount is credited to their wallet. Each visit deducts this amount instead of the regular per-visit cost.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <button type="submit" class="btn btn-finance btn-finance-primary">
                            <i class="bi bi-check-circle"></i> Save Settings
                        </button>
                        <a href="{{ route('swimming.wallets.index') }}" class="btn btn-finance btn-finance-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
  </div>
</div>
@endsection
