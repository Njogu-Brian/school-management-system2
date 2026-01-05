<?php

namespace App\Models\Pos;

use App\Models\InventoryItem;
use App\Models\RequirementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $table = 'pos_products';

    protected $fillable = [
        'name', 'sku', 'barcode', 'type', 'inventory_item_id', 'requirement_type_id',
        'description', 'category', 'brand', 'base_price', 'cost_price',
        'stock_quantity', 'min_stock_level', 'track_stock', 'allow_backorders', 'allow_overselling',
        'oversell_count', 'last_oversell_alert_at', 'is_active', 'is_featured', 'is_publicly_visible',
        'sort_order', 'images', 'specifications'
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'min_stock_level' => 'integer',
        'track_stock' => 'boolean',
        'allow_backorders' => 'boolean',
        'allow_overselling' => 'boolean',
        'oversell_count' => 'integer',
        'last_oversell_alert_at' => 'datetime',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_publicly_visible' => 'boolean',
        'sort_order' => 'integer',
        'images' => 'array',
        'specifications' => 'array',
    ];

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function requirementType()
    {
        return $this->belongsTo(RequirementType::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'product_id');
    }

    public function activeVariants()
    {
        return $this->variants()->where('is_active', true);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'product_id');
    }

    public function requirementTemplates()
    {
        return $this->hasMany(\App\Models\RequirementTemplate::class, 'pos_product_id');
    }

    public function isInStock(): bool
    {
        if (!$this->track_stock) {
            return true;
        }

        return $this->stock_quantity > 0;
    }

    public function isLowStock(): bool
    {
        if (!$this->track_stock) {
            return false;
        }

        return $this->stock_quantity <= $this->min_stock_level;
    }

    public function getPriceForVariant($variantId = null): float
    {
        $price = $this->base_price;

        if ($variantId) {
            $variant = $this->variants()->find($variantId);
            if ($variant) {
                $price += $variant->price_adjustment;
            }
        }

        return max(0, $price);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeInStock($query)
    {
        return $query->where(function ($q) {
            $q->where('track_stock', false)
              ->orWhereColumn('stock_quantity', '>', 0);
        });
    }

    public function scopePubliclyVisible($query)
    {
        return $query->where('is_publicly_visible', true);
    }

    /**
     * Check if product can be purchased (in stock or overselling allowed)
     */
    public function canPurchase($quantity = 1): bool
    {
        if (!$this->track_stock) {
            return true;
        }

        if ($this->stock_quantity >= $quantity) {
            return true;
        }

        return $this->allow_overselling;
    }

    /**
     * Record an oversell and alert admin if needed
     */
    public function recordOversell($quantity): void
    {
        if (!$this->allow_overselling) {
            return;
        }

        $this->oversell_count += 1;
        
        // Alert admin if not alerted recently (e.g., not in last hour)
        $shouldAlert = !$this->last_oversell_alert_at || 
                       $this->last_oversell_alert_at->diffInHours(now()) >= 1;
        
        if ($shouldAlert) {
            $this->last_oversell_alert_at = now();
            // TODO: Send notification to admin
            // You can use Laravel notifications or events here
        }
        
        $this->save();
    }

    /**
     * Check if product is out of stock
     */
    public function isOutOfStock(): bool
    {
        if (!$this->track_stock) {
            return false;
        }

        return $this->stock_quantity <= 0;
    }
}



