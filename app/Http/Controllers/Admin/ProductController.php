<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductPrice;
use App\Models\ProductImage;

class ProductController extends Controller
{
    public function index()
    {
        return Product::with(['variants.prices', 'images'])->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'slug' => 'required|unique:products',
            'overview' => 'nullable|string',
            'whats_included' => 'nullable|string',
            'is_variable' => 'required|boolean',
            'variants' => 'array',
            'variants.*.variant_name' => 'required|string',
            'variants.*.sku' => 'required|string|unique:product_variants,sku',
            'variants.*.price' => 'required|numeric',
            'images' => 'array',
            'images.*.image_path' => 'required|string',
        ]);

        // Create product
        $product = Product::create($validated);

        // Add variants
        foreach ($request->variants as $variant) {
            $newVariant = ProductVariant::create([
                'product_id' => $product->id,
                'variant_name' => $variant['variant_name'],
                'sku' => $variant['sku'],
            ]);

            // Add price
            ProductPrice::create([
                'product_id' => $product->id,
                'variant_id' => $newVariant->id,
                'price' => $variant['price'],
            ]);
        }

        // Add images
        foreach ($request->images as $image) {
            ProductImage::create([
                'product_id' => $product->id,
                'image_url' => $image['image_path'],
            ]);
        }

        return response()->json(Product::with(['variants.prices', 'images'])->find($product->id), 201);
    }

    public function show($id)
    {
        return response()->json(Product::with(['variants.prices', 'images'])->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $product->update($request->all());
        return response()->json($product);
    }

    public function destroy($id)
    {
        Product::findOrFail($id)->delete();
        return response()->json(['message' => 'Product deleted successfully']);
    }
}

