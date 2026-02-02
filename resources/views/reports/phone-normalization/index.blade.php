@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'Phone Normalization Report',
        'icon' => 'bi bi-telephone',
        'subtitle' => 'Track phone number fixes across the system',
        'actions' => '<a href="' . url()->previous() . '" class="btn btn-finance btn-finance-secondary"><i class="bi bi-arrow-left"></i> Back</a>'
    ])

    <div class="finance-card finance-animate mb-4 shadow-sm rounded-4 border-0">
        <div class="finance-card-body p-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Model</label>
                    <select name="model_type" class="form-select">
                        <option value="">All</option>
                        @foreach($modelTypes as $type)
                            <option value="{{ $type }}" {{ request('model_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Field</label>
                    <select name="field" class="form-select">
                        <option value="">All</option>
                        @foreach($fields as $field)
                            <option value="{{ $field }}" {{ request('field') == $field ? 'selected' : '' }}>{{ $field }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Source</label>
                    <select name="source" class="form-select">
                        <option value="">All</option>
                        @foreach($sources as $source)
                            <option value="{{ $source }}" {{ request('source') == $source ? 'selected' : '' }}>{{ $source }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Old/New/Model ID">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-finance btn-finance-primary w-100"><i class="bi bi-funnel"></i> Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
        <div class="finance-card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Model</th>
                            <th>Model ID</th>
                            <th>Field</th>
                            <th>Old</th>
                            <th>New</th>
                            <th>Country</th>
                            <th>Source</th>
                            <th>User</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td>{{ $log->created_at?->format('d M Y H:i') }}</td>
                                <td>{{ $log->model_type }}</td>
                                <td>{{ $log->model_id ?? 'N/A' }}</td>
                                <td>{{ $log->field }}</td>
                                <td><code>{{ $log->old_value ?? 'N/A' }}</code></td>
                                <td><code>{{ $log->new_value ?? 'N/A' }}</code></td>
                                <td>{{ $log->country_code ?? 'N/A' }}</td>
                                <td>{{ $log->source }}</td>
                                <td>{{ $log->user_id ?? 'System' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted p-4">No normalization changes found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($logs->hasPages())
            <div class="finance-card-footer p-3">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
@endsection
