@extends('layouts.app')

@section('content')
<div class="container">
    <h3 class="mb-3">Bulk Credit / Debit Adjustments</h3>

    @includeIf('finance.invoices.partials.alerts')

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <p class="mb-2">Upload an Excel/CSV using this exact header order:</p>
            <code>admission_number, votehead_name, effective_date, type, year, term, reason, amount</code>
            <div class="mt-2">
                <a href="{{ route('finance.journals.bulk.template') }}" class="btn btn-sm btn-outline-secondary">
                    Download Template
                </a>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('finance.journals.bulk.import') }}" enctype="multipart/form-data" class="row g-3">
                @csrf
                <div class="col-md-6">
                    <label class="form-label">Upload file (.xlsx, .xls, .csv)</label>
                    <input type="file" name="file" class="form-control" required>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary"><i class="bi bi-upload"></i> Import</button>
                </div>
            </form>
        </div>
    </div>

    @if(session('bulk_summary'))
        @php $sum = session('bulk_summary'); @endphp
        <div class="card shadow-sm mt-4">
            <div class="card-header fw-bold">Import Summary</div>
            <div class="card-body">
                <div class="mb-2">
                    <span class="badge bg-success">Success: {{ $sum['success'] }}</span>
                    <span class="badge bg-danger ms-2">Failed: {{ $sum['failed'] }}</span>
                    <span class="badge bg-secondary ms-2">Total: {{ $sum['total'] }}</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Line</th>
                                <th>Status</th>
                                <th>Message</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sum['rows'] as $r)
                                <tr>
                                    <td>{{ $r['line'] }}</td>
                                    <td>
                                        <span class="badge {{ $r['status']==='OK' ? 'bg-success' : 'bg-danger' }}">
                                            {{ $r['status'] }}
                                        </span>
                                    </td>
                                    <td>{{ $r['message'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <a class="btn btn-outline-primary" href="{{ route('finance.invoices.index') }}">Back to Invoices</a>
            </div>
        </div>
    @endif
</div>
@endsection
