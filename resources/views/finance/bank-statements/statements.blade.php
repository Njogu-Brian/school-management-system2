@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'Imported Bank Statements',
        'icon' => 'bi bi-file-earmark-pdf',
        'subtitle' => 'View all imported bank and M-Pesa statements with payment summaries',
        'actions' => '<a href="' . route('finance.bank-statements.create') . '" class="btn btn-finance btn-finance-primary"><i class="bi bi-upload"></i> Upload Statement</a>
                      <a href="' . route('finance.bank-statements.index') . '" class="btn btn-finance btn-finance-secondary"><i class="bi bi-list"></i> View Transactions</a>'
    ])

    @include('finance.invoices.partials.alerts')

    <!-- Summary Stats -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small">Total Statements</p>
                            <h4 class="mb-0">{{ $statements->total() }}</h4>
                        </div>
                        <div class="ms-3">
                            <i class="bi bi-file-earmark-pdf text-primary" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small">Total Transactions</p>
                            <h4 class="mb-0">{{ $statements->sum('total_transactions') }}</h4>
                        </div>
                        <div class="ms-3">
                            <i class="bi bi-list-check text-info" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small">Total Amount</p>
                            <h4 class="mb-0">Ksh {{ number_format($statements->sum('total_amount'), 2) }}</h4>
                        </div>
                        <div class="ms-3">
                            <i class="bi bi-cash-stack text-success" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small">Collected</p>
                            <h4 class="mb-0">{{ $statements->sum('collected_count') }}</h4>
                        </div>
                        <div class="ms-3">
                            <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statements List -->
    <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
        <div class="card-body">
            <h5 class="card-title mb-4">
                <i class="bi bi-folder2-open"></i> Imported Statements
            </h5>

            @forelse($statements as $statement)
                @php
                    // Get first transaction from this statement to access details
                    $firstTransaction = \App\Models\BankStatementTransaction::where('statement_file_path', $statement->statement_file_path)->first();
                    $bankAccount = $statement->bank_account_id ? ($bankAccounts[$statement->bank_account_id] ?? null) : null;
                    
                    // Calculate percentages
                    $totalTransactions = $statement->total_transactions;
                    $collectedPercentage = $totalTransactions > 0 ? ($statement->collected_count / $totalTransactions) * 100 : 0;
                    $confirmedPercentage = $totalTransactions > 0 ? ($statement->confirmed_count / $totalTransactions) * 100 : 0;
                    $draftPercentage = $totalTransactions > 0 ? ($statement->draft_count / $totalTransactions) * 100 : 0;
                    $archivedPercentage = $totalTransactions > 0 ? ($statement->archived_count / $totalTransactions) * 100 : 0;
                @endphp

                <div class="border rounded-3 p-4 mb-3" style="background-color: #f8f9fa;">
                    <div class="row">
                        <!-- Statement Info -->
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="d-flex align-items-start">
                                <div class="me-3">
                                    <i class="bi bi-file-earmark-pdf text-danger" style="font-size: 2.5rem;"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">
                                        @if($statement->bank_type === 'mpesa')
                                            <span class="badge bg-success me-2">M-PESA</span>
                                        @elseif($statement->bank_type === 'equity')
                                            <span class="badge bg-primary me-2">Equity Bank</span>
                                        @else
                                            <span class="badge bg-secondary me-2">{{ ucfirst($statement->bank_type ?? 'Unknown') }}</span>
                                        @endif
                                        Statement
                                    </h6>
                                    @if($bankAccount)
                                        <p class="mb-1 small text-muted">
                                            <i class="bi bi-bank"></i> {{ $bankAccount->name }}
                                            <br><small class="text-muted">{{ $bankAccount->account_number }}</small>
                                        </p>
                                    @endif
                                    <p class="mb-0 small text-muted">
                                        <i class="bi bi-calendar"></i> Uploaded: {{ \Carbon\Carbon::parse($statement->uploaded_at)->format('d M Y, h:i A') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Transaction Stats -->
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="mb-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted">Total Transactions</small>
                                    <strong>{{ $statement->total_transactions }}</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted">Total Amount</small>
                                    <strong class="text-success">Ksh {{ number_format($statement->total_amount, 2) }}</strong>
                                </div>
                            </div>

                            <hr class="my-2">

                            <div class="small">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-success"><i class="bi bi-check-circle-fill"></i> Collected</span>
                                    <strong>{{ $statement->collected_count }}</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-primary"><i class="bi bi-check-circle"></i> Confirmed</span>
                                    <strong>{{ $statement->confirmed_count }}</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-warning"><i class="bi bi-file-earmark"></i> Draft</span>
                                    <strong>{{ $statement->draft_count }}</strong>
                                </div>
                                @if($statement->archived_count > 0)
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-muted"><i class="bi bi-archive"></i> Archived</span>
                                        <strong>{{ $statement->archived_count }}</strong>
                                    </div>
                                @endif
                                @if($statement->duplicate_count > 0)
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-danger"><i class="bi bi-exclamation-triangle"></i> Duplicate</span>
                                        <strong>{{ $statement->duplicate_count }}</strong>
                                    </div>
                                @endif
                                @if($statement->rejected_count > 0)
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-danger"><i class="bi bi-x-circle"></i> Rejected</span>
                                        <strong>{{ $statement->rejected_count }}</strong>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Progress & Actions -->
                        <div class="col-md-4">
                            <!-- Progress Bars -->
                            <div class="mb-3">
                                <small class="text-muted d-block mb-1">Payment Status</small>
                                <div class="progress" style="height: 25px;">
                                    @if($statement->collected_count > 0)
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: {{ $collectedPercentage }}%"
                                             title="Collected: {{ $statement->collected_count }}">
                                            @if($collectedPercentage > 10)
                                                <small>{{ $statement->collected_count }}</small>
                                            @endif
                                        </div>
                                    @endif
                                    @if($statement->confirmed_count > 0)
                                        <div class="progress-bar bg-primary" role="progressbar" 
                                             style="width: {{ $confirmedPercentage }}%"
                                             title="Confirmed: {{ $statement->confirmed_count }}">
                                            @if($confirmedPercentage > 10)
                                                <small>{{ $statement->confirmed_count }}</small>
                                            @endif
                                        </div>
                                    @endif
                                    @if($statement->draft_count > 0)
                                        <div class="progress-bar bg-warning" role="progressbar" 
                                             style="width: {{ $draftPercentage }}%"
                                             title="Draft: {{ $statement->draft_count }}">
                                            @if($draftPercentage > 10)
                                                <small>{{ $statement->draft_count }}</small>
                                            @endif
                                        </div>
                                    @endif
                                    @if($statement->archived_count > 0)
                                        <div class="progress-bar bg-secondary" role="progressbar" 
                                             style="width: {{ $archivedPercentage }}%"
                                             title="Archived: {{ $statement->archived_count }}">
                                            @if($archivedPercentage > 5)
                                                <small>{{ $statement->archived_count }}</small>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex flex-column gap-2">
                                @if($firstTransaction)
                                    <a href="{{ route('finance.bank-statements.view-pdf', $firstTransaction) }}" 
                                       target="_blank" 
                                       class="btn btn-finance btn-finance-danger btn-sm">
                                        <i class="bi bi-file-pdf"></i> View PDF Statement
                                    </a>
                                    <a href="{{ route('finance.bank-statements.download-pdf', $firstTransaction) }}" 
                                       class="btn btn-finance btn-finance-secondary btn-sm">
                                        <i class="bi bi-download"></i> Download PDF
                                    </a>
                                @endif
                                <a href="{{ route('finance.bank-statements.index', ['statement_file' => $statement->statement_file_path]) }}" 
                                   class="btn btn-finance btn-finance-primary btn-sm">
                                    <i class="bi bi-list-ul"></i> View Transactions
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 4rem; color: #dee2e6;"></i>
                    <p class="text-muted mt-3">No statements imported yet</p>
                    <a href="{{ route('finance.bank-statements.create') }}" class="btn btn-finance btn-finance-primary">
                        <i class="bi bi-upload"></i> Upload Your First Statement
                    </a>
                </div>
            @endforelse
        </div>

        @if($statements->hasPages())
            <div class="card-footer bg-transparent border-0">
                <div class="d-flex justify-content-center">
                    {{ $statements->links() }}
                </div>
            </div>
        @endif
    </div>
@endsection

