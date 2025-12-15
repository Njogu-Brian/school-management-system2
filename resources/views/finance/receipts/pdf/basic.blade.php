<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt - {{ $payment->receipt_number ?? 'N/A' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .total {
            font-weight: bold;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $school['name'] ?? 'School Name' }}</h2>
        <p>{{ $school['address'] ?? '' }}</p>
        <p>Receipt Number: {{ $payment->receipt_number ?? 'N/A' }}</p>
    </div>

    <table>
        <tr>
            <th>Student:</th>
            <td>{{ $payment->student->first_name ?? 'N/A' }} {{ $payment->student->last_name ?? '' }}</td>
        </tr>
        <tr>
            <th>Date:</th>
            <td>{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') : date('d M Y') }}</td>
        </tr>
        <tr>
            <th>Amount:</th>
            <td class="total">Ksh {{ number_format($payment->amount, 2) }}</td>
        </tr>
        <tr>
            <th>Payment Method:</th>
            <td>{{ $payment->paymentMethod->name ?? $payment->payment_method ?? 'N/A' }}</td>
        </tr>
    </table>

    <p style="margin-top: 30px; text-align: center;">
        <strong>Thank You for Your Payment!</strong>
    </p>
</body>
</html>

