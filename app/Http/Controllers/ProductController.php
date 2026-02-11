<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // Get all products
    public function index()
    {
        $products = Product::with('category')->get();
        return response()->json($products);
    }

    // Get single product by ID
    public function show($id)
    {
        $product = Product::with('category')->find($id);
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }
        return response()->json($product);
    }

    // Create new product
    public function store(Request $request)
    {
        $request->validate([
            'name'   => 'required|string|max:255',
            'category_id' => 'required|integer|exists:categories,id',
            'buying_price'   => 'nullable|numeric',
            'selling_price'  => 'nullable|numeric',
            'quantity'       => 'nullable|integer',
        ]);

        $product = Product::create($request->only([
            'name', 'description', 'quantity', 'buying_price', 'selling_price', 'category_id'
        ]));

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product
        ], 201);
    }

    // Update existing product
    public function update(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'quantity' => 'sometimes|required|integer|min:0',
            'buying_price' => 'sometimes|required|numeric|min:0',
            'selling_price' => 'sometimes|required|numeric|min:0',
            'category_id' => 'sometimes|required|exists:categories,id',
        ]);

        $product->update($request->only([
            'name', 'description', 'quantity', 'buying_price', 'selling_price', 'category_id'
        ]));

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product
        ]);
    }

    // Delete product
    public function destroy($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }
    // Get total product count (not by quantity)
    public function totalCount()
    {
        $totalProducts = Product::count();
    
        return response()->json([
            'total_products' => $totalProducts
        ]);
    }
}
