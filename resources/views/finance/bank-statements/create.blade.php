@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'Upload Bank Statement',
        'icon' => 'bi bi-upload',
        'subtitle' => 'Upload and parse bank statement PDF',
        'actions' => '<a href="' . route('finance.bank-statements.index') . '" class="btn btn-finance btn-finance-secondary"><i class="bi bi-arrow-left"></i> Back</a>'
    ])

    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
                <div class="finance-card-header">
                    <h5 class="mb-0">Upload Statement</h5>
                </div>
                <div class="finance-card-body p-4">
                    <form method="POST" action="{{ route('finance.bank-statements.store') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-4">
                            <label class="finance-form-label">Bank Statement PDF</label>
                            <input type="file" name="statement_file" class="finance-form-control @error('statement_file') is-invalid @enderror" accept=".pdf" required>
                            <small class="form-text text-muted">Upload MPESA or Equity Bank statement PDF (max 10MB)</small>
                            @error('statement_file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="finance-form-label">Bank Account</label>
                            <select name="bank_account_id" class="finance-form-select">
                                <option value="">Select Bank Account (Optional)</option>
                                @foreach($bankAccounts as $account)
                                    <option value="{{ $account->id }}" {{ old('bank_account_id') == $account->id ? 'selected' : '' }}>
                                        {{ $account->name }} - {{ $account->account_number }} ({{ $account->bank_name }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Select the bank account this statement belongs to</small>
                        </div>

                        <div class="mb-4">
                            <label class="finance-form-label">Bank Type</label>
                            <select name="bank_type" class="finance-form-select">
                                <option value="">Auto-detect</option>
                                <option value="mpesa" {{ old('bank_type') == 'mpesa' ? 'selected' : '' }}>MPESA</option>
                                <option value="equity" {{ old('bank_type') == 'equity' ? 'selected' : '' }}>Equity Bank</option>
                            </select>
                            <small class="form-text text-muted">Leave as auto-detect if unsure</small>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('finance.bank-statements.index') }}" class="btn btn-finance btn-finance-secondary">Cancel</a>
                            <button type="submit" class="btn btn-finance btn-finance-primary">
                                <i class="bi bi-upload"></i> Upload & Parse
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mt-4">
                <div class="finance-card-body p-4">
                    <h6 class="mb-3">How it works:</h6>
                    <ul class="mb-0">
                        <li>Upload a PDF bank statement (MPESA or Equity Bank)</li>
                        <li>The system will automatically parse all transactions</li>
                        <li>Transactions will be matched to students by:
                            <ul>
                                <li>Admission number in description</li>
                                <li>Student name in description</li>
                                <li>Parent/guardian phone number</li>
                            </ul>
                        </li>
                        <li>Review and confirm matched transactions</li>
                        <li>Manually match unmatched transactions</li>
                        <li>Share payments among siblings if needed</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

