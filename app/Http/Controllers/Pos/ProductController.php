<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Pos\Product;
use App\Models\InventoryItem;
use App\Models\RequirementType;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['inventoryItem', 'requirementType']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->boolean('low_stock')) {
            $query->whereRaw('stock_quantity <= min_stock_level');
        }

        if ($request->boolean('out_of_stock')) {
            $query->where('stock_quantity', '<=', 0)->where('track_stock', true);
        }

        $products = $query->orderBy('name')->paginate(30);
        $categories = Product::distinct()->pluck('category')->filter();
        $types = ['stationery', 'uniform', 'other'];

        ActivityLog::log('view', null, 'Viewed POS products list');

        return view('pos.products.index', compact('products', 'categories', 'types'));
    }

    public function create()
    {
        // Manual POS entry is uniforms-only for now.
        $type = request('type', 'uniform');
        if ($type !== 'uniform') {
            return redirect()
                ->route('pos.products.create', ['type' => 'uniform'])
                ->with('info', 'Manual POS products are limited to uniforms for now.');
        }

        $classrooms = \App\Models\Academics\Classroom::query()
            ->withoutGlobalScope('offered')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('pos.products.create-uniform', compact('classrooms'));
    }

    public function store(Request $request)
    {
        // Manual POS create is uniforms-only.
        if ($request->input('type') !== 'uniform') {
            return redirect()
                ->route('pos.products.create', ['type' => 'uniform'])
                ->with('error', 'Manual POS products are limited to uniforms for now.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:uniform',
            'color' => 'nullable|string|max:80',
            'base_price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'classroom_ids' => 'required|array|min:1',
            'classroom_ids.*' => 'integer|exists:classrooms,id',
            'sizes_text' => 'nullable|string|max:2000',
            'track_stock' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'images' => 'nullable|array',
            'images.*' => 'image|max:2048',
        ]);

        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imagePaths[] = $image->store('pos/products', 'public');
            }
        }

        $color = trim((string) ($validated['color'] ?? ''));
        $sizes = $this->parseSizesText($validated['sizes_text'] ?? '');

        $product = Product::create([
            'name' => $validated['name'],
            'type' => 'uniform',
            'category' => 'Uniform',
            'brand' => $color !== '' ? $color : null,
            'base_price' => $validated['base_price'],
            'cost_price' => $validated['cost_price'] ?? null,
            'stock_quantity' => 0,
            'min_stock_level' => 0,
            'track_stock' => $request->boolean('track_stock', true),
            'allow_backorders' => true,
            'is_active' => $request->boolean('is_active', true),
            'is_publicly_visible' => true,
            'is_featured' => false,
            'images' => $imagePaths ?: null,
            'specifications' => array_filter([
                'color' => $color !== '' ? $color : null,
            ]),
        ]);

        $product->classrooms()->sync($validated['classroom_ids']);

        if ($color !== '') {
            $product->variants()->create([
                'variant_type' => 'color',
                'name' => 'Colour',
                'value' => $color,
                'stock_quantity' => 0,
                'price_adjustment' => 0,
                'is_active' => true,
            ]);
        }

        foreach ($sizes as $size) {
            $product->variants()->create([
                'variant_type' => 'size',
                'name' => 'Size',
                'value' => $size,
                'stock_quantity' => 0,
                'price_adjustment' => 0,
                'is_active' => true,
            ]);
        }

        ActivityLog::log('create', $product, "Created POS uniform: {$product->name}");

        return redirect()->route('pos.uniforms.show', $product)
            ->with('success', 'Uniform created successfully.');
    }

    private function parseSizesText(?string $text): array
    {
        if ($text === null || trim($text) === '') {
            return [];
        }

        $parts = preg_split('/[\s,;\/|]+/', $text) ?: [];
        $sizes = [];
        foreach ($parts as $part) {
            $value = trim((string) $part);
            if ($value === '') {
                continue;
            }
            $sizes[strtoupper($value)] = $value;
        }

        return array_values($sizes);
    }

    public function show(Product $product)
    {
        $product->load(['variants', 'orderItems.order', 'requirementTemplates']);
        
        return view('pos.products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $inventoryItems = InventoryItem::active()->orderBy('name')->get();
        $requirementTypes = RequirementType::active()->orderBy('name')->get();
        $categories = Product::distinct()->pluck('category')->filter();

        return view('pos.products.edit', compact('product', 'inventoryItems', 'requirementTypes', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:255|unique:pos_products,sku,' . $product->id,
            'barcode' => 'nullable|string|max:255',
            'type' => 'required|in:stationery,uniform,other',
            'inventory_item_id' => 'nullable|exists:inventory_items,id',
            'requirement_type_id' => 'nullable|exists:requirement_types,id',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'base_price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'min_stock_level' => 'nullable|integer|min:0',
            'track_stock' => 'boolean',
            'allow_backorders' => 'boolean',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'images' => 'nullable|array',
            'images.*' => 'image|max:2048',
            'specifications' => 'nullable|array',
            'remove_images' => 'nullable|array',
        ]);

        // Handle image removal
        if ($request->has('remove_images') && is_array($request->remove_images)) {
            $currentImages = $product->images ?? [];
            foreach ($request->remove_images as $imagePath) {
                if (in_array($imagePath, $currentImages)) {
                    storage_public()->delete($imagePath);
                    $currentImages = array_diff($currentImages, [$imagePath]);
                }
            }
            $validated['images'] = array_values($currentImages);
        } else {
            // Keep existing images if not removing any
            $validated['images'] = $product->images ?? [];
        }

        // Handle new image uploads
        if ($request->hasFile('images')) {
            $currentImages = $validated['images'] ?? [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('pos/products', 'public');
                $currentImages[] = $path;
            }
            $validated['images'] = $currentImages;
        }

        $product->update($validated);

        ActivityLog::log('update', $product, "Updated POS product: {$product->name}");

        return redirect()->route('pos.products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        // Delete associated images
        if ($product->images) {
            foreach ($product->images as $imagePath) {
                storage_public()->delete($imagePath);
            }
        }

        $productName = $product->name;
        $product->delete();

        ActivityLog::log('delete', null, "Deleted POS product: {$productName}");

        return redirect()->route('pos.products.index')
            ->with('success', 'Product deleted successfully.');
    }

    public function adjustStock(Request $request, Product $product)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer',
            'notes' => 'nullable|string',
        ]);

        $oldQuantity = $product->stock_quantity;
        $product->stock_quantity = max(0, $product->stock_quantity + $validated['quantity']);
        $product->save();

        ActivityLog::log('update', $product, "Adjusted stock for {$product->name}: {$oldQuantity} → {$product->stock_quantity}");

        return redirect()->back()
            ->with('success', 'Stock adjusted successfully.');
    }

    public function bulkImport(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:5120',
        ]);

        try {
            $import = new \App\Imports\PosProductImport();
            Excel::import($import, $request->file('file'));

            ActivityLog::log('create', null, 'Bulk imported POS products');

            return redirect()->route('pos.products.index')
                ->with('success', 'Products imported successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        $export = new \App\Exports\PosProductTemplateExport();
        return Excel::download($export, 'pos_products_template.xlsx');
    }
}

