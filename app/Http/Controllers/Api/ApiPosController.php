<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pos\Order;
use App\Models\Pos\Product;
use App\Models\RequirementTemplate;
use App\Models\Student;
use App\Services\PosReceiptService;
use App\Services\PosService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ApiPosController extends Controller
{
    public function __construct(protected PosService $posService)
    {
    }

    public function paymentInstructions()
    {
        return response()->json([
            'success' => true,
            'data' => $this->posService->paymentInstructions(),
        ]);
    }

    public function requirementsCatalog(Request $request, Student $student)
    {
        if (! $this->canAccessStudent($request, $student)) {
            return response()->json(['success' => false, 'message' => 'Not allowed.'], 403);
        }

        $student->load(['classroom']);
        $currentTerm = get_current_term_model();
        $isNewJoiner = $this->isNewJoiner($student, $currentTerm);
        $studentTypes = $isNewJoiner ? ['new', 'both'] : ['existing', 'both'];

        $templates = RequirementTemplate::with(['requirementType', 'posProduct'])
            ->where('is_active', true)
            ->where('is_available_in_shop', true)
            ->whereNotNull('pos_product_id')
            ->whereIn('student_type', $studentTypes)
            ->where(function ($q) use ($student) {
                $q->where('classroom_id', $student->classroom_id)
                    ->orWhereHas('classrooms', fn ($qq) => $qq->where('classrooms.id', $student->classroom_id))
                    ->orWhereNull('classroom_id');
            })
            ->when($currentTerm, fn ($q) => $q->where(function ($qq) use ($currentTerm) {
                $qq->where('term_id', $currentTerm->id)->orWhereNull('term_id');
            }))
            ->orderBy('id')
            ->get();

        $items = $templates->map(function (RequirementTemplate $tpl) {
            $product = $tpl->posProduct;
            $qty = (float) ($tpl->quantity_per_student ?? 1);
            $unit = (float) ($product?->base_price ?? 0);

            return [
                'template_id' => $tpl->id,
                'product_id' => $product?->id,
                'name' => $tpl->requirementType?->name ?? $product?->name ?? 'Item',
                'brand' => $tpl->brand ?? $product?->brand,
                'unit' => $tpl->unit,
                'default_quantity' => $qty,
                'unit_price' => $unit,
                'line_total' => round($qty * $unit, 2),
                'is_verification_only' => (bool) $tpl->is_verification_only,
                'custody_type' => $tpl->custody_type,
                'image_url' => $this->productImageUrl($product),
                'images' => $this->productImageUrls($product),
            ];
        })->filter(fn ($row) => $row['product_id'])->values();

        $packageTotal = round($items->sum('line_total'), 2);

        return response()->json([
            'success' => true,
            'data' => [
                'student' => [
                    'id' => $student->id,
                    'name' => trim($student->first_name.' '.$student->last_name),
                    'classroom' => $student->classroom?->name,
                ],
                'payment' => $this->posService->paymentInstructions(),
                'package_total' => $packageTotal,
                'items' => $items,
            ],
        ]);
    }

    public function uniforms(Request $request)
    {
        $studentId = $request->integer('student_id') ?: null;
        $classroomId = null;
        if ($studentId) {
            $student = Student::query()->find($studentId);
            if ($student && $this->canAccessStudent($request, $student)) {
                $classroomId = $student->classroom_id;
            }
        }

        $products = Product::query()
            ->where('type', 'uniform')
            ->where('is_active', true)
            ->when(! $this->isStaff($request), fn ($q) => $q->where('is_publicly_visible', true))
            ->when($classroomId, function ($q) use ($classroomId) {
                $q->whereHas('classrooms', fn ($qq) => $qq->where('classrooms.id', $classroomId));
            })
            ->with(['activeVariants', 'classrooms:id,name'])
            ->orderBy('name')
            ->get()
            ->map(fn (Product $p) => $this->serializeUniform($p));

        return response()->json([
            'success' => true,
            'data' => $products,
            'payment' => $this->posService->paymentInstructions(),
        ]);
    }

    public function storeOrder(Request $request, Student $student)
    {
        if (! $this->canAccessStudent($request, $student) && ! $this->isStaff($request)) {
            return response()->json(['success' => false, 'message' => 'Not allowed.'], 403);
        }

        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:pos_products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.variant_id' => 'nullable|exists:pos_product_variants,id',
            'items.*.requirement_template_id' => 'nullable|exists:requirement_templates,id',
            'notes' => 'nullable|string|max:1000',
            'transaction_code' => 'nullable|string|max:64',
            'payment_method' => 'nullable|string|max:40',
        ]);

        $meta = [
            'user_id' => $this->isStaff($request) ? $request->user()?->id : null,
            'parent_id' => $student->parent_id,
            'notes' => $data['notes'] ?? null,
            'payment_method' => $data['payment_method'] ?? 'brian_paybill',
            'transaction_code' => $data['transaction_code'] ?? null,
            'placed_by' => $this->isStaff($request) ? 'admin' : 'parent',
        ];

        $order = $this->posService->createOrderForStudent($student, $data['items'], $meta);

        return response()->json([
            'success' => true,
            'message' => $order->isPaid()
                ? 'Order placed and payment confirmed.'
                : 'Order created. Pay via paybill then confirm with the transaction code.',
            'data' => $this->serializeOrder($order),
            'payment' => $this->posService->paymentInstructions(),
        ], 201);
    }

    public function confirmPayment(Request $request, Order $order)
    {
        if (! $this->canAccessOrder($request, $order)) {
            return response()->json(['success' => false, 'message' => 'Not allowed.'], 403);
        }

        $data = $request->validate([
            'transaction_code' => 'required|string|min:5|max:64',
            'payment_method' => 'nullable|string|max:40',
        ]);

        if ($order->isPaid()) {
            return response()->json(['success' => false, 'message' => 'Order is already paid.'], 422);
        }

        try {
            $order = $this->posService->confirmPayment(
                $order,
                $data['transaction_code'],
                $data['payment_method'] ?? 'brian_paybill'
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment confirmed. Items will be availed in school.',
            'data' => $this->serializeOrder($order),
        ]);
    }

    public function indexOrders(Request $request)
    {
        $query = Order::with(['student.classroom', 'items'])
            ->latest();

        if ($this->isStaff($request)) {
            if ($request->filled('payment_status')) {
                $query->where('payment_status', $request->string('payment_status'));
            }
            if ($request->filled('order_type')) {
                $query->where('order_type', $request->string('order_type'));
            }
            if ($request->filled('status')) {
                $query->where('status', $request->string('status'));
            }
            if ($request->filled('student_id')) {
                $query->where('student_id', (int) $request->input('student_id'));
            }
        } else {
            $studentIds = $this->parentStudentIds($request);
            $query->whereIn('student_id', $studentIds ?: [-1]);
        }

        $orders = $query->paginate(min(50, max(1, (int) $request->input('per_page', 20))));

        return response()->json([
            'success' => true,
            'data' => collect($orders->items())->map(fn (Order $o) => $this->serializeOrder($o))->values(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function showOrder(Request $request, Order $order)
    {
        if (! $this->canAccessOrder($request, $order)) {
            return response()->json(['success' => false, 'message' => 'Not allowed.'], 403);
        }

        $order->load(['student.classroom', 'items.product', 'items.variant']);

        return response()->json([
            'success' => true,
            'data' => $this->serializeOrder($order),
            'payment' => $this->posService->paymentInstructions(),
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        if (! $this->isStaff($request)) {
            return response()->json(['success' => false, 'message' => 'Not allowed.'], 403);
        }

        $data = $request->validate([
            'status' => 'required|in:pending,processing,completed,cancelled',
        ]);

        $order->status = $data['status'];
        if ($data['status'] === 'completed') {
            $order->completed_at = now();
            foreach ($order->items as $item) {
                if ($item->fulfillment_status !== 'fulfilled') {
                    $item->fulfillment_status = 'fulfilled';
                    $item->quantity_fulfilled = $item->quantity;
                    $item->save();
                }
            }
        }
        $order->save();

        return response()->json([
            'success' => true,
            'data' => $this->serializeOrder($order->fresh(['student.classroom', 'items'])),
        ]);
    }

    public function receipt(Request $request, Order $order, PosReceiptService $receipts)
    {
        if (! $this->canAccessOrder($request, $order)) {
            return response()->json(['success' => false, 'message' => 'Not allowed.'], 403);
        }

        return $receipts->download($order);
    }

    public function upsertUniform(Request $request)
    {
        if (! $this->isStaff($request)) {
            return response()->json(['success' => false, 'message' => 'Not allowed.'], 403);
        }

        $data = $request->validate([
            'id' => 'nullable|exists:pos_products,id',
            'name' => 'required|string|max:190',
            'base_price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'color' => 'nullable|string|max:80',
            'classroom_ids' => 'required|array|min:1',
            'classroom_ids.*' => 'integer|exists:classrooms,id',
            'stock_quantity' => 'nullable|integer|min:0',
            'track_stock' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'is_publicly_visible' => 'nullable|boolean',
            'variants' => 'nullable|array',
            'variants.*.id' => 'nullable|exists:pos_product_variants,id',
            'variants.*.variant_type' => 'nullable|in:size,color,other',
            'variants.*.name' => 'nullable|string|max:80',
            'variants.*.value' => 'required_with:variants|string|max:80',
            'variants.*.stock_quantity' => 'nullable|integer|min:0',
            'variants.*.price_adjustment' => 'nullable|numeric',
            'variants.*.is_active' => 'nullable|boolean',
        ]);

        $product = ! empty($data['id'])
            ? Product::where('type', 'uniform')->findOrFail($data['id'])
            : new Product(['type' => 'uniform']);

        $color = trim((string) ($data['color'] ?? ''));

        $product->fill([
            'name' => $data['name'],
            'type' => 'uniform',
            'category' => 'Uniform',
            'base_price' => $data['base_price'],
            'cost_price' => $data['cost_price'] ?? null,
            'stock_quantity' => $data['stock_quantity'] ?? $product->stock_quantity ?? 0,
            'track_stock' => $data['track_stock'] ?? true,
            'is_active' => $data['is_active'] ?? true,
            'is_publicly_visible' => $data['is_publicly_visible'] ?? true,
            'brand' => $color !== '' ? $color : null,
            'allow_backorders' => true,
            'specifications' => array_filter([
                'color' => $color !== '' ? $color : null,
            ]),
        ]);
        $product->save();
        $product->classrooms()->sync($data['classroom_ids']);

        foreach ($data['variants'] ?? [] as $variantData) {
            $payload = [
                'variant_type' => $variantData['variant_type'] ?? 'size',
                'name' => $variantData['name'] ?? ucfirst($variantData['variant_type'] ?? 'size'),
                'value' => $variantData['value'],
                'stock_quantity' => $variantData['stock_quantity'] ?? 0,
                'price_adjustment' => $variantData['price_adjustment'] ?? 0,
                'is_active' => $variantData['is_active'] ?? true,
            ];
            if (! empty($variantData['id'])) {
                $product->variants()->where('id', $variantData['id'])->update($payload);
            } else {
                $product->variants()->create($payload);
            }
        }

        $product->load(['activeVariants', 'classrooms:id,name']);

        return response()->json([
            'success' => true,
            'data' => $this->serializeUniform($product),
        ]);
    }

    public function updateUniformStock(Request $request, Product $product)
    {
        if (! $this->isStaff($request)) {
            return response()->json(['success' => false, 'message' => 'Not allowed.'], 403);
        }
        if ($product->type !== 'uniform') {
            return response()->json(['success' => false, 'message' => 'Not a uniform product.'], 422);
        }

        $data = $request->validate([
            'stock_quantity' => 'nullable|integer|min:0',
            'variants' => 'nullable|array',
            'variants.*.id' => 'required|exists:pos_product_variants,id',
            'variants.*.stock_quantity' => 'required|integer|min:0',
        ]);

        if (array_key_exists('stock_quantity', $data)) {
            $product->stock_quantity = $data['stock_quantity'];
            $product->save();
        }

        foreach ($data['variants'] ?? [] as $row) {
            $product->variants()->where('id', $row['id'])->update([
                'stock_quantity' => $row['stock_quantity'],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $this->serializeUniform($product->fresh('activeVariants')),
        ]);
    }

    private function serializeUniform(Product $product): array
    {
        $variants = $product->relationLoaded('activeVariants')
            ? $product->activeVariants
            : $product->variants()->where('is_active', true)->get();

        return [
            'id' => $product->id,
            'name' => $product->name,
            'brand' => $product->brand,
            'color' => data_get($product->specifications, 'color') ?: $product->brand,
            'base_price' => (float) $product->base_price,
            'cost_price' => $product->cost_price !== null ? (float) $product->cost_price : null,
            'stock_quantity' => (int) $product->stock_quantity,
            'total_stock' => (int) ($variants->sum('stock_quantity') ?: $product->stock_quantity),
            'track_stock' => (bool) $product->track_stock,
            'is_active' => (bool) $product->is_active,
            'is_publicly_visible' => (bool) $product->is_publicly_visible,
            'image_url' => $this->productImageUrl($product),
            'images' => $this->productImageUrls($product),
            'classrooms' => $product->relationLoaded('classrooms')
                ? $product->classrooms->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values()
                : [],
            'variants' => $variants->map(fn ($v) => [
                'id' => $v->id,
                'variant_type' => $v->variant_type,
                'name' => $v->name,
                'value' => $v->value,
                'stock_quantity' => (int) $v->stock_quantity,
                'price_adjustment' => (float) $v->price_adjustment,
                'price' => (float) ((float) $product->base_price + (float) $v->price_adjustment),
                'is_active' => (bool) $v->is_active,
            ])->values(),
        ];
    }

    private function serializeOrder(Order $order): array
    {
        $order->loadMissing(['student.classroom', 'items']);

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'order_type' => $order->order_type,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'payment_method' => $order->payment_method,
            'payment_reference' => $order->payment_reference,
            'subtotal' => (float) $order->subtotal,
            'total_amount' => (float) $order->total_amount,
            'balance' => (float) $order->balance,
            'paid_at' => optional($order->paid_at)?->toIso8601String(),
            'created_at' => optional($order->created_at)?->toIso8601String(),
            'student' => $order->student ? [
                'id' => $order->student->id,
                'name' => trim($order->student->first_name.' '.$order->student->last_name),
                'classroom' => $order->student->classroom?->name,
            ] : null,
            'items' => $order->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'requirement_template_id' => $item->requirement_template_id,
                'product_name' => $item->product_name,
                'variant_name' => $item->variant_name,
                'quantity' => (int) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total_price' => (float) $item->total_price,
                'fulfillment_status' => $item->fulfillment_status,
            ])->values(),
        ];
    }

    private function productImageUrls(?Product $product): array
    {
        if (! $product) {
            return [];
        }
        return collect($product->images ?? [])
            ->filter()
            ->map(fn ($path) => storage_public_url($path) ?: asset('storage/'.$path))
            ->values()
            ->all();
    }

    private function productImageUrl(?Product $product): ?string
    {
        $urls = $this->productImageUrls($product);

        return $urls[0] ?? null;
    }

    private function canAccessStudent(Request $request, Student $student): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }
        if ($this->isStaff($request)) {
            return true;
        }

        return method_exists($user, 'canAccessStudent') && $user->canAccessStudent((int) $student->id);
    }

    private function canAccessOrder(Request $request, Order $order): bool
    {
        if ($this->isStaff($request)) {
            return true;
        }
        if (! $order->student_id) {
            return false;
        }

        return $this->canAccessStudent($request, Student::findOrFail($order->student_id));
    }

    private function isStaff(Request $request): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        return method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Finance Officer', 'Accountant', 'Teacher']);
    }

    private function parentStudentIds(Request $request): array
    {
        $user = $request->user();
        if (! $user || ! method_exists($user, 'accessibleStudentIds')) {
            // Fallback: parent_info linked students
            if ($user && Schema::hasColumn('students', 'parent_id') && $user->parentInfo) {
                return Student::query()->where('parent_id', $user->parentInfo->id)->pluck('id')->all();
            }

            return [];
        }

        return $user->accessibleStudentIds();
    }

    private function isNewJoiner(Student $student, $currentTerm): bool
    {
        if (! $currentTerm) {
            return false;
        }
        $yearId = $currentTerm->academic_year_id;
        $termId = $currentTerm->id;

        return (int) ($student->enrollment_year ?? 0) === (int) $yearId
            && (int) ($student->enrollment_term ?? 0) === (int) $termId;
    }
}
