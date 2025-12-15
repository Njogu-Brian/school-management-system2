<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Shop - {{ $link->name ?? 'Shop' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .product-card { transition: transform 0.2s; }
        .product-card:hover { transform: translateY(-5px); }
        .cart-badge { position: absolute; top: -5px; right: -5px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-shop"></i> School Shop
            </a>
            <div class="d-flex align-items-center">
                <a href="{{ route('pos.shop.cart.get', $link->token) }}" class="btn btn-light position-relative me-2">
                    <i class="bi bi-cart"></i> Cart
                    @if(isset($cart['items']) && count($cart['items']) > 0)
                        <span class="badge bg-danger rounded-pill cart-badge">{{ count($cart['items']) }}</span>
                    @endif
                </a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row mb-4">
            <div class="col-md-8">
                <h2>Welcome to the School Shop</h2>
                @if($student)
                    <p class="text-muted">Shopping for: {{ $student->first_name }} {{ $student->last_name }} ({{ $student->admission_number }})</p>
                @endif
            </div>
            <div class="col-md-4">
                <form method="GET" class="d-flex">
                    <input type="text" name="search" class="form-control me-2" placeholder="Search products..." value="{{ request('search') }}">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                </form>
            </div>
        </div>

        @if($requirements->count() > 0)
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-list-check"></i> Class Requirements</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">These are the required items for your class. You can add all or select specific items.</p>
                    <div class="row g-3">
                        @foreach($requirements as $requirement)
                            @if($requirement->posProduct && $requirement->posProduct->is_active)
                                <div class="col-md-4">
                                    <div class="card product-card h-100">
                                        @if($requirement->posProduct->images && count($requirement->posProduct->images) > 0)
                                            <img src="{{ asset('storage/' . $requirement->posProduct->images[0]) }}" class="card-img-top" style="height: 200px; object-fit: cover;">
                                        @endif
                                        <div class="card-body">
                                            <h6 class="card-title">{{ $requirement->posProduct->name }}</h6>
                                            <p class="card-text small text-muted">
                                                Required: {{ $requirement->quantity_per_student }} {{ $requirement->unit }}
                                            </p>
                                            <p class="card-text">
                                                <strong>KES {{ number_format($requirement->posProduct->base_price, 2) }}</strong>
                                            </p>
                                            <button class="btn btn-primary btn-sm w-100 add-to-cart" 
                                                    data-product-id="{{ $requirement->posProduct->id }}"
                                                    data-quantity="{{ $requirement->quantity_per_student }}"
                                                    data-requirement-id="{{ $requirement->id }}">
                                                <i class="bi bi-cart-plus"></i> Add to Cart
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">All Products</h5>
                    <div>
                        <select class="form-select form-select-sm d-inline-block" style="width: auto;" onchange="window.location.href=this.value">
                            <option value="{{ route('pos.shop.public', ['token' => $link->token]) }}">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ route('pos.shop.public', ['token' => $link->token, 'category' => $category]) }}" @selected(request('category') === $category)>
                                    {{ $category }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @forelse($products as $product)
                        <div class="col-md-3">
                            <div class="card product-card h-100">
                                @if($product->images && count($product->images) > 0)
                                    <img src="{{ asset('storage/' . $product->images[0]) }}" class="card-img-top" style="height: 200px; object-fit: cover;">
                                @else
                                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                        <i class="bi bi-image" style="font-size: 3rem; color: #ccc;"></i>
                                    </div>
                                @endif
                                <div class="card-body">
                                    <h6 class="card-title">{{ $product->name }}</h6>
                                    @if($product->description)
                                        <p class="card-text small text-muted">{{ Str::limit($product->description, 50) }}</p>
                                    @endif
                                    <p class="card-text">
                                        <strong>KES {{ number_format($product->base_price, 2) }}</strong>
                                    </p>
                                    @if($product->track_stock && $product->stock_quantity <= 0 && !$product->allow_backorders)
                                        <button class="btn btn-secondary btn-sm w-100" disabled>Out of Stock</button>
                                    @else
                                        <button class="btn btn-primary btn-sm w-100 add-to-cart" 
                                                data-product-id="{{ $product->id }}"
                                                data-quantity="1">
                                            <i class="bi bi-cart-plus"></i> Add to Cart
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center py-5">
                            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                            <p class="text-muted mt-3">No products found</p>
                        </div>
                    @endforelse
                </div>
            </div>
            @if($products->hasPages())
                <div class="card-footer">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.dataset.productId;
                const quantity = parseInt(this.dataset.quantity) || 1;
                const requirementId = this.dataset.requirementId || null;

                fetch('{{ route("pos.shop.cart.add", $link->token) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: quantity,
                        requirement_template_id: requirementId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Item added to cart!');
                        location.reload();
                    } else {
                        alert(data.error || 'Failed to add item to cart');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred');
                });
            });
        });
    </script>
</body>
</html>



