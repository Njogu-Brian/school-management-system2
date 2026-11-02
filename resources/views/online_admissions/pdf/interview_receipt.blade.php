<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Interview Fee Receipt</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        .header { text-align: center; margin-bottom: 24px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header p { margin: 4px 0; color: #555; }
        .box { border: 1px solid #ccc; padding: 16px; border-radius: 4px; }
        .row { margin-bottom: 8px; }
        .label { color: #666; width: 160px; display: inline-block; }
        .amount { font-size: 16px; font-weight: bold; margin-top: 12px; }
        .footer { margin-top: 28px; font-size: 10px; color: #777; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $schoolName }}</h1>
        <p>Interview / Assessment Fee Receipt</p>
    </div>
    <div class="box">
        <div class="row"><span class="label">Receipt No:</span> {{ $receiptNumber }}</div>
        <div class="row"><span class="label">Date:</span> {{ optional($paidAt)->format('d M Y H:i') ?? now()->format('d M Y H:i') }}</div>
        <div class="row"><span class="label">Received from:</span> {{ $payerName }}</div>
        <div class="row"><span class="label">Learner:</span> {{ $childName }}</div>
        <div class="row"><span class="label">Votehead:</span> {{ $voteheadName }}</div>
        <div class="row"><span class="label">M-Pesa / Txn:</span> {{ $mpesaCode }}</div>
        <div class="amount">Amount: KES {{ number_format((float) $amount, 2) }}</div>
    </div>
    <div class="footer">
        This receipt confirms payment of the interview/assessment fee for the admission process.
        The same fee will not be charged again on enrollment.
    </div>
</body>
</html>
