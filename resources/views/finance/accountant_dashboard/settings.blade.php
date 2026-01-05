@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Accountant Dashboard Settings',
        'icon' => 'bi bi-gear',
        'subtitle' => 'Configure thresholds and parameters for the accountant dashboard',
        'actions' => '<a href="' . route('finance.accountant-dashboard.index') . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>'
    ])

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show finance-animate" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('finance.accountant-dashboard.settings.update') }}">
        @csrf
        
        <div class="finance-card finance-animate mb-4">
            <div class="finance-card-header">
                <h5 class="mb-0"><i class="bi bi-sliders me-2"></i> Dashboard Thresholds</h5>
            </div>
            <div class="finance-card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="finance-form-label">Default Days Ahead <span class="text-danger">*</span></label>
                        <input type="number" 
                               name="days_ahead_default" 
                               class="finance-form-control" 
                               value="{{ old('days_ahead_default', $settings['days_ahead_default']) }}"
                               min="1"
                               max="365"
                               required>
                        <small class="form-text text-muted">Default number of days to look ahead for upcoming installments (1-365)</small>
                        @error('days_ahead_default')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="finance-form-label">High-Risk Days Until Term End <span class="text-danger">*</span></label>
                        <input type="number" 
                               name="high_risk_days" 
                               class="finance-form-control" 
                               value="{{ old('high_risk_days', $settings['high_risk_days']) }}"
                               min="1"
                               max="365"
                               required>
                        <small class="form-text text-muted">Number of days before term end to flag plans as high-risk (1-365)</small>
                        @error('high_risk_days')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="finance-form-label">High-Risk Percentage Threshold <span class="text-danger">*</span></label>
                        <input type="number" 
                               name="high_risk_percentage" 
                               class="finance-form-control" 
                               value="{{ old('high_risk_percentage', $settings['high_risk_percentage']) }}"
                               min="0"
                               max="100"
                               required>
                        <small class="form-text text-muted">Minimum percentage paid threshold for high-risk classification (0-100)</small>
                        @error('high_risk_percentage')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="finance-form-label">High-Risk Minimum Balance <span class="text-danger">*</span></label>
                        <input type="number" 
                               name="high_risk_min_balance" 
                               class="finance-form-control" 
                               value="{{ old('high_risk_min_balance', $settings['high_risk_min_balance']) }}"
                               min="0"
                               required>
                        <small class="form-text text-muted">Minimum outstanding balance (KES) to be considered high-risk</small>
                        @error('high_risk_min_balance')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('finance.accountant-dashboard.index') }}" class="btn btn-finance btn-finance-outline">
                <i class="bi bi-x-circle"></i> Cancel
            </a>
            <button type="submit" class="btn btn-finance btn-finance-primary">
                <i class="bi bi-check-circle"></i> Save Settings
            </button>
        </div>
    </form>
  </div>
</div>
@endsection

