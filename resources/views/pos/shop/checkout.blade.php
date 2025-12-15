<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - School Shop</title>
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
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Checkout</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('pos.shop.checkout.process', $link->token) }}">
                            @csrf

                            @if($student)
                                <input type="hidden" name="student_id" value="{{ $student->id }}">
                            @endif
                            @if($parent)
                                <input type="hidden" name="parent_id" value="{{ $parent->id }}">
                            @endif

                            <div class="mb-3">
                                <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                                <select name="payment_method" class="form-select" required>
                                    <option value="cash">Cash (Pay at School)</option>
                                    <option value="mpesa">M-Pesa</option>
                                    <option value="card">Card</option>
                                    <option value="paypal">PayPal</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Shipping Address (Optional)</label>
                                <textarea name="shipping_address" class="form-control" rows="3" placeholder="If different from school address"></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Notes (Optional)</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="Any special instructions"></textarea>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="{{ route('pos.shop.public', $link->token) }}" class="btn btn-light">Continue Shopping</a>
                                <button type="submit" class="btn btn-primary">Place Order</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tbody>
                                    @foreach($cart['items'] as $item)
                                        <tr>
                                            <td>
                                                {{ $item['product_name'] }}
                                                @if($item['variant_name'])
                                                    <div class="small text-muted">{{ $item['variant_name'] }}</div>
                                                @endif
                                                <div class="small text-muted">Qty: {{ $item['quantity'] }}</div>
                                            </td>
                                            <td class="text-end">KES {{ number_format($item['total'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>Subtotal:</th>
                                        <th class="text-end">KES {{ number_format($cart['subtotal'], 2) }}</th>
                                    </tr>
                                    @if($cart['discount_amount'] > 0)
                                        <tr>
                                            <th>Discount:</th>
                                            <th class="text-end text-danger">-KES {{ number_format($cart['discount_amount'], 2) }}</th>
                                        </tr>
                                    @endif
                                    <tr>
                                        <th>Total:</th>
                                        <th class="text-end">KES {{ number_format($cart['total'], 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>



