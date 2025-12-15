<?php

namespace App\Models\Pos;

use App\Models\Student;
use App\Models\ParentInfo;
use App\Models\User;
use App\Models\PaymentTransaction;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'pos_orders';

    protected $fillable = [
        'order_number', 'student_id', 'parent_id', 'user_id', 'order_type',
        'status', 'payment_status', 'subtotal', 'discount_amount', 'tax_amount',
        'total_amount', 'paid_amount', 'balance', 'payment_method', 'payment_reference',
        'payment_transaction_id', 'notes', 'shipping_address', 'shipping_method',
        'paid_at', 'completed_at'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'paid_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }
        });
    }

    public static function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $date = now()->format('Ymd');
        $lastOrder = static::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastOrder ? (int) substr($lastOrder->order_number, -4) + 1 : 1;

        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function parent()
    {
        return $this->belongsTo(ParentInfo::class, 'parent_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function paymentTransaction()
    {
        return $this->belongsTo(PaymentTransaction::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function studentRequirements()
    {
        return $this->hasMany(\App\Models\StudentRequirement::class, 'pos_order_id');
    }

    public function calculateTotals()
    {
        $subtotal = $this->items->sum('total_price');
        $this->subtotal = $subtotal;
        $this->total_amount = $subtotal - $this->discount_amount + $this->tax_amount;
        $this->balance = $this->total_amount - $this->paid_amount;
        $this->save();
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid' && $this->balance <= 0;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function markAsPaid($paymentMethod = null, $reference = null)
    {
        $this->payment_status = 'paid';
        $this->paid_amount = $this->total_amount;
        $this->balance = 0;
        $this->paid_at = now();

        if ($paymentMethod) {
            $this->payment_method = $paymentMethod;
        }

        if ($reference) {
            $this->payment_reference = $reference;
        }

        $this->save();
    }
}



