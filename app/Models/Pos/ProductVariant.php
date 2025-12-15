<?php

namespace App\Models\Pos;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $table = 'pos_product_variants';

    protected $fillable = [
        'product_id', 'name', 'value', 'variant_type',
        'price_adjustment', 'stock_quantity', 'sku', 'barcode',
        'is_default', 'is_active'
    ];

    protected $casts = [
        'price_adjustment' => 'decimal:2',
        'stock_quantity' => 'integer',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'variant_id');
    }

    public function getFullPrice(): float
    {
        $basePrice = $this->product ? $this->product->base_price : 0;
        return $basePrice + $this->price_adjustment;
    }

    public function isInStock(): bool
    {
        return $this->stock_quantity > 0;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}



