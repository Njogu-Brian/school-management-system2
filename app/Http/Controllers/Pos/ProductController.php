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
        $inventoryItems = InventoryItem::active()->orderBy('name')->get();
        $requirementTypes = RequirementType::active()->orderBy('name')->get();
        $categories = Product::distinct()->pluck('category')->filter();

        return view('pos.products.create', compact('inventoryItems', 'requirementTypes', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:255|unique:pos_products,sku',
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
        ]);

        // Handle image uploads
        if ($request->hasFile('images')) {
            $imagePaths = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('pos/products', 'public');
                $imagePaths[] = $path;
            }
            $validated['images'] = $imagePaths;
        }

        $product = Product::create($validated);

        ActivityLog::log('create', $product, "Created POS product: {$product->name}");

        return redirect()->route('pos.products.index')
            ->with('success', 'Product created successfully.');
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
                    Storage::disk('public')->delete($imagePath);
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
                Storage::disk('public')->delete($imagePath);
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

