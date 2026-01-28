@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Bank Statement PDF</h4>
        <div>
            <a href="{{ route('finance.bank-statements.download-pdf', $bankStatementId) }}" class="btn btn-primary">
                <i class="bi bi-download"></i> Download
            </a>
            <a href="{{ route('finance.bank-statements.show', $bankStatementId) }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <iframe 
                src="{{ route('finance.bank-statements.serve-pdf', $bankStatementId) }}" 
                style="width: 100%; height: 80vh; border: none;"
                title="Bank Statement PDF">
            </iframe>
        </div>
    </div>
</div>
@endsection

