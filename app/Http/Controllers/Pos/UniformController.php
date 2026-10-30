<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Pos\Product;
use App\Models\Pos\Order;
use App\Models\Pos\OrderItem;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UniformController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::where('type', 'uniform')->with('variants');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $uniforms = $query->orderBy('name')->paginate(30);

        // Get backordered orders
        $backorderedOrders = Order::where('order_type', 'uniform')
            ->whereHas('items', function($q) {
                $q->where('fulfillment_status', 'backordered');
            })
            ->with(['items.product', 'items.variant', 'student'])
            ->latest()
            ->take(10)
            ->get();

        return view('pos.uniforms.index', compact('uniforms', 'backorderedOrders'));
    }

    public function show(Product $uniform)
    {
        if ($uniform->type !== 'uniform') {
            abort(404);
        }

        $uniform->load(['variants', 'orderItems.order']);
        
        // Get orders with this uniform
        $orders = Order::whereHas('items', function($q) use ($uniform) {
            $q->where('product_id', $uniform->id);
        })
        ->with(['items.variant', 'student', 'parent'])
        ->latest()
        ->paginate(20);

        return view('pos.uniforms.show', compact('uniform', 'orders'));
    }

    public function manageSizes(Product $uniform)
    {
        if ($uniform->type !== 'uniform') {
            abort(404);
        }

        $uniform->load('variants');
        return view('pos.uniforms.manage-sizes', compact('uniform'));
    }

    public function updateSizeStock(Request $request, Product $uniform)
    {
        if ($uniform->type !== 'uniform') {
            abort(404);
        }

        $validated = $request->validate([
            'variants' => 'required|array',
            'variants.*.id' => 'required|exists:pos_product_variants,id',
            'variants.*.stock_quantity' => 'required|integer|min:0',
            'variants.*.price_adjustment' => 'nullable|numeric',
            'variants.*.is_active' => 'nullable|boolean',
            'variants.*.variant_type' => 'nullable|in:size,color,other',
            'variants.*.value' => 'nullable|string|max:80',
            'variants.*.name' => 'nullable|string|max:80',
            'new_variants' => 'nullable|array',
            'new_variants.*.variant_type' => 'nullable|in:size,color,other',
            'new_variants.*.value' => 'required_with:new_variants|string|max:80',
            'new_variants.*.name' => 'nullable|string|max:80',
            'new_variants.*.stock_quantity' => 'nullable|integer|min:0',
            'new_variants.*.price_adjustment' => 'nullable|numeric',
        ]);

        DB::transaction(function () use ($uniform, $validated) {
            foreach ($validated['variants'] as $variantData) {
                $variant = $uniform->variants()->find($variantData['id']);
                if ($variant) {
                    $oldStock = $variant->stock_quantity;
                    $variant->stock_quantity = $variantData['stock_quantity'];
                    if (array_key_exists('price_adjustment', $variantData)) {
                        $variant->price_adjustment = $variantData['price_adjustment'];
                    }
                    if (array_key_exists('is_active', $variantData)) {
                        $variant->is_active = (bool) $variantData['is_active'];
                    }
                    if (! empty($variantData['variant_type'])) {
                        $variant->variant_type = $variantData['variant_type'];
                    }
                    if (! empty($variantData['value'])) {
                        $variant->value = $variantData['value'];
                    }
                    if (! empty($variantData['name'])) {
                        $variant->name = $variantData['name'];
                    }
                    $variant->save();

                    // If stock was added, check for backordered items
                    if ($variant->stock_quantity > $oldStock && $variant->stock_quantity > 0) {
                        $this->fulfillBackorders($variant);
                    }
                }
            }

            foreach ($validated['new_variants'] ?? [] as $newVariant) {
                $uniform->variants()->create([
                    'variant_type' => $newVariant['variant_type'] ?? 'size',
                    'name' => $newVariant['name'] ?? ucfirst($newVariant['variant_type'] ?? 'size'),
                    'value' => $newVariant['value'],
                    'stock_quantity' => $newVariant['stock_quantity'] ?? 0,
                    'price_adjustment' => $newVariant['price_adjustment'] ?? 0,
                    'is_active' => true,
                ]);
            }
        });

        ActivityLog::log('update', $uniform, "Updated stock for uniform sizes: {$uniform->name}");

        return redirect()->back()
            ->with('success', 'Stock updated successfully. Backorders have been checked.');
    }

    protected function fulfillBackorders($variant)
    {
        // Find backordered order items for this variant
        $backorderedItems = OrderItem::where('variant_id', $variant->id)
            ->where('fulfillment_status', 'backordered')
            ->whereHas('order', function($q) {
                $q->where('status', '!=', 'cancelled');
            })
            ->orderBy('created_at')
            ->get();

        foreach ($backorderedItems as $item) {
            if ($variant->stock_quantity <= 0) {
                break;
            }

            $quantityNeeded = $item->quantity - $item->quantity_fulfilled;
            $quantityToFulfill = min($quantityNeeded, $variant->stock_quantity);

            if ($quantityToFulfill > 0) {
                $item->fulfill($quantityToFulfill);
                $variant->stock_quantity -= $quantityToFulfill;
                $variant->save();

                // Update order status if all items are fulfilled
                $order = $item->order;
                $allFulfilled = $order->items()->where('fulfillment_status', '!=', 'fulfilled')->count() === 0;
                if ($allFulfilled && $order->isPaid()) {
                    $order->status = 'completed';
                    $order->completed_at = now();
                    $order->save();
                }
            }
        }
    }

    public function backorders(Request $request)
    {
        $query = Order::where('order_type', 'uniform')
            ->whereHas('items', function($q) {
                $q->where('fulfillment_status', 'backordered');
            })
            ->with(['items.product', 'items.variant', 'student', 'parent']);

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

        $orders = $query->latest()->paginate(30);

        return view('pos.uniforms.backorders', compact('orders'));
    }

    public function fulfillBackorder(Request $request, Order $order, OrderItem $item)
    {
        if ($order->order_type !== 'uniform') {
            return redirect()->back()->with('error', 'This is not a uniform order.');
        }

        if ($item->fulfillment_status !== 'backordered') {
            return redirect()->back()->with('error', 'This item is not backordered.');
        }

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1|max:' . ($item->quantity - $item->quantity_fulfilled),
        ]);

        DB::transaction(function () use ($item, $validated, $order) {
            $variant = $item->variant;
            if ($variant && $variant->stock_quantity >= $validated['quantity']) {
                $item->fulfill($validated['quantity']);
                $variant->stock_quantity -= $validated['quantity'];
                $variant->save();

                // Check if all items are fulfilled
                $allFulfilled = $order->items()->where('fulfillment_status', '!=', 'fulfilled')->count() === 0;
                if ($allFulfilled && $order->isPaid()) {
                    $order->status = 'completed';
                    $order->completed_at = now();
                    $order->save();
                }

                ActivityLog::log('update', $item, "Fulfilled backordered item: {$item->product_name}");
            } else {
                throw new \Exception('Insufficient stock to fulfill this backorder.');
            }
        });

        return redirect()->back()
            ->with('success', 'Backorder fulfilled successfully.');
    }
}

