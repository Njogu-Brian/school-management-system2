<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Pos\PublicShopLink;
use App\Models\Pos\Product;
use App\Models\Pos\Order;
use App\Models\Pos\Discount;
use App\Models\RequirementTemplate;
use App\Models\Student;
use App\Models\Academics\Classroom;
use App\Services\PosService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class PublicShopController extends Controller
{
    protected PosService $posService;

    public function __construct(PosService $posService)
    {
        $this->posService = $posService;
    }

    public function shop(Request $request, $token)
    {
        $link = PublicShopLink::where('token', $token)->firstOrFail();

        if (!$link->isValid()) {
            abort(404, 'This shop link is no longer valid.');
        }

        $link->incrementUsage();

        $student = $link->student;
        $classroom = $link->classroom ?? ($student ? $student->classroom : null);

        // Get products based on link settings
        $query = Product::active();

        if ($link->show_requirements_only && $classroom) {
            // Show only products linked to requirements for this class
            $requirementProductIds = RequirementTemplate::where('classroom_id', $classroom->id)
                ->where('is_available_in_shop', true)
                ->whereNotNull('pos_product_id')
                ->pluck('pos_product_id')
                ->unique();

            $query->whereIn('id', $requirementProductIds);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $products = $query->with('activeVariants')->orderBy('sort_order')->orderBy('name')->paginate(20);

        // Get requirements for the student/class if applicable
        $requirements = collect();
        if ($student || $classroom) {
            $requirementsQuery = RequirementTemplate::with(['requirementType', 'posProduct'])
                ->where('is_available_in_shop', true)
                ->where('is_active', true);

            if ($student) {
                $requirementsQuery->where('classroom_id', $student->classroom_id);
            } elseif ($classroom) {
                $requirementsQuery->where('classroom_id', $classroom->id);
            }

            $requirements = $requirementsQuery->get();
        }

        $categories = Product::active()->distinct()->pluck('category')->filter();
        $cart = $this->posService->getCart($token);

        return view('pos.shop.index', compact('link', 'products', 'requirements', 'categories', 'student', 'classroom', 'cart'));
    }

    public function addToCart(Request $request, $token)
    {
        $link = PublicShopLink::where('token', $token)->firstOrFail();

        if (!$link->isValid()) {
            return response()->json(['error' => 'Invalid shop link'], 404);
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:pos_products,id',
            'variant_id' => 'nullable|exists:pos_product_variants,id',
            'quantity' => 'required|integer|min:1',
            'requirement_template_id' => 'nullable|exists:requirement_templates,id',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        // Check stock
        if ($product->track_stock) {
            $availableStock = $product->stock_quantity;
            if ($validated['variant_id']) {
                $variant = $product->variants()->find($validated['variant_id']);
                if ($variant) {
                    $availableStock = $variant->stock_quantity;
                }
            }

            if ($availableStock < $validated['quantity'] && !$product->allow_backorders) {
                return response()->json(['error' => 'Insufficient stock available'], 400);
            }
        }

        $cart = $this->posService->addToCart($token, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Item added to cart',
            'cart' => $cart
        ]);
    }

    public function updateCart(Request $request, $token)
    {
        $validated = $request->validate([
            'item_key' => 'required|string',
            'quantity' => 'required|integer|min:0',
        ]);

        $cart = $this->posService->updateCartItem($token, $validated['item_key'], $validated['quantity']);

        return response()->json([
            'success' => true,
            'cart' => $cart
        ]);
    }

    public function removeFromCart(Request $request, $token)
    {
        $validated = $request->validate([
            'item_key' => 'required|string',
        ]);

        $cart = $this->posService->removeFromCart($token, $validated['item_key']);

        return response()->json([
            'success' => true,
            'cart' => $cart
        ]);
    }

    public function getCart($token)
    {
        $cart = $this->posService->getCart($token);

        return response()->json($cart);
    }

    public function applyDiscount(Request $request, $token)
    {
        $validated = $request->validate([
            'code' => 'required|string',
        ]);

        $discount = Discount::where('code', $validated['code'])->first();

        if (!$discount || !$discount->isValid()) {
            return response()->json(['error' => 'Invalid or expired discount code'], 400);
        }

        $cart = $this->posService->applyDiscount($token, $discount);

        return response()->json([
            'success' => true,
            'message' => 'Discount applied',
            'cart' => $cart
        ]);
    }

    public function checkout(Request $request, $token)
    {
        $link = PublicShopLink::where('token', $token)->firstOrFail();

        if (!$link->isValid()) {
            abort(404, 'This shop link is no longer valid.');
        }

        $cart = $this->posService->getCart($token);

        if (empty($cart['items'])) {
            return redirect()->route('pos.shop.public', ['token' => $token])
                ->with('error', 'Your cart is empty.');
        }

        $student = $link->student;
        $parent = $student ? $student->parent : null;

        return view('pos.shop.checkout', compact('link', 'cart', 'student', 'parent'));
    }

    public function processCheckout(Request $request, $token)
    {
        $link = PublicShopLink::where('token', $token)->firstOrFail();

        if (!$link->isValid()) {
            return redirect()->back()->with('error', 'Invalid shop link.');
        }

        $validated = $request->validate([
            'student_id' => 'nullable|exists:students,id',
            'parent_id' => 'nullable|exists:parent_info,id',
            'payment_method' => 'required|in:cash,mpesa,card,paypal',
            'shipping_address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $cart = $this->posService->getCart($token);

        if (empty($cart['items'])) {
            return redirect()->back()->with('error', 'Your cart is empty.');
        }

        try {
            $order = $this->posService->createOrderFromCart($token, $validated, $link);

            // Clear cart
            $this->posService->clearCart($token);

            // If payment is online, redirect to payment gateway
            if (in_array($validated['payment_method'], ['mpesa', 'card', 'paypal'])) {
                return redirect()->route('pos.shop.payment', ['token' => $token, 'order' => $order->id]);
            }

            // For cash payments, show order confirmation
            return redirect()->route('pos.shop.order-confirmation', ['token' => $token, 'order' => $order->id])
                ->with('success', 'Order placed successfully. Please complete payment at the school.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to process order: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function orderConfirmation($token, Order $order)
    {
        $link = PublicShopLink::where('token', $token)->firstOrFail();
        $order->load(['items.product', 'items.variant', 'student', 'parent']);

        return view('pos.shop.order-confirmation', compact('link', 'order'));
    }
}



