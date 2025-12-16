<!DOCTYPE html>
<html>
<head>
    <title>Fee Statement - {{ $student->full_name }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .student-info { margin-bottom: 20px; }
        .summary { display: flex; justify-content: space-around; margin: 20px 0; }
        .summary-box { border: 1px solid #ddd; padding: 15px; text-align: center; flex: 1; margin: 0 10px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>FEE STATEMENT</h1>
        <h2>{{ config('app.name', 'School Management System') }}</h2>
    </div>
    
    <div class="student-info">
        <p><strong>Student Name:</strong> {{ $student->full_name }}</p>
        <p><strong>Admission Number:</strong> {{ $student->admission_number }}</p>
        <p><strong>Class:</strong> {{ $student->currentClass->name ?? 'N/A' }}</p>
        <p><strong>Academic Year:</strong> {{ $year }} @if($term) | <strong>Term:</strong> {{ $terms->find($term)->name ?? "Term {$term}" }} @endif</p>
        <p><strong>Statement Date:</strong> {{ now()->format('d M Y') }}</p>
    </div>
    
    <div class="summary">
        <div class="summary-box">
            <h3>Total Charges</h3>
            <h2>Ksh {{ number_format($invoices->sum('total'), 2) }}</h2>
        </div>
        <div class="summary-box">
            <h3>Total Payments</h3>
            <h2>Ksh {{ number_format($payments->sum('amount'), 2) }}</h2>
        </div>
        <div class="summary-box">
            <h3>Total Discounts</h3>
            <h2>Ksh {{ number_format($discounts->sum('value'), 2) }}</h2>
        </div>
        <div class="summary-box">
            <h3>Balance</h3>
            <h2>Ksh {{ number_format($invoices->sum('total') - $payments->sum('amount') - $discounts->sum('value'), 2) }}</h2>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Description</th>
                <th>Reference</th>
                <th class="text-end">Debit</th>
                <th class="text-end">Credit</th>
            </tr>
        </thead>
        <tbody>
            @php
                $allTransactions = collect();
                foreach ($invoices as $invoice) {
                    $allTransactions->push([
                        'date' => $invoice->created_at->format('d M Y'),
                        'type' => 'Invoice',
                        'description' => 'Invoice #' . $invoice->invoice_number,
                        'reference' => $invoice->invoice_number,
                        'debit' => $invoice->total,
                        'credit' => 0,
                    ]);
                }
                foreach ($payments as $payment) {
                    $allTransactions->push([
                        'date' => $payment->payment_date->format('d M Y'),
                        'type' => 'Payment',
                        'description' => 'Payment',
                        'reference' => $payment->receipt_number,
                        'debit' => 0,
                        'credit' => $payment->amount,
                    ]);
                }
                foreach ($discounts as $discount) {
                    $allTransactions->push([
                        'date' => $discount->created_at->format('d M Y'),
                        'type' => 'Discount',
                        'description' => $discount->discountTemplate->name ?? 'Discount',
                        'reference' => 'DIS-' . $discount->id,
                        'debit' => 0,
                        'credit' => $discount->value,
                    ]);
                }
                $allTransactions = $allTransactions->sortBy('date');
            @endphp
            
            @foreach($allTransactions as $transaction)
                <tr>
                    <td>{{ $transaction['date'] }}</td>
                    <td>{{ $transaction['type'] }}</td>
                    <td>{{ $transaction['description'] }}</td>
                    <td>{{ $transaction['reference'] }}</td>
                    <td class="text-end">{{ $transaction['debit'] > 0 ? 'Ksh ' . number_format($transaction['debit'], 2) : '—' }}</td>
                    <td class="text-end">{{ $transaction['credit'] > 0 ? 'Ksh ' . number_format($transaction['credit'], 2) : '—' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" class="text-end">Totals:</th>
                <th class="text-end">Ksh {{ number_format($invoices->sum('total'), 2) }}</th>
                <th class="text-end">Ksh {{ number_format($payments->sum('amount') + $discounts->sum('value'), 2) }}</th>
            </tr>
        </tfoot>
    </table>
    
    <div style="margin-top: 30px; text-align: center;">
        <p><em>This is a computer-generated statement. Please contact the finance office for any queries.</em></p>
    </div>
</body>
</html>

