<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $order->order_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 14px; margin: 16px 0 8px; }
        .muted { color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #f3f3f3; }
        .right { text-align: right; }
        .totals td { border: none; }
        .badge { display: inline-block; padding: 2px 8px; background: #e8f5e9; color: #1b5e20; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>{{ $schoolName }}</h1>
    <div class="muted">POS Order Receipt</div>

    <h2>Order {{ $order->order_number }}</h2>
    <p>
        Date: {{ optional($order->paid_at ?? $order->created_at)->format('d M Y H:i') }}<br>
        Status: <span class="badge">{{ strtoupper($order->payment_status) }}</span><br>
        Type: {{ ucfirst($order->order_type) }}
    </p>

    @if($order->student)
        <p>
            <strong>Student:</strong> {{ $order->student->first_name }} {{ $order->student->last_name }}<br>
            <strong>Class:</strong> {{ $order->student->classroom->name ?? '—' }}
        </p>
    @endif

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th class="right">Unit</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>
                        {{ $item->product_name }}
                        @if($item->variant_name)
                            <div class="muted">{{ $item->variant_name }}</div>
                        @endif
                    </td>
                    <td>{{ $item->quantity }}</td>
                    <td class="right">{{ number_format((float) $item->unit_price, 2) }}</td>
                    <td class="right">{{ number_format((float) $item->total_price, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals" style="margin-top: 12px; width: 40%; margin-left: auto;">
        <tr>
            <td>Subtotal</td>
            <td class="right">KES {{ number_format((float) $order->subtotal, 2) }}</td>
        </tr>
        @if((float) $order->discount_amount > 0)
            <tr>
                <td>Discount</td>
                <td class="right">- KES {{ number_format((float) $order->discount_amount, 2) }}</td>
            </tr>
        @endif
        <tr>
            <td><strong>Total</strong></td>
            <td class="right"><strong>KES {{ number_format((float) $order->total_amount, 2) }}</strong></td>
        </tr>
    </table>

    @if($order->payment_reference)
        <p style="margin-top: 16px;">
            <strong>Payment reference:</strong> {{ $order->payment_reference }}<br>
            <strong>Method:</strong> {{ $order->payment_method }}
        </p>
    @endif

    <p class="muted" style="margin-top: 24px;">Thank you. Keep this receipt for your records.</p>
</body>
</html>
