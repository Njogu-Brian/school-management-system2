<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Pos\Order;
use App\Models\Pos\OrderItem;
use App\Models\Student;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['student', 'parent', 'user']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('student', function($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('admission_number', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('order_type')) {
            $query->where('order_type', $request->order_type);
        }

        $orders = $query->latest()->paginate(30);

        ActivityLog::log('view', null, 'Viewed POS orders list');

        return view('pos.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load(['items.product', 'items.variant', 'student', 'parent', 'user', 'paymentTransaction']);

        return view('pos.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,processing,completed,cancelled,refunded',
            'notes' => 'nullable|string',
        ]);

        $oldStatus = $order->status;
        $order->status = $validated['status'];

        if ($validated['status'] === 'completed') {
            $order->completed_at = now();
        }

        if ($validated['notes']) {
            $order->notes = ($order->notes ? $order->notes . "\n" : '') . $validated['notes'];
        }

        $order->save();

        ActivityLog::log('update', $order, "Updated order {$order->order_number} status: {$oldStatus} → {$validated['status']}");

        return redirect()->back()
            ->with('success', 'Order status updated successfully.');
    }

    public function fulfillItem(Request $request, Order $order, OrderItem $item)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1|max:' . ($item->quantity - $item->quantity_fulfilled),
        ]);

        DB::transaction(function () use ($item, $validated, $order) {
            $item->fulfill($validated['quantity']);

            // Update stock if product tracks stock
            if ($item->product && $item->product->track_stock) {
                $product = $item->product;
                if ($item->variant) {
                    $variant = $item->variant;
                    $variant->stock_quantity = max(0, $variant->stock_quantity - $validated['quantity']);
                    $variant->save();
                } else {
                    $product->stock_quantity = max(0, $product->stock_quantity - $validated['quantity']);
                    $product->save();
                }
            }

            // Check if all items are fulfilled
            $allFulfilled = $order->items()->where('fulfillment_status', '!=', 'fulfilled')->count() === 0;
            if ($allFulfilled && $order->isPaid()) {
                $order->status = 'completed';
                $order->completed_at = now();
                $order->save();
            }
        });

        ActivityLog::log('update', $item, "Fulfilled order item: {$item->product_name}");

        return redirect()->back()
            ->with('success', 'Order item fulfilled successfully.');
    }

    public function cancel(Order $order)
    {
        if ($order->status === 'completed' || $order->payment_status === 'paid') {
            return redirect()->back()
                ->with('error', 'Cannot cancel a completed or paid order.');
        }

        DB::transaction(function () use ($order) {
            // Restore stock
            foreach ($order->items as $item) {
                if ($item->fulfillment_status !== 'cancelled') {
                    if ($item->product && $item->product->track_stock) {
                        $product = $item->product;
                        if ($item->variant) {
                            $variant = $item->variant;
                            $variant->stock_quantity += $item->quantity_fulfilled;
                            $variant->save();
                        } else {
                            $product->stock_quantity += $item->quantity_fulfilled;
                            $product->save();
                        }
                    }

                    $item->fulfillment_status = 'cancelled';
                    $item->save();
                }
            }

            $order->status = 'cancelled';
            $order->save();
        });

        ActivityLog::log('update', $order, "Cancelled order: {$order->order_number}");

        return redirect()->back()
            ->with('success', 'Order cancelled successfully.');
    }
}



