<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - School Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
</head>
<body>
    <nav class="navbar navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="{{ route('pos.shop.public', $link->token) }}">
                <i class="bi bi-shop"></i> School Shop
            </a>
        </div>
    </nav>

    <div class="container my-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <div class="mb-4">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                        </div>
                        <h2 class="mb-3">Order Placed Successfully!</h2>
                        <p class="text-muted mb-4">Your order has been received and is being processed.</p>

                        <div class="card bg-light mb-4">
                            <div class="card-body text-start">
                                <h5 class="mb-3">Order Details</h5>
                                <p><strong>Order Number:</strong> {{ $order->order_number }}</p>
                                <p><strong>Total Amount:</strong> KES {{ number_format($order->total_amount, 2) }}</p>
                                <p><strong>Payment Method:</strong> {{ ucfirst($order->payment_method ?? 'Cash') }}</p>
                                <p><strong>Order Date:</strong> {{ $order->created_at->format('F d, Y H:i') }}</p>

                                @if($order->payment_method === 'cash')
                                    <div class="alert alert-info mt-3">
                                        <i class="bi bi-info-circle"></i> Please complete payment at the school office.
                                    </div>
                                @else
                                    <div class="alert alert-warning mt-3">
                                        <i class="bi bi-clock"></i> Please complete your online payment to confirm your order.
                                    </div>
                                    <a href="{{ route('pos.shop.payment', ['token' => $link->token, 'order' => $order->id]) }}" class="btn btn-primary">
                                        Complete Payment
                                    </a>
                                @endif
                            </div>
                        </div>

                        <div class="d-flex justify-content-center gap-2">
                            <a href="{{ route('pos.shop.public', $link->token) }}" class="btn btn-outline-primary">
                                Continue Shopping
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>



