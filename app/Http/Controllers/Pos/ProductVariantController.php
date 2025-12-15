<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Pos\Product;
use App\Models\Pos\ProductVariant;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
    public function index(Product $product)
    {
        $variants = $product->variants()->orderBy('variant_type')->orderBy('value')->get();

        return response()->json($variants);
    }

    public function store(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'value' => 'required|string|max:255',
            'variant_type' => 'required|string|max:50',
            'price_adjustment' => 'nullable|numeric',
            'stock_quantity' => 'required|integer|min:0',
            'sku' => 'nullable|string|max:255|unique:pos_product_variants,sku',
            'barcode' => 'nullable|string|max:255',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        // If this is set as default, unset other defaults
        if ($validated['is_default'] ?? false) {
            $product->variants()->update(['is_default' => false]);
        }

        $variant = $product->variants()->create($validated);

        ActivityLog::log('create', $variant, "Created variant for product: {$product->name}");

        return redirect()->back()
            ->with('success', 'Variant created successfully.');
    }

    public function update(Request $request, ProductVariant $variant)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'value' => 'required|string|max:255',
            'variant_type' => 'required|string|max:50',
            'price_adjustment' => 'nullable|numeric',
            'stock_quantity' => 'required|integer|min:0',
            'sku' => 'nullable|string|max:255|unique:pos_product_variants,sku,' . $variant->id,
            'barcode' => 'nullable|string|max:255',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        // If this is set as default, unset other defaults
        if ($validated['is_default'] ?? false) {
            $variant->product->variants()->where('id', '!=', $variant->id)->update(['is_default' => false]);
        }

        $variant->update($validated);

        ActivityLog::log('update', $variant, "Updated variant for product: {$variant->product->name}");

        return redirect()->back()
            ->with('success', 'Variant updated successfully.');
    }

    public function destroy(ProductVariant $variant)
    {
        $productName = $variant->product->name;
        $variantName = $variant->name . ': ' . $variant->value;
        $variant->delete();

        ActivityLog::log('delete', null, "Deleted variant {$variantName} for product: {$productName}");

        return redirect()->back()
            ->with('success', 'Variant deleted successfully.');
    }
}



