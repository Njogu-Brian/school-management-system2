@extends('layouts.app')

@section('content')
<div class="finance-page">
    <div class="finance-shell">
        @include('finance.partials.header', [
            'title' => $extraIncome->name,
            'icon' => 'bi bi-piggy-bank',
            'subtitle' => $extraIncome->kindLabel()
                . ' · Term ' . $extraIncome->term . ' ' . $extraIncome->year
                . ' · Ksh ' . number_format($extraIncome->amount, 2)
                . ($extraIncome->classroom ? ' · ' . $extraIncome->classroom->name : ''),
            'actions' => '<a href="' . route('finance.extra-income.edit', $extraIncome) . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-pencil"></i> Edit</a>'
                . '<a href="' . route('finance.extra-income.index') . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-arrow-left"></i> All extra income</a>',
        ])

        @include('finance.invoices.partials.alerts')

        <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mb-4">
            <div class="finance-card-body p-4">
                @if($extraIncome->isSwimming())
                    <p class="mb-0">
                        When you split a fees payment, the amount you assign to this swimming charge is credited to that child's swimming wallet.
                        @if($extraIncome->classroom)
                            Only {{ $extraIncome->classroom->name }} can use it.
                        @else
                            Any student can use it.
                        @endif
                    </p>
                @else
                    <p class="mb-3">
                        Students in {{ $extraIncome->classroom->name ?? 'the class' }} can have part of a fees payment applied to this {{ strtolower($extraIncome->kindLabel()) }}.
                        The amount is posted on their term invoice as <strong>{{ $extraIncome->votehead->name ?? $extraIncome->name }}</strong>, separate from school fees.
                    </p>
                    @if($extraIncome->classroom_id)
                        <form method="POST" action="{{ route('finance.extra-income.charge', $extraIncome) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-finance btn-finance-primary">
                                <i class="bi bi-receipt"></i> Charge this class
                            </button>
                        </form>
                    @endif
                @endif
            </div>
        </div>

        @if($students->isNotEmpty())
            <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mb-4">
                <div class="finance-card-header">
                    <h5 class="mb-0">{{ $extraIncome->classroom->name }}</h5>
                </div>
                <div class="finance-card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-modern align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Student</th>
                                    <th class="text-end">Charged</th>
                                    <th class="text-end">Paid</th>
                                    <th class="text-end">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($students as $student)
                                    @php
                                        $line = $invoiceItems[$student->id] ?? null;
                                        $charged = $line ? (float) $line->amount : (float) ($optionalFees[$student->id]->amount ?? 0);
                                        $paid = $line ? (float) $line->getAllocatedAmount() : (float) ($received[$student->id] ?? 0);
                                        if ($extraIncome->isSwimming()) {
                                            $paid = (float) ($received[$student->id] ?? 0);
                                            $charged = (float) $extraIncome->amount;
                                        }
                                        $balance = max(0, $charged - $paid);
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $student->full_name }}</div>
                                            <div class="small text-muted">{{ $student->admission_number }}</div>
                                        </td>
                                        <td class="text-end">{{ $charged > 0 ? 'Ksh ' . number_format($charged, 2) : '—' }}</td>
                                        <td class="text-end">Ksh {{ number_format($paid, 2) }}</td>
                                        <td class="text-end">{{ $charged > 0 ? 'Ksh ' . number_format($balance, 2) : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @elseif($extraIncome->isSwimming())
            <div class="alert alert-info">Swimming splits are listed on each bank or M-Pesa transaction after you apply them.</div>
        @endif

        <button type="button" class="btn btn-finance btn-finance-danger mt-3" data-bs-toggle="modal" data-bs-target="#deleteExtraIncomeModal">
            <i class="bi bi-trash"></i> Delete
        </button>

        <div class="modal fade" id="deleteExtraIncomeModal" tabindex="-1" aria-labelledby="deleteExtraIncomeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteExtraIncomeModalLabel">Delete {{ $extraIncome->name }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        If payments have already been split onto this activity, it will be closed instead of deleted. Closed activities no longer appear in the split list.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-finance btn-finance-outline" data-bs-dismiss="modal">Cancel</button>
                        <form method="POST" action="{{ route('finance.extra-income.destroy', $extraIncome) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-finance btn-finance-danger">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
