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

    /**
     * Create a POS order for a student from an explicit item list (parent app / admin buy-on-behalf).
     *
     * @param  array<int, array{product_id:int, quantity:int, variant_id?:int|null, requirement_template_id?:int|null}>  $items
     * @param  array{parent_id?:int|null, user_id?:int|null, notes?:string|null, payment_method?:string|null, placed_by?:string|null}  $meta
     */
    public function createOrderForStudent(Student $student, array $items, array $meta = []): Order
    {
        if ($items === []) {
            throw new \InvalidArgumentException('Order must include at least one item.');
        }

        return DB::transaction(function () use ($student, $items, $meta) {
            $hasUniforms = false;
            $hasStationery = false;
            $built = [];

            foreach ($items as $row) {
                $product = Product::findOrFail((int) $row['product_id']);
                $variant = ! empty($row['variant_id']) ? ProductVariant::find((int) $row['variant_id']) : null;
                $qty = max(1, (int) ($row['quantity'] ?? 1));
                $unitPrice = (float) $product->base_price + (float) ($variant?->price_adjustment ?? 0);

                if ($product->type === 'uniform') {
                    $hasUniforms = true;
                } else {
                    $hasStationery = true;
                }

                $built[] = [
                    'product' => $product,
                    'variant' => $variant,
                    'requirement_template_id' => $row['requirement_template_id'] ?? null,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'total' => $qty * $unitPrice,
                    'product_name' => $product->name,
                    'variant_name' => $variant ? trim(($variant->name ?: $variant->variant_type).': '.$variant->value, ': ') : null,
                ];
            }

            $orderType = 'stationery';
            if ($hasUniforms && $hasStationery) {
                $orderType = 'mixed';
            } elseif ($hasUniforms) {
                $orderType = 'uniform';
            }

            $subtotal = array_sum(array_column($built, 'total'));

            $order = Order::create([
                'student_id' => $student->id,
                'parent_id' => $meta['parent_id'] ?? $student->parent_id,
                'user_id' => $meta['user_id'] ?? null,
                'order_type' => $orderType,
                'subtotal' => $subtotal,
                'discount_amount' => 0,
                'total_amount' => $subtotal,
                'balance' => $subtotal,
                'payment_method' => $meta['payment_method'] ?? 'brian_paybill',
                'notes' => $meta['notes'] ?? null,
                'status' => 'pending',
                'payment_status' => 'pending',
            ]);

            foreach ($built as $item) {
                /** @var Product $product */
                $product = $item['product'];
                $variant = $item['variant'];

                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'variant_id' => $variant?->id,
                    'requirement_template_id' => $item['requirement_template_id'],
                    'product_name' => $item['product_name'],
                    'variant_name' => $item['variant_name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total'],
                    'fulfillment_status' => 'pending',
                ]);

                if ($item['requirement_template_id']) {
                    $this->linkOrderToRequirement($order, $orderItem, (int) $item['requirement_template_id'], $student->id);
                }

                $this->adjustStockForItem($product, $variant, (int) $item['quantity']);
            }

            $order->calculateTotals();
            $order->load(['items', 'student.classroom', 'parent']);

            if (! empty($meta['transaction_code'])) {
                $this->confirmPayment($order, (string) $meta['transaction_code'], $meta['payment_method'] ?? 'brian_paybill');
            } else {
                $this->sendOrderConfirmation($order, false);
            }

            return $order->fresh(['items', 'student.classroom', 'parent']);
        });
    }

    public function confirmPayment(Order $order, string $transactionCode, string $paymentMethod = 'brian_paybill'): Order
    {
        $code = strtoupper(trim($transactionCode));
        if ($code === '') {
            throw new \InvalidArgumentException('Transaction code is required.');
        }

        $duplicate = Order::query()
            ->where('id', '!=', $order->id)
            ->where('payment_status', 'paid')
            ->where('payment_reference', $code)
            ->exists();
        if ($duplicate) {
            throw new \InvalidArgumentException('This transaction code was already used on another order.');
        }

        return DB::transaction(function () use ($order, $code, $paymentMethod) {
            $order->markAsPaid($paymentMethod, $code);
            $order->status = $order->status === 'pending' ? 'processing' : $order->status;
            $order->save();

            foreach ($order->items as $item) {
                if ($item->requirement_template_id && $order->student_id) {
                    $this->linkOrderToRequirement($order, $item, (int) $item->requirement_template_id, (int) $order->student_id);
                }
            }

            $order->load(['items', 'student.classroom', 'parent']);
            $this->sendOrderConfirmation($order, true);

            return $order;
        });
    }

    public function paymentInstructions(): array
    {
        return [
            'paybill' => config('pos.brian_paybill', '247247'),
            'account' => config('pos.brian_account', '0708225397'),
            'contact_name' => config('pos.brian_name', 'Brian'),
            'contact_whatsapp' => config('pos.brian_whatsapp', '0708225397'),
            'note' => 'Pay via M-Pesa Paybill, then paste the transaction code to confirm your order. Do not use school fee payment methods for Brian-managed buys.',
        ];
    }

    protected function adjustStockForItem(Product $product, ?ProductVariant $variant, int $quantity): void
    {
        if (! $product->track_stock) {
            return;
        }

        $availableStock = $variant ? $variant->stock_quantity : $product->stock_quantity;

        if ($availableStock >= $quantity) {
            if ($variant) {
                $variant->stock_quantity = max(0, $variant->stock_quantity - $quantity);
                $variant->save();
            } else {
                $product->stock_quantity = max(0, $product->stock_quantity - $quantity);
                $product->save();
            }

            return;
        }

        if ($product->allow_overselling) {
            $product->recordOversell($quantity);
            if ($variant) {
                $variant->stock_quantity = $variant->stock_quantity - $quantity;
                $variant->save();
            } else {
                $product->stock_quantity = $product->stock_quantity - $quantity;
                $product->save();
            }

            return;
        }

        if (! $product->allow_backorders) {
            throw new \Exception("Insufficient stock for {$product->name}");
        }
    }

    protected function sendOrderConfirmation(Order $order, bool $paid = false): void
    {
        $order->loadMissing(['items', 'student.classroom', 'parent']);
        $student = $order->student;

        $orderSummary = $this->buildOrderSummaryText($order, $paid);

        try {
            if ($student) {
                $messageTemplate = "Dear {{parent_name}},\n\n";
                $messageTemplate .= $paid
                    ? "Your order has been received and payment confirmed for {$student->first_name} {$student->last_name}.\n\n"
                    : "Order placed for {$student->first_name} {$student->last_name}:\n\n";
                $messageTemplate .= $orderSummary;
                if ($paid) {
                    $messageTemplate .= "\n\nThe specified items will be availed in school.";
                }
                $messageTemplate .= "\n\nThank you!";

                $parentNotify = app(\App\Services\ParentSchoolNotificationService::class);
                $parentNotify->sendSmsTemplateToStudentParents($student, $messageTemplate, 'Order Confirmation');
                $parentNotify->sendEmailTemplateToStudentParents(
                    $student,
                    'Order Confirmation - '.$order->order_number,
                    $messageTemplate
                );

                $waBody = str_replace('{{parent_name}}', 'Parent', $messageTemplate);
                $parentNotify->sendWhatsAppTemplateToStudentParents($student, $waBody, 'Order Confirmation');
            }

            if ($paid) {
                $this->notifyBrianOfPaidOrder($order);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send order confirmation', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function notifyBrianOfPaidOrder(Order $order): void
    {
        $brian = config('pos.brian_whatsapp');
        if (! $brian) {
            return;
        }

        $student = $order->student;
        $className = $student?->classroom?->name ?? 'N/A';
        $who = $order->user_id ? 'Admin (on behalf)' : 'Parent';

        $msg = "POS order received\n";
        $msg .= "Order: {$order->order_number}\n";
        if ($student) {
            $msg .= "Student: {$student->first_name} {$student->last_name} ({$className})\n";
        }
        $msg .= "Placed by: {$who}\n";
        $msg .= "Txn: {$order->payment_reference}\n";
        $msg .= "Total: KES ".number_format((float) $order->total_amount, 2)."\n\nItems:\n";
        foreach ($order->items as $item) {
            $msg .= "- {$item->product_name}";
            if ($item->variant_name) {
                $msg .= " ({$item->variant_name})";
            }
            $msg .= " x{$item->quantity}\n";
        }

        try {
            app(\App\Services\WhatsAppService::class)->sendMessage((string) $brian, $msg);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to WhatsApp Brian about POS order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function buildOrderSummaryText(Order $order, bool $paid = false): string
    {
        $orderSummary = "Order #{$order->order_number}\n\n";
        $orderSummary .= "Items Ordered:\n";
        foreach ($order->items as $item) {
            $orderSummary .= "- {$item->product_name}";
            if ($item->variant_name) {
                $orderSummary .= " ({$item->variant_name})";
            }
            $orderSummary .= ' x'.$item->quantity.' = KES '.number_format((float) $item->total_price, 2)."\n";
        }
        $orderSummary .= "\nSubtotal: KES ".number_format((float) $order->subtotal, 2)."\n";
        if ((float) $order->discount_amount > 0) {
            $orderSummary .= 'Discount: -KES '.number_format((float) $order->discount_amount, 2)."\n";
        }
        $orderSummary .= 'Total: KES '.number_format((float) $order->total_amount, 2)."\n";

        if ($paid) {
            $orderSummary .= "\nPayment: CONFIRMED";
            if ($order->payment_reference) {
                $orderSummary .= ' ('.$order->payment_reference.')';
            }
        } elseif ($order->payment_method === 'cash') {
            $orderSummary .= "\nPayment Method: Cash (Pay at School)";
        } elseif ($order->payment_method === 'brian_paybill') {
            $pay = $this->paymentInstructions();
            $orderSummary .= "\nPaybill: {$pay['paybill']} Account: {$pay['account']}";
            $orderSummary .= "\nPaste the M-Pesa transaction code in the app to confirm.";
        } else {
            $orderSummary .= "\nPayment Method: ".strtoupper((string) $order->payment_method);
            $orderSummary .= "\nPlease complete payment to confirm your order.";
        }

        return $orderSummary;
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



