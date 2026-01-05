@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'Document Settings',
        'icon' => 'bi bi-file-earmark-text',
        'subtitle' => 'Configure headers, footers, and number formats for receipts, invoices, and statements'
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

        {{-- Document Number Settings --}}
        <div class="row">
            <div class="col-md-12">
                <div class="finance-card finance-animate mb-4">
                    <div class="finance-card-header">
                        <i class="bi bi-hash me-2"></i> Document Number Settings
                    </div>
                    <div class="finance-card-body">
                        <p class="text-muted mb-4">Configure prefix, suffix, starting number, and format for document numbers.</p>
                        
                        <div class="row g-3">
                            {{-- Invoice Number --}}
                            <div class="col-md-6 col-lg-4">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="fw-bold mb-3"><i class="bi bi-file-text text-primary me-2"></i> Invoice Number</h6>
                                    <div class="mb-3">
                                        <label class="finance-form-label small">Prefix</label>
                                        <input type="text" 
                                               name="counters[invoice][prefix]" 
                                               class="finance-form-control form-control-sm" 
                                               value="{{ old('counters.invoice.prefix', $counters['invoice']->prefix ?? 'INV') }}"
                                               maxlength="20">
                                    </div>
                                    <div class="mb-3">
                                        <label class="finance-form-label small">Suffix</label>
                                        <input type="text" 
                                               name="counters[invoice][suffix]" 
                                               class="finance-form-control form-control-sm" 
                                               value="{{ old('counters.invoice.suffix', $counters['invoice']->suffix ?? '') }}"
                                               maxlength="20">
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="finance-form-label small">Starting Number</label>
                                            <input type="number" 
                                                   name="counters[invoice][next_number]" 
                                                   class="finance-form-control form-control-sm" 
                                                   value="{{ old('counters.invoice.next_number', $counters['invoice']->next_number ?? 1) }}"
                                                   min="1">
                                        </div>
                                        <div class="col-6">
                                            <label class="finance-form-label small">Format (Digits)</label>
                                            <input type="number" 
                                                   name="counters[invoice][padding_length]" 
                                                   class="finance-form-control form-control-sm" 
                                                   value="{{ old('counters.invoice.padding_length', $counters['invoice']->padding_length ?? 5) }}"
                                                   min="1" max="10">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="finance-form-label small">Reset Period</label>
                                        <select name="counters[invoice][reset_period]" class="finance-form-control form-control-sm">
                                            <option value="never" {{ ($counters['invoice']->reset_period ?? 'yearly') == 'never' ? 'selected' : '' }}>Never</option>
                                            <option value="yearly" {{ ($counters['invoice']->reset_period ?? 'yearly') == 'yearly' ? 'selected' : '' }}>Yearly</option>
                                            <option value="monthly" {{ ($counters['invoice']->reset_period ?? 'yearly') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                        </select>
                                    </div>
                                    <div class="small text-muted">
                                        <strong>Preview:</strong> {{ ($counters['invoice']->prefix ?? 'INV') }}-{{ str_pad(($counters['invoice']->next_number ?? 1), ($counters['invoice']->padding_length ?? 5), '0', STR_PAD_LEFT) }}{{ $counters['invoice']->suffix ?? '' }}
                                    </div>
                                </div>
                            </div>

                            {{-- Receipt Number --}}
                            <div class="col-md-6 col-lg-4">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="fw-bold mb-3"><i class="bi bi-receipt text-success me-2"></i> Receipt Number</h6>
                                    <div class="mb-3">
                                        <label class="finance-form-label small">Prefix</label>
                                        <input type="text" 
                                               name="counters[receipt][prefix]" 
                                               class="finance-form-control form-control-sm" 
                                               value="{{ old('counters.receipt.prefix', $counters['receipt']->prefix ?? 'RCPT') }}"
                                               maxlength="20">
                                    </div>
                                    <div class="mb-3">
                                        <label class="finance-form-label small">Suffix</label>
                                        <input type="text" 
                                               name="counters[receipt][suffix]" 
                                               class="finance-form-control form-control-sm" 
                                               value="{{ old('counters.receipt.suffix', $counters['receipt']->suffix ?? '') }}"
                                               maxlength="20">
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="finance-form-label small">Starting Number</label>
                                            <input type="number" 
                                                   name="counters[receipt][next_number]" 
                                                   class="finance-form-control form-control-sm" 
                                                   value="{{ old('counters.receipt.next_number', $counters['receipt']->next_number ?? 1) }}"
                                                   min="1">
                                        </div>
                                        <div class="col-6">
                                            <label class="finance-form-label small">Format (Digits)</label>
                                            <input type="number" 
                                                   name="counters[receipt][padding_length]" 
                                                   class="finance-form-control form-control-sm" 
                                                   value="{{ old('counters.receipt.padding_length', $counters['receipt']->padding_length ?? 6) }}"
                                                   min="1" max="10">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="finance-form-label small">Reset Period</label>
                                        <select name="counters[receipt][reset_period]" class="finance-form-control form-control-sm">
                                            <option value="never" {{ ($counters['receipt']->reset_period ?? 'yearly') == 'never' ? 'selected' : '' }}>Never</option>
                                            <option value="yearly" {{ ($counters['receipt']->reset_period ?? 'yearly') == 'yearly' ? 'selected' : '' }}>Yearly</option>
                                            <option value="monthly" {{ ($counters['receipt']->reset_period ?? 'yearly') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                        </select>
                                    </div>
                                    <div class="small text-muted">
                                        <strong>Preview:</strong> {{ ($counters['receipt']->prefix ?? 'RCPT') }}-{{ str_pad(($counters['receipt']->next_number ?? 1), ($counters['receipt']->padding_length ?? 6), '0', STR_PAD_LEFT) }}{{ $counters['receipt']->suffix ?? '' }}
                                    </div>
                                </div>
                            </div>

                            {{-- Credit Note Number --}}
                            <div class="col-md-6 col-lg-4">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="fw-bold mb-3"><i class="bi bi-file-earmark-plus text-info me-2"></i> Credit Note Number</h6>
                                    <div class="mb-3">
                                        <label class="finance-form-label small">Prefix</label>
                                        <input type="text" 
                                               name="counters[credit_note][prefix]" 
                                               class="finance-form-control form-control-sm" 
                                               value="{{ old('counters.credit_note.prefix', $counters['credit_note']->prefix ?? 'CN') }}"
                                               maxlength="20">
                                    </div>
                                    <div class="mb-3">
                                        <label class="finance-form-label small">Suffix</label>
                                        <input type="text" 
                                               name="counters[credit_note][suffix]" 
                                               class="finance-form-control form-control-sm" 
                                               value="{{ old('counters.credit_note.suffix', $counters['credit_note']->suffix ?? '') }}"
                                               maxlength="20">
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="finance-form-label small">Starting Number</label>
                                            <input type="number" 
                                                   name="counters[credit_note][next_number]" 
                                                   class="finance-form-control form-control-sm" 
                                                   value="{{ old('counters.credit_note.next_number', $counters['credit_note']->next_number ?? 1) }}"
                                                   min="1">
                                        </div>
                                        <div class="col-6">
                                            <label class="finance-form-label small">Format (Digits)</label>
                                            <input type="number" 
                                                   name="counters[credit_note][padding_length]" 
                                                   class="finance-form-control form-control-sm" 
                                                   value="{{ old('counters.credit_note.padding_length', $counters['credit_note']->padding_length ?? 5) }}"
                                                   min="1" max="10">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="finance-form-label small">Reset Period</label>
                                        <select name="counters[credit_note][reset_period]" class="finance-form-control form-control-sm">
                                            <option value="never" {{ ($counters['credit_note']->reset_period ?? 'never') == 'never' ? 'selected' : '' }}>Never</option>
                                            <option value="yearly" {{ ($counters['credit_note']->reset_period ?? 'never') == 'yearly' ? 'selected' : '' }}>Yearly</option>
                                            <option value="monthly" {{ ($counters['credit_note']->reset_period ?? 'never') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                        </select>
                                    </div>
                                    <div class="small text-muted">
                                        <strong>Preview:</strong> {{ ($counters['credit_note']->prefix ?? 'CN') }}-{{ str_pad(($counters['credit_note']->next_number ?? 1), ($counters['credit_note']->padding_length ?? 5), '0', STR_PAD_LEFT) }}{{ $counters['credit_note']->suffix ?? '' }}
                                    </div>
                                </div>
                            </div>

                            {{-- Debit Note Number --}}
                            <div class="col-md-6 col-lg-4">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="fw-bold mb-3"><i class="bi bi-file-earmark-minus text-warning me-2"></i> Debit Note Number</h6>
                                    <div class="mb-3">
                                        <label class="finance-form-label small">Prefix</label>
                                        <input type="text" 
                                               name="counters[debit_note][prefix]" 
                                               class="finance-form-control form-control-sm" 
                                               value="{{ old('counters.debit_note.prefix', $counters['debit_note']->prefix ?? 'DN') }}"
                                               maxlength="20">
                                    </div>
                                    <div class="mb-3">
                                        <label class="finance-form-label small">Suffix</label>
                                        <input type="text" 
                                               name="counters[debit_note][suffix]" 
                                               class="finance-form-control form-control-sm" 
                                               value="{{ old('counters.debit_note.suffix', $counters['debit_note']->suffix ?? '') }}"
                                               maxlength="20">
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="finance-form-label small">Starting Number</label>
                                            <input type="number" 
                                                   name="counters[debit_note][next_number]" 
                                                   class="finance-form-control form-control-sm" 
                                                   value="{{ old('counters.debit_note.next_number', $counters['debit_note']->next_number ?? 1) }}"
                                                   min="1">
                                        </div>
                                        <div class="col-6">
                                            <label class="finance-form-label small">Format (Digits)</label>
                                            <input type="number" 
                                                   name="counters[debit_note][padding_length]" 
                                                   class="finance-form-control form-control-sm" 
                                                   value="{{ old('counters.debit_note.padding_length', $counters['debit_note']->padding_length ?? 5) }}"
                                                   min="1" max="10">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="finance-form-label small">Reset Period</label>
                                        <select name="counters[debit_note][reset_period]" class="finance-form-control form-control-sm">
                                            <option value="never" {{ ($counters['debit_note']->reset_period ?? 'never') == 'never' ? 'selected' : '' }}>Never</option>
                                            <option value="yearly" {{ ($counters['debit_note']->reset_period ?? 'never') == 'yearly' ? 'selected' : '' }}>Yearly</option>
                                            <option value="monthly" {{ ($counters['debit_note']->reset_period ?? 'never') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                        </select>
                                    </div>
                                    <div class="small text-muted">
                                        <strong>Preview:</strong> {{ ($counters['debit_note']->prefix ?? 'DN') }}-{{ str_pad(($counters['debit_note']->next_number ?? 1), ($counters['debit_note']->padding_length ?? 5), '0', STR_PAD_LEFT) }}{{ $counters['debit_note']->suffix ?? '' }}
                                    </div>
                                </div>
                            </div>

                            {{-- Discount Number --}}
                            <div class="col-md-6 col-lg-4">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="fw-bold mb-3"><i class="bi bi-tag text-danger me-2"></i> Discount Number</h6>
                                    <div class="mb-3">
                                        <label class="finance-form-label small">Prefix</label>
                                        <input type="text" 
                                               name="counters[discount][prefix]" 
                                               class="finance-form-control form-control-sm" 
                                               value="{{ old('counters.discount.prefix', $counters['discount']->prefix ?? 'DISC') }}"
                                               maxlength="20">
                                    </div>
                                    <div class="mb-3">
                                        <label class="finance-form-label small">Suffix</label>
                                        <input type="text" 
                                               name="counters[discount][suffix]" 
                                               class="finance-form-control form-control-sm" 
                                               value="{{ old('counters.discount.suffix', $counters['discount']->suffix ?? '') }}"
                                               maxlength="20">
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="finance-form-label small">Starting Number</label>
                                            <input type="number" 
                                                   name="counters[discount][next_number]" 
                                                   class="finance-form-control form-control-sm" 
                                                   value="{{ old('counters.discount.next_number', $counters['discount']->next_number ?? 1) }}"
                                                   min="1">
                                        </div>
                                        <div class="col-6">
                                            <label class="finance-form-label small">Format (Digits)</label>
                                            <input type="number" 
                                                   name="counters[discount][padding_length]" 
                                                   class="finance-form-control form-control-sm" 
                                                   value="{{ old('counters.discount.padding_length', $counters['discount']->padding_length ?? 5) }}"
                                                   min="1" max="10">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="finance-form-label small">Reset Period</label>
                                        <select name="counters[discount][reset_period]" class="finance-form-control form-control-sm">
                                            <option value="never" {{ ($counters['discount']->reset_period ?? 'never') == 'never' ? 'selected' : '' }}>Never</option>
                                            <option value="yearly" {{ ($counters['discount']->reset_period ?? 'never') == 'yearly' ? 'selected' : '' }}>Yearly</option>
                                            <option value="monthly" {{ ($counters['discount']->reset_period ?? 'never') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                        </select>
                                    </div>
                                    <div class="small text-muted">
                                        <strong>Preview:</strong> {{ ($counters['discount']->prefix ?? 'DISC') }}-{{ str_pad(($counters['discount']->next_number ?? 1), ($counters['discount']->padding_length ?? 5), '0', STR_PAD_LEFT) }}{{ $counters['discount']->suffix ?? '' }}
                                    </div>
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

