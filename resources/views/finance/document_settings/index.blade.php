@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'Document Settings',
        'icon' => 'bi bi-file-earmark-text',
        'subtitle' => 'Configure headers and footers for receipts, invoices, and statements'
    ])

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show finance-animate" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('finance.document-settings.update') }}">
        @csrf
        
        <div class="row">
            <div class="col-md-6">
                <div class="finance-card finance-animate mb-4">
                    <div class="finance-card-header">
                        <i class="bi bi-receipt me-2"></i> Receipt Settings
                    </div>
                    <div class="finance-card-body">
                        <div class="mb-3">
                            <label class="finance-form-label">Receipt Header</label>
                            <textarea name="receipt_header" 
                                      class="finance-form-control" 
                                      rows="5"
                                      placeholder="Enter header text/HTML for receipts">{{ old('receipt_header', $settings['receipt_header']) }}</textarea>
                            <small class="form-text text-muted">This will appear at the top of all receipts</small>
                        </div>
                        <div class="mb-3">
                            <label class="finance-form-label">Receipt Footer</label>
                            <textarea name="receipt_footer" 
                                      class="finance-form-control" 
                                      rows="5"
                                      placeholder="Enter footer text/HTML for receipts">{{ old('receipt_footer', $settings['receipt_footer']) }}</textarea>
                            <small class="form-text text-muted">This will appear at the bottom of all receipts</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="finance-card finance-animate mb-4">
                    <div class="finance-card-header">
                        <i class="bi bi-file-text me-2"></i> Invoice Settings
                    </div>
                    <div class="finance-card-body">
                        <div class="mb-3">
                            <label class="finance-form-label">Invoice Header</label>
                            <textarea name="invoice_header" 
                                      class="finance-form-control" 
                                      rows="5"
                                      placeholder="Enter header text/HTML for invoices">{{ old('invoice_header', $settings['invoice_header']) }}</textarea>
                            <small class="form-text text-muted">This will appear at the top of all invoices</small>
                        </div>
                        <div class="mb-3">
                            <label class="finance-form-label">Invoice Footer</label>
                            <textarea name="invoice_footer" 
                                      class="finance-form-control" 
                                      rows="5"
                                      placeholder="Enter footer text/HTML for invoices">{{ old('invoice_footer', $settings['invoice_footer']) }}</textarea>
                            <small class="form-text text-muted">This will appear at the bottom of all invoices</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="finance-card finance-animate mb-4">
                    <div class="finance-card-header">
                        <i class="bi bi-list-check me-2"></i> Statement Settings
                    </div>
                    <div class="finance-card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="finance-form-label">Statement Header</label>
                                    <textarea name="statement_header" 
                                              class="finance-form-control" 
                                              rows="5"
                                              placeholder="Enter header text/HTML for statements">{{ old('statement_header', $settings['statement_header']) }}</textarea>
                                    <small class="form-text text-muted">This will appear at the top of all statements</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="finance-form-label">Statement Footer</label>
                                    <textarea name="statement_footer" 
                                              class="finance-form-control" 
                                              rows="5"
                                              placeholder="Enter footer text/HTML for statements">{{ old('statement_footer', $settings['statement_footer']) }}</textarea>
                                    <small class="form-text text-muted">This will appear at the bottom of all statements</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('finance.invoices.index') }}" class="btn btn-finance btn-finance-outline">
                <i class="bi bi-x-circle"></i> Cancel
            </a>
            <button type="submit" class="btn btn-finance btn-finance-primary">
                <i class="bi bi-check-circle"></i> Save Settings
            </button>
        </div>
    </form>
@endsection

