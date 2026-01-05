<?php

namespace App\Services;

use App\Models\Pos\Product;
use App\Models\Pos\ProductVariant;
use App\Models\Pos\Order;
use App\Models\Pos\OrderItem;
use App\Models\Pos\Discount;
use App\Models\Pos\PublicShopLink;
use App\Models\Student;
use App\Models\RequirementTemplate;
use App\Models\StudentRequirement;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class PosService
{
    public function getCart(string $token): array
    {
        $cartKey = "pos_cart_{$token}";
        $cart = Session::get($cartKey, [
            'items' => [],
            'discount_code' => null,
            'discount_id' => null,
            'subtotal' => 0,
            'discount_amount' => 0,
            'total' => 0,
        ]);

        // Recalculate totals
        $this->calculateCartTotals($cart);

        return $cart;
    }

    public function addToCart(string $token, array $itemData): array
    {
        $cart = $this->getCart($token);

        $product = Product::findOrFail($itemData['product_id']);
        $variant = $itemData['variant_id'] ? ProductVariant::find($itemData['variant_id']) : null;

        $itemKey = $this->generateCartItemKey($itemData['product_id'], $itemData['variant_id'], $itemData['requirement_template_id'] ?? null);

        // Check if item already exists in cart
        $existingItemIndex = null;
        foreach ($cart['items'] as $index => $item) {
            if ($item['key'] === $itemKey) {
                $existingItemIndex = $index;
                break;
            }
        }

        $unitPrice = $variant ? $variant->getFullPrice() : $product->base_price;

        if ($existingItemIndex !== null) {
            // Update quantity
            $cart['items'][$existingItemIndex]['quantity'] += $itemData['quantity'];
            $cart['items'][$existingItemIndex]['total'] = $cart['items'][$existingItemIndex]['quantity'] * $unitPrice;
        } else {
            // Add new item
            $cart['items'][] = [
                'key' => $itemKey,
                'product_id' => $itemData['product_id'],
                'variant_id' => $itemData['variant_id'] ?? null,
                'requirement_template_id' => $itemData['requirement_template_id'] ?? null,
                'product_name' => $product->name,
                'variant_name' => $variant ? $variant->name . ': ' . $variant->value : null,
                'quantity' => $itemData['quantity'],
                'unit_price' => $unitPrice,
                'total' => $itemData['quantity'] * $unitPrice,
            ];
        }

        $this->calculateCartTotals($cart);
        $this->saveCart($token, $cart);

        return $cart;
    }

    public function updateCartItem(string $token, string $itemKey, int $quantity): array
    {
        $cart = $this->getCart($token);

        foreach ($cart['items'] as $index => $item) {
            if ($item['key'] === $itemKey) {
                if ($quantity <= 0) {
                    unset($cart['items'][$index]);
                } else {
                    $cart['items'][$index]['quantity'] = $quantity;
                    $cart['items'][$index]['total'] = $quantity * $item['unit_price'];
                }
                break;
            }
        }

        $cart['items'] = array_values($cart['items']); // Reindex array
        $this->calculateCartTotals($cart);
        $this->saveCart($token, $cart);

        return $cart;
    }

    public function removeFromCart(string $token, string $itemKey): array
    {
        $cart = $this->getCart($token);

        $cart['items'] = array_filter($cart['items'], function($item) use ($itemKey) {
            return $item['key'] !== $itemKey;
        });

        $cart['items'] = array_values($cart['items']); // Reindex array
        $this->calculateCartTotals($cart);
        $this->saveCart($token, $cart);

        return $cart;
    }

    public function applyDiscount(string $token, Discount $discount): array
    {
        $cart = $this->getCart($token);

        if (!$discount->isValid()) {
            throw new \Exception('Invalid or expired discount code');
        }

        $cart['discount_code'] = $discount->code;
        $cart['discount_id'] = $discount->id;

        $this->calculateCartTotals($cart, $discount);
        $this->saveCart($token, $cart);

        return $cart;
    }

    public function clearCart(string $token): void
    {
        $cartKey = "pos_cart_{$token}";
        Session::forget($cartKey);
    }

    public function createOrderFromCart(string $token, array $orderData, PublicShopLink $link): Order
    {
        $cart = $this->getCart($token);

        if (empty($cart['items'])) {
            throw new \Exception('Cart is empty');
        }

        return DB::transaction(function () use ($cart, $orderData, $link) {
            // Determine order type
            $orderType = 'stationery';
            $hasUniforms = false;
            $hasStationery = false;

            foreach ($cart['items'] as $item) {
                $product = Product::find($item['product_id']);
                if ($product) {
                    if ($product->type === 'uniform') {
                        $hasUniforms = true;
                    } else {
                        $hasStationery = true;
                    }
                }
            }

            if ($hasUniforms && $hasStationery) {
                $orderType = 'mixed';
            } elseif ($hasUniforms) {
                $orderType = 'uniform';
            }

            // Create order
            $order = Order::create([
                'student_id' => $orderData['student_id'] ?? $link->student_id,
                'parent_id' => $orderData['parent_id'] ?? ($link->student ? $link->student->parent_id : null),
                'order_type' => $orderType,
                'subtotal' => $cart['subtotal'],
                'discount_amount' => $cart['discount_amount'],
                'total_amount' => $cart['total'],
                'balance' => $cart['total'],
                'payment_method' => $orderData['payment_method'],
                'shipping_address' => $orderData['shipping_address'] ?? null,
                'notes' => $orderData['notes'] ?? null,
                'status' => 'pending',
                'payment_status' => 'pending',
            ]);

            // Create order items
            foreach ($cart['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $variant = $item['variant_id'] ? ProductVariant::find($item['variant_id']) : null;

                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'],
                    'requirement_template_id' => $item['requirement_template_id'] ?? null,
                    'product_name' => $item['product_name'],
                    'variant_name' => $item['variant_name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total'],
                    'fulfillment_status' => 'pending',
                ]);

                // If linked to requirement template, create/update student requirement
                if ($item['requirement_template_id'] && $order->student_id) {
                    $this->linkOrderToRequirement($order, $orderItem, $item['requirement_template_id'], $order->student_id);
                }

                // Update stock and handle overselling
                if ($product->track_stock) {
                    $availableStock = $product->stock_quantity;
                    if ($variant) {
                        $availableStock = $variant->stock_quantity;
                    }

                    if ($availableStock >= $item['quantity']) {
                        // Sufficient stock - deduct normally
                        if ($variant) {
                            $variant->stock_quantity = max(0, $variant->stock_quantity - $item['quantity']);
                            $variant->save();
                        } else {
                            $product->stock_quantity = max(0, $product->stock_quantity - $item['quantity']);
                            $product->save();
                        }
                    } elseif ($product->allow_overselling) {
                        // Overselling allowed - record it
                        $product->recordOversell($item['quantity']);
                        // Still deduct what's available (goes to negative)
                        if ($variant) {
                            $variant->stock_quantity = $variant->stock_quantity - $item['quantity'];
                            $variant->save();
                        } else {
                            $product->stock_quantity = $product->stock_quantity - $item['quantity'];
                            $product->save();
                        }
                    } elseif (!$product->allow_backorders) {
                        // No overselling or backorders - should not reach here (checked earlier)
                        throw new \Exception("Insufficient stock for {$product->name}");
                    }
                }
            }

            // Apply discount if exists
            if ($cart['discount_id']) {
                $discount = Discount::find($cart['discount_id']);
                if ($discount) {
                    $discount->incrementUsage();
                }
            }

            $order->calculateTotals();

            // Send order confirmation notification
            $this->sendOrderConfirmation($order);

            return $order;
        });
    }

    protected function sendOrderConfirmation(Order $order): void
    {
        if (!$order->parent && !$order->student?->parent) {
            return;
        }

        $parent = $order->parent ?? $order->student->parent;
        $student = $order->student;
        
        try {
            $commService = app(\App\Services\CommunicationService::class);

            // Build order summary
            $orderSummary = "Order #{$order->order_number}\n\n";
            $orderSummary .= "Items Ordered:\n";
            foreach ($order->items as $item) {
                $orderSummary .= "- {$item->product_name}";
                if ($item->variant_name) {
                    $orderSummary .= " ({$item->variant_name})";
                }
                $orderSummary .= " x{$item->quantity} = KES " . number_format($item->total_price, 2) . "\n";
            }
            $orderSummary .= "\nSubtotal: KES " . number_format($order->subtotal, 2) . "\n";
            if ($order->discount_amount > 0) {
                $orderSummary .= "Discount: -KES " . number_format($order->discount_amount, 2) . "\n";
            }
            $orderSummary .= "Total: KES " . number_format($order->total_amount, 2) . "\n";
            
            if ($order->payment_method === 'cash') {
                $orderSummary .= "\n💳 Payment Method: Cash (Pay at School)";
            } else {
                $orderSummary .= "\n💳 Payment Method: " . strtoupper($order->payment_method);
                $orderSummary .= "\n⚠️ Please complete payment to confirm your order.";
            }

            $message = "Dear Parent,\n\n";
            if ($student) {
                $message .= "Order placed for {$student->first_name} {$student->last_name}:\n\n";
            }
            $message .= $orderSummary;
            $message .= "\n\nThank you for your order!";

            // Send SMS
            if ($parent->phone) {
                $commService->sendSMS('parent', $parent->id, $parent->phone, $message, 'Order Confirmation');
            }

            // Send Email
            if ($parent->email) {
                $htmlMessage = nl2br($message);
                $commService->sendEmail('parent', $parent->id, $parent->email, 'Order Confirmation - ' . $order->order_number, $htmlMessage);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send order confirmation', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function linkOrderToRequirement(Order $order, OrderItem $orderItem, int $templateId, int $studentId): void
    {
        $template = RequirementTemplate::findOrFail($templateId);
        $currentYear = AcademicYear::where('is_active', true)->first();
        $currentTerm = Term::where('is_current', true)->first();

        if (!$currentYear || !$currentTerm) {
            return; // Can't link without active year/term
        }

        $studentRequirement = StudentRequirement::firstOrCreate(
            [
                'student_id' => $studentId,
                'requirement_template_id' => $templateId,
                'academic_year_id' => $currentYear->id,
                'term_id' => $currentTerm->id,
            ],
            [
                'quantity_required' => $template->quantity_per_student,
                'quantity_collected' => 0,
                'quantity_missing' => $template->quantity_per_student,
                'status' => 'pending',
            ]
        );

        // Link to order
        $studentRequirement->pos_order_id = $order->id;
        $studentRequirement->pos_order_item_id = $orderItem->id;
        $studentRequirement->purchased_through_pos = true;

        // If payment is completed, mark as collected
        if ($order->isPaid()) {
            $studentRequirement->quantity_collected = min(
                $studentRequirement->quantity_required,
                $studentRequirement->quantity_collected + $orderItem->quantity
            );
        }

        $studentRequirement->updateStatus();
        $studentRequirement->save();
    }

    protected function calculateCartTotals(array &$cart, ?Discount $discount = null): void
    {
        $subtotal = array_sum(array_column($cart['items'], 'total'));
        $cart['subtotal'] = $subtotal;

        $discountAmount = 0;
        if ($discount) {
            $discountAmount = $discount->calculateDiscount($subtotal, array_sum(array_column($cart['items'], 'quantity')));
        } elseif ($cart['discount_id']) {
            $discount = Discount::find($cart['discount_id']);
            if ($discount && $discount->isValid()) {
                $discountAmount = $discount->calculateDiscount($subtotal, array_sum(array_column($cart['items'], 'quantity')));
            }
        }

        $cart['discount_amount'] = $discountAmount;
        $cart['total'] = max(0, $subtotal - $discountAmount);
    }

    protected function saveCart(string $token, array $cart): void
    {
        $cartKey = "pos_cart_{$token}";
        Session::put($cartKey, $cart);
    }

    protected function generateCartItemKey(int $productId, ?int $variantId, ?int $requirementTemplateId): string
    {
        return "{$productId}_" . ($variantId ?? '0') . "_" . ($requirementTemplateId ?? '0');
    }
}



