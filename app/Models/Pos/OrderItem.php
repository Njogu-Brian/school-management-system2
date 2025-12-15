<?php

namespace App\Models\Pos;

use App\Models\RequirementTemplate;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $table = 'pos_order_items';

    protected $fillable = [
        'order_id', 'product_id', 'variant_id', 'requirement_template_id',
        'product_name', 'variant_name', 'quantity', 'unit_price',
        'discount_amount', 'total_price', 'fulfillment_status',
        'quantity_fulfilled', 'notes'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_price' => 'decimal:2',
        'quantity_fulfilled' => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function requirementTemplate()
    {
        return $this->belongsTo(RequirementTemplate::class);
    }

    public function studentRequirements()
    {
        return $this->hasMany(\App\Models\StudentRequirement::class, 'pos_order_item_id');
    }

    public function calculateTotal()
    {
        $this->total_price = ($this->unit_price * $this->quantity) - $this->discount_amount;
        $this->save();
    }

    public function fulfill($quantity = null)
    {
        $quantity = $quantity ?? $this->quantity;
        $this->quantity_fulfilled += $quantity;

        if ($this->quantity_fulfilled >= $this->quantity) {
            $this->fulfillment_status = 'fulfilled';
        } elseif ($this->quantity_fulfilled > 0) {
            $this->fulfillment_status = 'partial';
        }

        $this->save();
    }

    public function isFulfilled(): bool
    {
        return $this->fulfillment_status === 'fulfilled';
    }
}



